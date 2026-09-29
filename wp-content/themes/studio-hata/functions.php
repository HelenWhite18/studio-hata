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

