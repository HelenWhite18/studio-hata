<?php
/**
 * Studio Hata theme setup.
 *
 * @package StudioHata
 */

declare(strict_types=1);

add_action(
    'after_setup_theme',
    static function (): void {
        add_theme_support('editor-styles');
        add_editor_style('style.css');
    }
);

add_action(
    'wp_enqueue_scripts',
    static function (): void {
        $theme = wp_get_theme();

        wp_enqueue_style(
            'studio-hata',
            get_stylesheet_uri(),
            [],
            $theme->get('Version')
        );
    }
);

/** Return metadata for a local-only placeholder image when it exists. */
function hata_a_placeholder_image_data(string $key): array
{
    $files = [
        'still-life' => '/assets/images/placeholders/still-life.jpg',
        'session' => '/assets/images/placeholders/session.jpg',
        'studio' => '/assets/images/placeholders/studio.jpg',
    ];

    if (!isset($files[$key])) {
        return [];
    }

    $relative_path = $files[$key];
    $absolute_path = get_stylesheet_directory() . $relative_path;
    if (!is_file($absolute_path)) {
        return [];
    }

    $size = getimagesize($absolute_path);

    return [
        'url' => get_stylesheet_directory_uri() . $relative_path,
        'width' => is_array($size) ? (int) $size[0] : 0,
        'height' => is_array($size) ? (int) $size[1] : 0,
    ];
}
