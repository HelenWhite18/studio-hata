<?php
/**
 * Plugin Name: HATA core
 * Description: Shared HATA indexing, navigation, contact, and structured-data logic.
 */
if (!defined('ABSPATH')) {
    exit;
}

/* Keep local and staging environments out of search indexes. */
if (wp_get_environment_type() !== 'production') {
    add_action('send_headers', function (): void {
        header('X-Robots-Tag: noindex, nofollow, noarchive', true);
    });
    add_filter('wp_robots', function (array $robots): array {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
        return $robots;
    }, 99);
    add_filter('pre_option_blog_public', '__return_zero');
}

function hata_lang(): string
{
    return function_exists('pll_current_language')
        ? (pll_current_language('slug') ?: 'nl')
        : 'nl';
}

/** Read deployment configuration without storing client data in Git or WordPress. */
function hata_config(string $name, string $default = ''): string
{
    if (defined($name) && is_string(constant($name))) {
        return trim(constant($name));
    }

    $value = getenv($name);
    return is_string($value) && $value !== '' ? trim($value) : $default;
}

/** Render the language-specific Core Navigation post selected by the bootstrap. */
function hata_render_navigation(array $attributes): string
{
    $area = ($attributes['area'] ?? 'header') === 'footer' ? 'footer' : 'header';
    $menus = get_option('hata_navigation_posts', []);
    $ref = (int) ($menus[$area][hata_lang()] ?? 0);

    if (!$ref || get_post_type($ref) !== 'wp_navigation') {
        return '';
    }

    $attrs = [
        'ref' => $ref,
        'overlayMenu' => $area === 'header' ? 'mobile' : 'never',
        'className' => 'hata-navigation hata-navigation-' . $area,
        'layout' => [
            'type' => 'flex',
            'justifyContent' => $area === 'header' ? 'right' : 'left',
            'orientation' => 'horizontal',
        ],
    ];

    return do_blocks('<!-- wp:navigation ' . wp_json_encode($attrs) . ' /-->');
}

/** Use Polylang's URLs; only the visible labels are project-specific. */
function hata_render_language_switcher(): string
{
    if (!function_exists('pll_the_languages')) {
        return '';
    }

    $languages = pll_the_languages([
        'raw' => 1,
        'hide_if_empty' => 0,
        'hide_if_no_translation' => 0,
    ]);

    if (!is_array($languages)) {
        return '';
    }

    $labels = ['nl' => 'NL', 'en' => 'EN', 'uk' => 'UA'];
    $items = [];

    foreach ($labels as $slug => $label) {
        if (!isset($languages[$slug]['url'])) {
            continue;
        }

        $current = !empty($languages[$slug]['current_lang']);
        $items[] = sprintf(
            '<li><a href="%s" lang="%s" hreflang="%s"%s>%s</a></li>',
            esc_url($languages[$slug]['url']),
            esc_attr($slug),
            esc_attr($slug),
            $current ? ' aria-current="page"' : '',
            esc_html($label)
        );
    }

    return $items
        ? '<nav class="hata-language-switcher" aria-label="Language"><ul>'
            . implode('', $items) . '</ul></nav>'
        : '';
}

/** Hide contact details until deployment values are configured. */
function hata_render_contact_details(): string
{
    $email = sanitize_email(hata_config('HATA_CONTACT_EMAIL'));
    $instagram = esc_url(hata_config('HATA_INSTAGRAM_URL'));
    $links = [];

    if ($instagram) {
        $links[] = '<a href="' . $instagram
            . '" rel="noopener noreferrer">Instagram</a>';
    }
    if ($email) {
        $links[] = '<a href="mailto:' . esc_attr($email) . '">'
            . esc_html($email) . '</a>';
    }

    return $links
        ? '<address class="hata-contact-details">'
            . implode('<span aria-hidden="true"> · </span>', $links)
            . '</address>'
        : '';
}

function hata_render_disclaimer(): string
{
    $copy = [
        'nl' => 'Aanvullende praktijk, geen medische zorg.',
        'en' => 'Complementary practice, not medical care.',
        'uk' => 'Комплементарна практика, не медична допомога.',
    ];

    return '<p class="hata-disclaimer">'
        . esc_html($copy[hata_lang()] ?? $copy['en']) . '</p>';
}

/* Dynamic blocks keep language and deployment data out of static template parts. */
add_action('init', function (): void {
    register_block_type('hata/navigation', [
        'api_version' => 3,
        'attributes' => [
            'area' => ['type' => 'string', 'default' => 'header'],
        ],
        'render_callback' => 'hata_render_navigation',
    ]);
    register_block_type('hata/language-switcher', [
        'api_version' => 3,
        'render_callback' => 'hata_render_language_switcher',
    ]);
    register_block_type('hata/contact-details', [
        'api_version' => 3,
        'render_callback' => 'hata_render_contact_details',
    ]);
    register_block_type('hata/disclaimer', [
        'api_version' => 3,
        'render_callback' => 'hata_render_disclaimer',
    ]);
});

/* Structured data uses the same deployment configuration as the footer. */
add_action('wp_head', function (): void {
    if (is_admin() || !is_front_page()) {
        return;
    }

    $lang = hata_lang();
    $home = function_exists('pll_home_url') ? pll_home_url($lang) : home_url('/');
    $descriptions = [
        'nl' => 'Een zachte plek voor vrouwen om te vertragen en weer contact te maken met je hart.',
        'en' => 'A gentle space for women to slow down and reconnect with your heart.',
        'uk' => 'М’який простір для жінок, щоб сповільнитися і відновити зв’язок зі своїм серцем.',
    ];
    $instagram = esc_url(hata_config('HATA_INSTAGRAM_URL'));
    $email = sanitize_email(hata_config('HATA_CONTACT_EMAIL'));
    $business = [
        '@context' => 'https://schema.org',
        '@type' => 'HealthAndBeautyBusiness',
        '@id' => trailingslashit($home) . '#business',
        'name' => 'HATA Studio',
        'url' => $home,
        'description' => $descriptions[$lang] ?? $descriptions['en'],
        'inLanguage' => $lang,
        'areaServed' => [
            '@type' => 'AdministrativeArea',
            'name' => hata_config('HATA_REGION', 'Netherlands'),
        ],
        'knowsLanguage' => ['nl', 'en', 'uk'],
    ];

    if ($instagram) {
        $business['sameAs'] = [$instagram];
    }
    if ($email) {
        $business['email'] = $email;
    }

    echo '<script type="application/ld+json">'
        . wp_json_encode($business, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        . "</script>\n";
}, 20);

/* Details blocks become FAQ structured data without coupling this to a theme. */
add_action('wp_head', function (): void {
    if (is_admin() || !is_singular()) {
        return;
    }

    $content = (string) get_post_field('post_content', get_queried_object_id());
    if (!preg_match_all(
        '#<details[^>]*>\s*<summary>(.*?)</summary>(.*?)</details>#s',
        $content,
        $matches,
        PREG_SET_ORDER
    )) {
        return;
    }

    $items = [];
    foreach ($matches as $match) {
        $question = trim(wp_strip_all_tags($match[1]));
        $answer = trim(wp_strip_all_tags(preg_replace('#<!--.*?-->#s', '', $match[2])));
        if ($question && $answer) {
            $items[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer],
            ];
        }
    }

    if ($items) {
        echo '<script type="application/ld+json">'
            . wp_json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'inLanguage' => hata_lang(),
                'mainEntity' => $items,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . "</script>\n";
    }
}, 21);
