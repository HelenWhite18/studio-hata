<?php
/**
 * Bootstrap HATA A pages and Polylang relationships.
 *
 * Run with WP-CLI. Existing managed pages are preserved unless
 * HATA_BOOTSTRAP_FORCE=1 is set. New pages default to draft status.
 */

if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) {
    return;
}

if (wp_get_environment_type() === 'production') {
    WP_CLI::error('The HATA A bootstrap cannot run in production.');
}

if (!function_exists('pll_languages_list')) {
    WP_CLI::error('Polylang must be active before running the HATA A bootstrap.');
}

$languages = ['nl', 'en', 'uk'];
$installed = pll_languages_list(['fields' => 'slug']);
$missing = array_diff($languages, $installed);

if ($missing) {
    WP_CLI::error('Add these Polylang languages first: ' . implode(', ', $missing));
}

$status = getenv('HATA_BOOTSTRAP_STATUS') === 'publish' ? 'publish' : 'draft';
$force = getenv('HATA_BOOTSTRAP_FORCE') === '1';
$force_navigation = $force
    || getenv('HATA_BOOTSTRAP_FORCE_NAVIGATION') === '1';
$root = __DIR__;

/**
 * Load and validate a JSON file.
 *
 * @return array<string, mixed>
 */
function hata_a_load_json(string $path): array
{
    $data = json_decode((string) file_get_contents($path), true);

    if (!is_array($data)) {
        WP_CLI::error('Invalid bootstrap JSON: ' . $path);
    }

    return $data;
}

/** Return the configured language root used for each static front page. */
function hata_a_language_home_url(string $language): string
{
    $default = function_exists('pll_default_language')
        ? pll_default_language('slug')
        : 'nl';

    return $language === $default
        ? home_url('/')
        : home_url('/' . $language . '/');
}

/** Resolve an internal page key or a configured external destination. */
function hata_a_resolve_target(
    string $target,
    string $language,
    array $ids
): string {
    if ($target === '{{BOOKING}}') {
        $booking_url = function_exists('hata_config')
            ? hata_config('HATA_BOOKING_URL')
            : '';

        if ($booking_url === '') {
            WP_CLI::error('Set HATA_BOOKING_URL before running the HATA A bootstrap.');
        }

        return esc_url_raw($booking_url);
    }

    return isset($ids[$target][$language])
        ? get_permalink($ids[$target][$language])
        : hata_a_language_home_url($language);
}

/**
 * Find a page previously managed by this bootstrap.
 */
function hata_a_find_managed_page(string $identity): ?WP_Post
{
    $posts = get_posts(
        [
            'post_type' => 'page',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'meta_key' => '_hata_bootstrap_id',
            'meta_value' => $identity,
            'suppress_filters' => false,
        ]
    );

    return $posts[0] ?? null;
}

/**
 * Render the small, reviewable content vocabulary into core blocks.
 *
 * @param array<int, array<string, string>> $blocks
 * @param array<string, array<string, int>> $ids
 */
function hata_a_render_blocks(array $blocks, string $language, array $ids): string
{
    $output = '';

    foreach ($blocks as $block) {
        $type = $block['type'] ?? '';
        $text = esc_html($block['text'] ?? '');

        if ($type === 'h1') {
            $output .= '<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">'
                . $text . '</h1><!-- /wp:heading -->';
        } elseif ($type === 'p') {
            $output .= '<!-- wp:paragraph --><p>' . $text . '</p><!-- /wp:paragraph -->';
        } elseif ($type === 'quote') {
            $output .= '<!-- wp:quote --><blockquote class="wp-block-quote"><p>'
                . $text . '</p></blockquote><!-- /wp:quote -->';
        } elseif ($type === 'note') {
            $output .= '<!-- wp:paragraph {"className":"hata-draft-note"} -->'
                . '<p class="hata-draft-note">' . $text . '</p><!-- /wp:paragraph -->';
        } elseif ($type === 'button') {
            $target = $block['target'] ?? '';
            $url = hata_a_resolve_target($target, $language, $ids);
            $output .= '<!-- wp:buttons --><div class="wp-block-buttons">'
                . '<!-- wp:button --><div class="wp-block-button">'
                . '<a class="wp-block-button__link wp-element-button" href="'
                . esc_url($url) . '">' . $text . '</a></div><!-- /wp:button -->'
                . '</div><!-- /wp:buttons -->';
        }
    }

    return $output;
}

/** Build a responsive Core Image block for an available local placeholder. */
function hata_a_placeholder_image_block(
    string $key,
    string $alt,
    string $marker
): string {
    if (!function_exists('hata_a_placeholder_image_data')) {
        return '';
    }

    $image = hata_a_placeholder_image_data($key);
    if (!$image) {
        return '';
    }

    $class = 'hata-placeholder-image ' . $marker;
    $attributes = [
        'sizeSlug' => 'large',
        'linkDestination' => 'none',
        'className' => $class,
    ];
    $dimensions = $image['width'] && $image['height']
        ? ' width="' . (int) $image['width'] . '" height="' . (int) $image['height'] . '"'
        : '';
    $loading = $marker === 'hata-placeholder-home-hero'
        ? ' loading="eager" decoding="async" fetchpriority="high"'
        : ' loading="lazy" decoding="async"';

    return '<!-- wp:image ' . wp_json_encode($attributes) . ' -->'
        . '<figure class="wp-block-image size-large ' . esc_attr($class) . '">'
        . '<img src="' . esc_url($image['url']) . '" alt="' . esc_attr($alt) . '"'
        . $dimensions . $loading . '/></figure><!-- /wp:image -->';
}

/**
 * Add one managed placeholder without replacing copy edited in the Site Editor.
 * For Home, the image is appended inside a named group; otherwise it follows H1.
 */
function hata_a_ensure_placeholder_image(
    int $post_id,
    string $key,
    string $alt,
    string $marker,
    string $group_class = ''
): void {
    $content = (string) get_post_field('post_content', $post_id);
    if (strpos($content, $marker) !== false) {
        $image = function_exists('hata_a_placeholder_image_data')
            ? hata_a_placeholder_image_data($key)
            : [];
        $processor = new WP_HTML_Tag_Processor($content);

        while ($image && $processor->next_tag('figure')) {
            $classes = preg_split(
                '/\s+/',
                (string) $processor->get_attribute('class')
            );
            if (!in_array($marker, $classes, true)) {
                continue;
            }
            if (!$processor->next_tag('img')) {
                break;
            }

            $processor->set_attribute('src', esc_url_raw($image['url']));
            $processor->set_attribute('alt', $alt);
            if ($image['width'] && $image['height']) {
                $processor->set_attribute('width', (string) $image['width']);
                $processor->set_attribute('height', (string) $image['height']);
            }
            $processor->set_attribute('decoding', 'async');
            if ($marker === 'hata-placeholder-home-hero') {
                $processor->set_attribute('loading', 'eager');
                $processor->set_attribute('fetchpriority', 'high');
            } else {
                $processor->set_attribute('loading', 'lazy');
            }

            $updated_content = $processor->get_updated_html();
            if ($updated_content !== $content) {
                wp_update_post(
                    ['ID' => $post_id, 'post_content' => $updated_content]
                );
            }
            break;
        }
        return;
    }

    $markup = hata_a_placeholder_image_block($key, $alt, $marker);
    if ($markup === '') {
        return;
    }

    $image_blocks = parse_blocks($markup);
    $image_block = $image_blocks[0] ?? null;
    if (!$image_block) {
        return;
    }

    $blocks = parse_blocks($content);
    $inserted = false;

    if ($group_class !== '') {
        foreach ($blocks as &$block) {
            $classes = preg_split(
                '/\s+/',
                (string) ($block['attrs']['className'] ?? '')
            );
            if (
                $block['blockName'] !== 'core/group'
                || !in_array($group_class, $classes, true)
            ) {
                continue;
            }

            array_unshift($block['innerBlocks'], $image_block);
            array_splice(
                $block['innerContent'],
                min(1, count($block['innerContent'])),
                0,
                [null]
            );
            $inserted = true;
            break;
        }
        unset($block);
    } else {
        foreach ($blocks as $index => $block) {
            if ($block['blockName'] === 'core/heading') {
                array_splice($blocks, $index + 1, 0, [$image_block]);
                $inserted = true;
                break;
            }
        }
    }

    if (!$inserted) {
        WP_CLI::warning("Could not place placeholder image {$marker}.");
        return;
    }

    $result = wp_update_post(
        ['ID' => $post_id, 'post_content' => serialize_blocks($blocks)],
        true
    );
    if (is_wp_error($result)) {
        WP_CLI::error($result);
    }
}

/** Move one managed child block to the start of a named group. */
function hata_a_move_group_child_to_start(
    int $post_id,
    string $group_class,
    string $child_class
): void {
    $content = (string) get_post_field('post_content', $post_id);
    $blocks = parse_blocks($content);
    $changed = false;

    foreach ($blocks as &$block) {
        $group_classes = preg_split(
            '/\s+/',
            (string) ($block['attrs']['className'] ?? '')
        );
        if (
            $block['blockName'] !== 'core/group'
            || !in_array($group_class, $group_classes, true)
        ) {
            continue;
        }

        foreach ($block['innerBlocks'] as $index => $inner_block) {
            $child_classes = preg_split(
                '/\s+/',
                (string) ($inner_block['attrs']['className'] ?? '')
            );
            if (!in_array($child_class, $child_classes, true) || $index === 0) {
                continue;
            }

            array_splice($block['innerBlocks'], $index, 1);
            array_unshift($block['innerBlocks'], $inner_block);
            $changed = true;
            break;
        }
        break;
    }
    unset($block);

    if ($changed) {
        wp_update_post([
            'ID' => $post_id,
            'post_content' => serialize_blocks($blocks),
        ]);
    }
}

/**
 * Refresh generated button destinations without replacing edited page copy.
 *
 * @param array<int, array<string, string>> $blocks
 * @param array<string, array<string, int>> $ids
 */
function hata_a_refresh_button_links(
    string $post_content,
    array $blocks,
    string $language,
    array $ids
): string {
    $targets = [];

    foreach ($blocks as $block) {
        if (($block['type'] ?? '') !== 'button') {
            continue;
        }

        $target = $block['target'] ?? '';
        $targets[] = hata_a_resolve_target($target, $language, $ids);
    }

    if (!$targets) {
        return $post_content;
    }

    $processor = new WP_HTML_Tag_Processor($post_content);
    $index = 0;

    while (
        isset($targets[$index])
        && $processor->next_tag(
            ['tag_name' => 'A', 'class_name' => 'wp-block-button__link']
        )
    ) {
        $processor->set_attribute('href', esc_url_raw($targets[$index]));
        ++$index;
    }

    return $processor->get_updated_html();
}

/** Add reviewable Home anchors without replacing existing page content. */
function hata_a_ensure_home_sections(int $post_id, string $language): void
{
    $copy = [
        'nl' => [
            'about_title' => 'Over HATA',
            'about' => 'HATA Studio is een rustige plek voor vrouwen om te vertragen, op adem te komen en opnieuw contact te maken met zichzelf.',
            'faq_title' => 'Veelgestelde vragen',
            'questions' => [
                ['Wat kan ik verwachten?', 'Een rustige sessie met ruimte voor een kort gesprek, ontspanning en afronding op jouw tempo.'],
                ['Heb ik ervaring nodig?', 'Nee. Je hoeft niets voor te bereiden en bent welkom zoals je bent.'],
                ['Is dit medische zorg?', 'Nee. Dit is een aanvullende praktijk voor ontspanning en geen vervanging voor medische zorg.'],
            ],
        ],
        'en' => [
            'about_title' => 'About HATA',
            'about' => 'HATA Studio is a quiet space for women to slow down, breathe, and reconnect with themselves.',
            'faq_title' => 'Frequently asked questions',
            'questions' => [
                ['What can I expect?', 'A calm session with space for a short conversation, relaxation, and a gentle close at your own pace.'],
                ['Do I need experience?', 'No. You do not need to prepare anything and are welcome as you are.'],
                ['Is this medical care?', 'No. This is a complementary practice for relaxation and does not replace medical care.'],
            ],
        ],
        'uk' => [
            'about_title' => 'Про HATA',
            'about' => 'HATA Studio — це тихий простір для жінок, де можна сповільнитися, видихнути й відновити зв’язок із собою.',
            'faq_title' => 'Часті запитання',
            'questions' => [
                ['Чого очікувати?', 'Спокійна сесія з короткою розмовою, розслабленням і м’яким завершенням у твоєму темпі.'],
                ['Чи потрібен досвід?', 'Ні. Нічого готувати не потрібно — приходь такою, якою ти є.'],
                ['Це медична допомога?', 'Ні. Це комплементарна практика для розслаблення, яка не замінює медичну допомогу.'],
            ],
        ],
    ];
    $text = $copy[$language];
    $content = (string) get_post_field('post_content', $post_id);

    if (strpos($content, 'id="about"') === false) {
        $content .= '<!-- wp:group {"anchor":"about","className":"hata-home-section hata-home-about","layout":{"type":"constrained"}} -->'
            . '<div id="about" class="wp-block-group hata-home-section hata-home-about">'
            . '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html($text['about_title']) . '</h2><!-- /wp:heading -->'
            . '<!-- wp:paragraph --><p>' . esc_html($text['about']) . '</p><!-- /wp:paragraph -->'
            . '</div><!-- /wp:group -->';
    }

    if (strpos($content, 'id="faq"') === false) {
        $content .= '<!-- wp:group {"anchor":"faq","className":"hata-home-section hata-home-faq","layout":{"type":"constrained"}} -->'
            . '<div id="faq" class="wp-block-group hata-home-section hata-home-faq">'
            . '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html($text['faq_title']) . '</h2><!-- /wp:heading -->';

        foreach ($text['questions'] as [$question, $answer]) {
            $content .= '<!-- wp:details --><details class="wp-block-details"><summary>'
                . esc_html($question) . '</summary><!-- wp:paragraph --><p>'
                . esc_html($answer) . '</p><!-- /wp:paragraph --></details><!-- /wp:details -->';
        }

        $content .= '</div><!-- /wp:group -->';
    }

    wp_update_post(['ID' => $post_id, 'post_content' => $content]);
}

/** Wrap the existing Home introduction in the single animated hero group. */
function hata_a_ensure_home_hero(int $post_id): void
{
    $content = (string) get_post_field('post_content', $post_id);
    $blocks = parse_blocks($content);
    $split = count($blocks);

    foreach ($blocks as $index => $block) {
        $classes = (string) ($block['attrs']['className'] ?? '');
        if (in_array('hata-hero', preg_split('/\s+/', $classes), true)) {
            return;
        }
        if (($block['attrs']['anchor'] ?? '') === 'about') {
            $split = $index;
            break;
        }
    }

    $hero_blocks = array_slice($blocks, 0, $split);
    if (!$hero_blocks) {
        return;
    }

    $wrapper = parse_blocks(
        '<!-- wp:group {"align":"full","className":"hata-hero","layout":{"type":"constrained"}} -->'
        . '<div class="wp-block-group alignfull hata-hero"></div><!-- /wp:group -->'
    )[0];
    $wrapper['innerBlocks'] = $hero_blocks;
    $wrapper['innerContent'] = array_merge(
        ['<div class="wp-block-group alignfull hata-hero">'],
        array_fill(0, count($hero_blocks), null),
        ['</div>']
    );
    $wrapped = serialize_blocks(
        array_merge([$wrapper], array_slice($blocks, $split))
    );

    wp_update_post(['ID' => $post_id, 'post_content' => $wrapped]);
}

/** Create or update one language-specific Navigation-block menu. */
function hata_a_upsert_navigation(
    string $name,
    string $language,
    string $area,
    array $blocks,
    bool $force = false
): int {
    $posts = get_posts([
        'post_type' => 'wp_navigation',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'meta_query' => [
            ['key' => '_hata_language', 'value' => $language],
            ['key' => '_hata_area', 'value' => $area],
        ],
    ]);
    $post_id = 0;

    foreach ($posts as $post) {
        if ($post->post_title === $name) {
            $post_id = (int) $post->ID;
            break;
        }
    }

    $generated_content = serialize_blocks($blocks);
    $postarr = [
        'post_type' => 'wp_navigation',
        'post_status' => 'publish',
        'post_title' => $name,
        'post_content' => $generated_content,
    ];

    if ($post_id) {
        $current_content = (string) get_post_field('post_content', $post_id);
        $generated_hash = hash('sha256', $generated_content);
        $current_hash = hash('sha256', $current_content);
        $stored_hash = (string) get_post_meta(
            $post_id,
            '_hata_bootstrap_content_hash',
            true
        );

        if (!$force && $current_hash !== $generated_hash) {
            if ($stored_hash === '' || !hash_equals($stored_hash, $current_hash)) {
                WP_CLI::warning(
                    "Preserved edited navigation {$name}; set "
                    . 'HATA_BOOTSTRAP_FORCE_NAVIGATION=1 to replace it.'
                );
                return $post_id;
            }
        }

        $result = wp_update_post(['ID' => $post_id] + $postarr, true);
    } else {
        $result = wp_insert_post($postarr, true);
    }

    if (is_wp_error($result)) {
        WP_CLI::error($result);
    }

    $post_id = (int) $result;
    update_post_meta($post_id, '_hata_language', $language);
    update_post_meta($post_id, '_hata_area', $area);
    update_post_meta(
        $post_id,
        '_hata_bootstrap_content_hash',
        hash('sha256', $generated_content)
    );
    return $post_id;
}

function hata_a_navigation_link(string $label, string $url, string $class = ''): array
{
    $attributes = [
        'label' => $label,
        'type' => 'custom',
        'url' => $url,
        'kind' => 'custom',
        'isTopLevelLink' => true,
    ];
    if ($class !== '') {
        $attributes['className'] = $class;
    }

    return [
        'blockName' => 'core/navigation-link',
        'attrs' => $attributes,
        'innerBlocks' => [],
        'innerHTML' => '',
        'innerContent' => [],
    ];
}

/** Build the header and footer wp_navigation posts and persist their IDs. */
function hata_a_build_navigation(
    array $ids,
    array $languages,
    bool $force = false
): void
{
    $labels = [
        'nl' => ['about' => 'Over HATA', 'sessions' => 'Sessies', 'reiki' => 'Reiki', 'aromatouch' => 'AromaTouch', 'breath' => 'Ademhaling en mindfulness', 'faq' => 'Veelgestelde vragen', 'contact' => 'Contact', 'book' => 'Boek', 'privacy' => 'Privacybeleid', 'cookies' => 'Cookiemelding'],
        'en' => ['about' => 'About', 'sessions' => 'Sessions', 'reiki' => 'Reiki', 'aromatouch' => 'AromaTouch', 'breath' => 'Breath and mindfulness', 'faq' => 'FAQ', 'contact' => 'Contact', 'book' => 'Book', 'privacy' => 'Privacy policy', 'cookies' => 'Cookie notice'],
        'uk' => ['about' => 'Про HATA', 'sessions' => 'Сеанси', 'reiki' => 'Рейкі', 'aromatouch' => 'AromaTouch', 'breath' => 'Дихання й усвідомленість', 'faq' => 'Часті запитання', 'contact' => 'Контакти', 'book' => 'Записатися', 'privacy' => 'Політика конфіденційності', 'cookies' => 'Повідомлення про cookies'],
    ];
    $map = ['header' => [], 'footer' => []];

    foreach ($languages as $language) {
        $text = $labels[$language];
        $home = trailingslashit(hata_a_language_home_url($language));
        $submenu = [
            'blockName' => 'core/navigation-submenu',
            'attrs' => [
                'label' => $text['sessions'],
                'type' => 'custom',
                'url' => '#',
                'kind' => 'custom',
                'isTopLevelItem' => true,
            ],
            'innerBlocks' => [
                hata_a_navigation_link($text['reiki'], get_permalink($ids['reiki'][$language])),
                hata_a_navigation_link($text['aromatouch'], get_permalink($ids['aromatouch'][$language])),
                hata_a_navigation_link($text['breath'], get_permalink($ids['breath'][$language])),
            ],
            'innerHTML' => '',
            'innerContent' => [null, null, null],
        ];
        $header = [
            hata_a_navigation_link($text['about'], $home . '#about'),
            $submenu,
            hata_a_navigation_link($text['faq'], $home . '#faq'),
            hata_a_navigation_link($text['contact'], get_permalink($ids['contact'][$language])),
            hata_a_navigation_link($text['book'], get_permalink($ids['book'][$language]), 'hata-nav-book'),
        ];
        $footer = [
            hata_a_navigation_link($text['privacy'], get_permalink($ids['privacy'][$language])),
            hata_a_navigation_link($text['cookies'], get_permalink($ids['cookies'][$language])),
        ];

        $map['header'][$language] = hata_a_upsert_navigation(
            'HATA Header ' . strtoupper($language),
            $language,
            'header',
            $header,
            $force
        );
        $map['footer'][$language] = hata_a_upsert_navigation(
            'HATA Footer ' . strtoupper($language),
            $language,
            'footer',
            $footer,
            $force
        );
    }

    update_option('hata_navigation_posts', $map);
}

/** Register the theme logo once and let the Core Site Logo block render it. */
function hata_a_install_logo(): void
{
    $attachments = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'meta_key' => '_hata_asset_key',
        'meta_value' => 'header-logo',
    ]);
    $attachment_id = $attachments ? (int) $attachments[0]->ID : 0;

    if (!$attachment_id) {
        $source = WP_CONTENT_DIR
            . '/themes/studio-hata/assets/images/hata-logo-horizontal.png';
        if (!is_file($source)) {
            WP_CLI::error('Missing HATA header logo: ' . $source);
        }

        $upload = wp_upload_bits(
            'hata-logo-horizontal.png',
            null,
            (string) file_get_contents($source)
        );
        if (!empty($upload['error'])) {
            WP_CLI::error($upload['error']);
        }

        $attachment_id = (int) wp_insert_attachment([
            'post_mime_type' => 'image/png',
            'post_title' => 'HATA Studio logo',
            'post_status' => 'inherit',
        ], $upload['file']);
        require_once ABSPATH . 'wp-admin/includes/image.php';
        wp_update_attachment_metadata(
            $attachment_id,
            wp_generate_attachment_metadata($attachment_id, $upload['file'])
        );
        update_post_meta($attachment_id, '_hata_asset_key', 'header-logo');
    }

    update_post_meta($attachment_id, '_wp_attachment_image_alt', 'HATA Studio');
    set_theme_mod('custom_logo', $attachment_id);
}

$pages = hata_a_load_json($root . '/pages.json');
$content = [];

foreach ($languages as $language) {
    $content[$language] = hata_a_load_json($root . '/content/' . $language . '.json');
}

$ids = [];
$created = [];

foreach ($pages as $key => $page) {
    foreach ($languages as $language) {
        if (!isset($content[$language][$key])) {
            WP_CLI::error("Missing content for {$key}/{$language}.");
        }

        $identity = "hata-a:{$key}:{$language}";
        $managed = hata_a_find_managed_page($identity);
        $slug = $page['slugs'][$language] ?: 'home';
        $title = $content[$language][$key]['title'];

        if ($managed) {
            $id = $managed->ID;
            $created[$key][$language] = false;
        } else {
            $id = wp_insert_post(
                [
                    'post_type' => 'page',
                    'post_status' => $status,
                    'post_title' => $title,
                    'post_name' => $slug . '-bootstrap-' . $language,
                    'post_content' => '',
                ],
                true
            );

            if (is_wp_error($id)) {
                WP_CLI::error($id);
            }

            pll_set_post_language($id, $language);
            update_post_meta($id, '_hata_bootstrap_id', $identity);
            $created[$key][$language] = true;
        }

        $ids[$key][$language] = (int) $id;
    }

    pll_save_post_translations($ids[$key]);

    // Polylang must know the translation group before duplicate translated
    // slugs are assigned, otherwise WordPress appends numeric suffixes.
    foreach ($languages as $language) {
        $slug = $page['slugs'][$language] ?: 'home';
        $result = wp_update_post(
            [
                'ID' => $ids[$key][$language],
                'post_name' => $slug,
            ],
            true
        );

        if (is_wp_error($result)) {
            WP_CLI::error($result);
        }
    }
}

foreach ($pages as $key => $_page) {
    foreach ($languages as $language) {
        $entry = $content[$language][$key];

        if (!$created[$key][$language] && !$force) {
            $current_content = (string) get_post_field(
                'post_content',
                $ids[$key][$language]
            );
            $updated_content = hata_a_refresh_button_links(
                $current_content,
                $entry['blocks'],
                $language,
                $ids
            );

            if ($updated_content !== $current_content) {
                wp_update_post(
                    [
                        'ID' => $ids[$key][$language],
                        'post_content' => $updated_content,
                    ]
                );
            }

            WP_CLI::log("Preserved existing {$key}/{$language}.");
            continue;
        }

        $result = wp_update_post(
            [
                'ID' => $ids[$key][$language],
                'post_title' => $entry['title'],
                'post_status' => $status,
                'post_content' => hata_a_render_blocks(
                    $entry['blocks'],
                    $language,
                    $ids
                ),
            ],
            true
        );

        if (is_wp_error($result)) {
            WP_CLI::error($result);
        }
    }
}

foreach ($languages as $language) {
    hata_a_ensure_home_hero($ids['home'][$language]);
    hata_a_ensure_home_sections($ids['home'][$language], $language);
}

$placeholder_alt = [
    'nl' => [
        'still-life' => 'Een keramische kop en gevouwen linnen in warm raamlicht.',
        'session' => 'Een rustige individuele sessie in een zacht verlichte studio.',
        'studio' => 'Een rustige behandelruimte met zachtgroene muren en natuurlijk licht.',
    ],
    'en' => [
        'still-life' => 'A ceramic cup and folded linen in warm window light.',
        'session' => 'A quiet individual session in a softly lit studio.',
        'studio' => 'A calm treatment room with soft sage walls and natural light.',
    ],
    'uk' => [
        'still-life' => 'Керамічна чашка та складений льон у теплому світлі з вікна.',
        'session' => 'Спокійна індивідуальна сесія в м’яко освітленій студії.',
        'studio' => 'Спокійна кімната для сесій із м’якими шавлієвими стінами та природним світлом.',
    ],
];

foreach ($languages as $language) {
    $localized_alt = $placeholder_alt[$language];
    hata_a_ensure_placeholder_image(
        $ids['home'][$language],
        'still-life',
        $localized_alt['still-life'],
        'hata-placeholder-home-hero',
        'hata-hero'
    );
    hata_a_move_group_child_to_start(
        $ids['home'][$language],
        'hata-hero',
        'hata-placeholder-home-hero'
    );
    hata_a_ensure_placeholder_image(
        $ids['home'][$language],
        'studio',
        $localized_alt['studio'],
        'hata-placeholder-home-about',
        'hata-home-about'
    );

    foreach (
        [
            'reiki' => 'session',
            'aromatouch' => 'session',
            'breath' => 'studio',
            'book' => 'still-life',
            'contact' => 'studio',
        ] as $page_key => $image_key
    ) {
        hata_a_ensure_placeholder_image(
            $ids[$page_key][$language],
            $image_key,
            $localized_alt[$image_key],
            'hata-placeholder-' . $page_key
        );
    }
}

$polylang = get_option('polylang', []);
$polylang['force_lang'] = 1;
$polylang['hide_default'] = true;
$polylang['rewrite'] = true;
$polylang['redirect_lang'] = true;
$polylang['browser'] = false;
$polylang['media_support'] = false;
update_option('polylang', $polylang);
// Polylang caches each language's home URL in its language objects.
// Clear that cache after changing URL settings so the next request uses roots.
if (function_exists('PLL') && isset(PLL()->model)) {
    PLL()->model->clean_languages_cache();
}
update_option('blogname', 'HATA Studio');
hata_a_build_navigation($ids, $languages, $force_navigation);
hata_a_install_logo();

if ($status === 'publish') {
    update_option('show_on_front', 'page');
    update_option('page_on_front', $ids['home']['nl']);
    update_option('wp_page_for_privacy_policy', $ids['privacy']['nl']);
}

WP_CLI::success(
    sprintf(
        'HATA A bootstrap complete: %d pages x %d languages (%s).',
        count($pages),
        count($languages),
        $status
    )
);
