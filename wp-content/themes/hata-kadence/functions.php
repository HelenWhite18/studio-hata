<?php
/**
 * HATA Kadence child-theme setup.
 *
 * @package Hata
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/** Resolve the uploaded hero video without ever storing the media file in Git. */
function hata_hero_video_url(): string
{
    $url = defined('HATA_HERO_VIDEO_URL')
        ? HATA_HERO_VIDEO_URL
        : getenv('HATA_HERO_VIDEO_URL');

    return is_string($url) ? esc_url_raw($url) : '';
}

/** Return dimensions and URL for a child-theme image, or an empty array. */
function hata_theme_image_data(string $relative_path): array
{
    $path = get_stylesheet_directory() . $relative_path;
    if (!file_exists($path)) {
        return [];
    }

    $size = getimagesize($path);

    return [
        'url' => get_stylesheet_directory_uri() . $relative_path,
        'width' => is_array($size) ? (int) $size[0] : 0,
        'height' => is_array($size) ? (int) $size[1] : 0,
    ];
}

/** Local-only generated images can stand in until approved photography arrives. */
function hata_placeholder_image_data(string $key): array
{
    $files = [
        'still-life' => '/assets/img/placeholders/still-life.jpg',
        'session' => '/assets/img/placeholders/session.jpg',
        'studio' => '/assets/img/placeholders/studio.jpg',
    ];

    return isset($files[$key]) ? hata_theme_image_data($files[$key]) : [];
}

function hata_hero_poster_data(): array
{
    $approved = hata_theme_image_data('/assets/img/hero-poster.jpg');

    return $approved ?: hata_placeholder_image_data('still-life');
}

function hata_hero_poster_url(): string
{
    $poster = hata_hero_poster_data();

    return $poster['url'] ?? '';
}

/** Keep Kadence's design tokens owned by the child theme rather than the database. */
function hata_kadence_palette(): string
{
    $colors = [
        ['color' => '#5A6850', 'slug' => 'palette1', 'name' => 'Deep Sage'],
        ['color' => '#7D523C', 'slug' => 'palette2', 'name' => 'Deep Clay'],
        ['color' => '#5F564D', 'slug' => 'palette3', 'name' => 'Bark'],
        ['color' => '#5F564D', 'slug' => 'palette4', 'name' => 'Bark'],
        ['color' => '#B88D73', 'slug' => 'palette5', 'name' => 'Clay'],
        ['color' => '#C9B28C', 'slug' => 'palette6', 'name' => 'Wheat'],
        ['color' => '#DCCEB8', 'slug' => 'palette7', 'name' => 'Oat'],
        ['color' => '#E8DFD0', 'slug' => 'palette8', 'name' => 'Linen'],
        ['color' => '#F7F4EE', 'slug' => 'palette9', 'name' => 'Warm White'],
        ['color' => '#7D523C', 'slug' => 'palette10', 'name' => 'Deep Clay'],
        ['color' => '#5A6850', 'slug' => 'palette11', 'name' => 'Deep Sage'],
        ['color' => '#A8B29A', 'slug' => 'palette12', 'name' => 'Sage'],
        ['color' => '#7D523C', 'slug' => 'palette13', 'name' => 'Deep Clay'],
        ['color' => '#C9B28C', 'slug' => 'palette14', 'name' => 'Wheat'],
        ['color' => '#B88D73', 'slug' => 'palette15', 'name' => 'Clay'],
    ];

    return (string) wp_json_encode([
        'palette' => $colors,
        'second-palette' => $colors,
        'third-palette' => $colors,
        'active' => 'palette',
    ]);
}

add_filter('option_kadence_global_palette', 'hata_kadence_palette', 99);
add_filter('kadence_global_palette_defaults', 'hata_kadence_palette', 99);

add_filter('theme_mod_logo_layout', static function (): array {
    return [
        'include' => ['mobile' => 'logo', 'tablet' => 'logo', 'desktop' => 'logo'],
        'layout' => ['mobile' => 'standard', 'tablet' => 'standard', 'desktop' => 'standard'],
    ];
});

add_filter('theme_mod_logo_width', static function (): array {
    return [
        'size' => ['mobile' => 150, 'tablet' => 175, 'desktop' => 195],
        'unit' => ['mobile' => 'px', 'tablet' => 'px', 'desktop' => 'px'],
    ];
});

add_filter('theme_mod_base_font', static function (): array {
    return [
        'size' => ['desktop' => 17],
        'lineHeight' => ['desktop' => 1.75],
        'family' => 'Manrope',
        'google' => false,
        'weight' => '400',
        'variant' => 'regular',
        'color' => 'palette4',
    ];
});

add_filter('theme_mod_heading_font', static function (): array {
    return [
        'family' => 'Cormorant Garamond',
        'google' => false,
        'weight' => '500',
        'variant' => '500',
        'color' => 'palette3',
    ];
});

foreach (['site_background', 'content_background', 'header_wrap_background', 'header_main_background'] as $setting) {
    add_filter("theme_mod_{$setting}", static function (): array {
        return [
            'desktop' => ['color' => 'palette9'],
            'tablet' => ['color' => 'palette9'],
            'mobile' => ['color' => 'palette9'],
        ];
    });
}

add_filter('kadence_logo_url', static function (string $url): string {
    return function_exists('pll_home_url') ? pll_home_url() : $url;
});

/** Replace Kadence's minimal footer with the complete HATA footer. */
function hata_kadence_footer_markup(): void
{
    $regions = [
        'nl' => 'Nederland',
        'en' => 'Netherlands',
        'uk' => 'Нідерланди',
    ];
    $language = function_exists('hata_lang') ? hata_lang() : 'en';
    $region = function_exists('hata_config')
        ? hata_config('HATA_REGION', $regions[$language] ?? $regions['en'])
        : ($regions[$language] ?? $regions['en']);
    ?>
    <footer id="colophon" class="site-footer hata-site-footer">
        <div class="hata-footer-inner">
            <div class="hata-footer-identity">
                <p class="hata-footer-name">HATA Studio</p>
                <p class="hata-footer-region"><?php echo esc_html($region); ?></p>
                <?php
                if (function_exists('hata_render_contact_details')) {
                    echo wp_kses_post(hata_render_contact_details());
                }
                ?>
            </div>
            <div class="hata-footer-links">
                <?php
                wp_nav_menu([
                    'theme_location' => 'footer',
                    'container' => 'nav',
                    'container_class' => 'hata-footer-navigation',
                    'container_aria_label' => __('Footer navigation', 'hata-kadence'),
                    'fallback_cb' => false,
                    'depth' => 1,
                ]);
                if (function_exists('hata_render_disclaimer')) {
                    echo wp_kses_post(hata_render_disclaimer());
                }
                ?>
            </div>
        </div>
    </footer>
    <?php
}

add_action('after_setup_theme', static function (): void {
    remove_action('kadence_footer', 'Kadence\\footer_markup');
    add_action('kadence_footer', 'hata_kadence_footer_markup');
}, 20);

add_action('kadence_after_header', static function (): void {
    if (!function_exists('hata_render_language_switcher')) {
        return;
    }

    $switcher = hata_render_language_switcher();
    if ($switcher !== '') {
        $allowed_html = [
            'nav' => ['class' => true, 'aria-label' => true],
            'ul' => [],
            'li' => [],
            'a' => [
                'href' => true,
                'lang' => true,
                'hreflang' => true,
                'aria-current' => true,
            ],
        ];
        echo '<div class="hata-language-bar"><div class="hata-language-bar__inner">'
            . wp_kses($switcher, $allowed_html)
            . '</div></div>';
    }
});

add_action(
    'wp_enqueue_scripts',
    static function (): void {
        $directory = get_stylesheet_directory();
        $uri = get_stylesheet_directory_uri();
        $brand_path = $directory . '/assets/css/brand.css';

        wp_enqueue_style(
            'hata-brand',
            $uri . '/assets/css/brand.css',
            [],
            (string) filemtime($brand_path)
        );

        $owned_path = $directory . '/assets/css/owned.css';
        wp_enqueue_style(
            'hata-owned',
            $uri . '/assets/css/owned.css',
            ['hata-brand'],
            (string) filemtime($owned_path)
        );

        $save_data = strtolower((string) ($_SERVER['HTTP_SAVE_DATA'] ?? '')) === 'on';
        $video_url = $save_data ? '' : hata_hero_video_url();

        $poster_url = hata_hero_poster_url();

        if (is_front_page() && $video_url !== '' && $poster_url !== '') {
            $script_path = $directory . '/assets/js/hero-video.js';
            wp_enqueue_script(
                'hata-hero-video',
                $uri . '/assets/js/hero-video.js',
                [],
                (string) filemtime($script_path),
                true
            );
            wp_add_inline_script(
                'hata-hero-video',
                'window.hataHeroVideo=' . wp_json_encode([
                    'src' => $video_url,
                    'poster' => $poster_url,
                ]) . ';',
                'before'
            );
        }
    },
    99
);

add_filter(
    'body_class',
    static function (array $classes): array {
        $classes[] = 'hata-v-b';
        return $classes;
    }
);
