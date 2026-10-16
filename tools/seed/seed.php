<?php
/**
 * HATA B seeder. Run via WP-CLI.
 * Creates pages in NL/EN/UK, links translations, builds menus, sets front page + Polylang URL rules.
 */
if (!defined('ABSPATH')) exit;
if (!function_exists('pll_languages_list')) { WP_CLI::error('Polylang is not active.'); }

$postStatus = getenv('HATA_SEED_STATUS') === 'publish' ? 'publish' : 'draft';
$L = ['nl', 'en', 'uk'];
$have = pll_languages_list(['fields' => 'slug']);
$missing = array_diff($L, $have);
if ($missing) {
    WP_CLI::error('Add these languages in wp-admin > Languages first (NL default): ' . implode(', ', $missing) .
        "\n  Nederlands nl_NL | English en_GB | Українська uk");
}
if (pll_default_language() !== 'nl') { WP_CLI::warning('Default language is not NL. Set NL as default in Languages > Settings.'); }

$contentPath = __DIR__ . '/content.json';
$c = json_decode((string) file_get_contents($contentPath), true);
if (!is_array($c) || !isset($c['pages'])) {
    WP_CLI::error('Could not read valid seed content from ' . $contentPath);
}
$pages = $c['pages'];
update_option('hata_email', getenv('HATA_CONTACT_EMAIL') ?: '');
update_option('hata_region', getenv('HATA_REGION') ?: 'Netherlands');
$EMAIL = get_option('hata_email');
$BOOKING = function_exists('hata_config') ? hata_config('HATA_BOOKING_URL') : '';
if (!$BOOKING) WP_CLI::error('Set HATA_BOOKING_URL before running the HATA B seed.');

/* ---- Polylang URL rules: NL at root, /en/, /uk/, no browser redirect ---- */
$o = get_option('polylang');
$o['force_lang'] = 1; $o['hide_default'] = 1; $o['rewrite'] = 1; $o['browser'] = 0; $o['redirect_lang'] = 1;
update_option('polylang', $o);
PLL()->model->clean_languages_cache();

/* ---- Pass 1: create shells ---- */
$ids = []; // $ids[key][lang]
$wpPrivacy = (int) get_option('wp_page_for_privacy_policy');
if (
    $wpPrivacy
    && get_post_status($wpPrivacy) === 'draft'
    && get_post_field('post_name', $wpPrivacy) === 'privacy-policy'
    && !pll_get_post_language($wpPrivacy)
) {
    wp_update_post([
        'ID' => $wpPrivacy,
        'post_name' => 'privacy-policy-wordpress-draft',
    ]);
}

foreach ($pages as $key => $p) {
    foreach ($L as $lang) {
        $slug = $p['slug'][$lang] ?: 'home';
        $identity = "hata-b:$key:$lang";
        $existing = get_posts([
            'post_type' => 'page',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'meta_key' => '_hata_seed_id',
            'meta_value' => $identity,
            'suppress_filters' => false,
        ]);

        if (!$existing) {
            $candidates = get_posts([
                'post_type' => 'page',
                'name' => $slug,
                'posts_per_page' => -1,
                'post_status' => 'any',
                'suppress_filters' => true,
            ]);
            foreach ($candidates as $candidate) {
                if (pll_get_post_language($candidate->ID, 'slug') === $lang) {
                    $existing = [$candidate];
                    break;
                }
            }
        }

        if ($existing) {
            $id = $existing[0]->ID;
            update_post_meta($id, '_hata_seed_id', $identity);
        } else {
            $id = wp_insert_post(['post_type' => 'page', 'post_status' => $postStatus, 'post_title' => $p['title'][$lang],
                'post_name' => $slug . '-tmp-' . $lang, 'post_content' => '']);
            pll_set_post_language($id, $lang);
            update_post_meta($id, '_hata_seed_id', $identity);
        }
        $ids[$key][$lang] = $id;
    }
    pll_save_post_translations($ids[$key]);

    // Vacate old managed slugs before assigning the approved language slugs.
    foreach ($L as $lang) {
        wp_update_post([
            'ID' => $ids[$key][$lang],
            'post_name' => "hata-b-$key-$lang-{$ids[$key][$lang]}",
        ]);
    }

    foreach ($L as $lang) {
        $slug = $p['slug'][$lang] ?: 'home';
        wp_update_post(['ID' => $ids[$key][$lang], 'post_name' => $slug]);
        $real = get_post_field('post_name', $ids[$key][$lang]);
        if ($real !== $slug) WP_CLI::warning("Slug for $key/$lang became '$real' (wanted '$slug').");
    }
}

/* ---- Rendering ---- */
function h_e($s) { return esc_html($s); }
function h_link($to, $lang, $ids, $BOOKING) {
    if ($to === '{{BOOKING}}') return $BOOKING;
    if (isset($ids[$to][$lang])) return get_permalink($ids[$to][$lang]);
    return pll_home_url($lang);
}
function h_image_block($key, $alt = '', $class = 'hata-placeholder-image') {
    if (!function_exists('hata_placeholder_image_data')) return '';
    $image = hata_placeholder_image_data($key);
    if (!$image) return '';
    $attrs = ['sizeSlug' => 'large', 'linkDestination' => 'none', 'className' => $class];
    $dimensions = $image['width'] && $image['height']
        ? ' width="' . (int) $image['width'] . '" height="' . (int) $image['height'] . '"'
        : '';
    return '<!-- wp:image ' . wp_json_encode($attrs) . ' -->'
        . '<figure class="wp-block-image size-large ' . esc_attr($class) . '"><img src="' . esc_url($image['url']) . '" alt="' . esc_attr($alt) . '"'
        . $dimensions . ' loading="lazy" decoding="async"/></figure><!-- /wp:image -->';
}
function h_render(array $blocks, $lang, $ids, $pages, $BOOKING, $EMAIL, $depth = 0) {
    $out = '';
    foreach ($blocks as $b) {
        switch ($b['t']) {
            case 'hero':
                $labels = [
                    'nl' => ['pause' => 'Pauzeer achtergrondvideo', 'play' => 'Speel achtergrondvideo af'],
                    'en' => ['pause' => 'Pause background video', 'play' => 'Play background video'],
                    'uk' => ['pause' => 'Призупинити фонове відео', 'play' => 'Відтворити фонове відео'],
                ][$lang];
                $poster = function_exists('hata_hero_poster_data') ? hata_hero_poster_data() : [];
                $attrs = ['dimRatio' => 0, 'align' => 'full', 'className' => 'hata-hero hata-video-hero'];
                $poster_markup = '';
                if ($poster) {
                    $attrs['url'] = $poster['url'];
                    $poster_dimensions = $poster['width'] && $poster['height']
                        ? ' width="' . (int) $poster['width'] . '" height="' . (int) $poster['height'] . '"'
                        : '';
                    $poster_markup = '<img class="wp-block-cover__image-background hata-video-hero__poster" alt="" src="' . esc_url($poster['url']) . '"'
                      . $poster_dimensions . ' data-object-fit="cover" decoding="async" fetchpriority="high"/>';
                }
                $out .= '<!-- wp:cover ' . wp_json_encode($attrs) . ' -->'
                  . '<div class="wp-block-cover alignfull hata-hero hata-video-hero">'
                  . $poster_markup
                  . '<span aria-hidden="true" class="wp-block-cover__background hata-video-hero__overlay"></span>'
                  . '<div class="wp-block-cover__inner-container">'
                  . '<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">' . h_e($b['h']) . '</h1><!-- /wp:heading -->'
                  . '<!-- wp:paragraph --><p>' . h_e($b['x']) . '</p><!-- /wp:paragraph -->'
                  . h_render([['t' => 'btn', 'x' => $b['b'], 'to' => $b['to']]], $lang, $ids, $pages, $BOOKING, $EMAIL)
                  . '<!-- wp:html --><button class="hata-video-toggle" type="button" hidden aria-pressed="true" aria-label="' . esc_attr($labels['pause']) . '" data-pause-label="' . esc_attr($labels['pause']) . '" data-play-label="' . esc_attr($labels['play']) . '"><span class="hata-video-toggle__icon" aria-hidden="true">Ⅱ</span></button><!-- /wp:html -->'
                  . '</div>'
                  . '</div><!-- /wp:cover -->';
                break;
            case 'image':
                $out .= h_image_block($b['key'], $b['alt'] ?? '');
                break;
            case 'section':
                $cls = 'hata-section' . ($b['tone'] === 'linen' ? ' is-linen' : '');
                $id = $b['id'] ? ' id="' . esc_attr($b['id']) . '"' : '';
                $out .= '<!-- wp:group {"align":"full","className":"' . $cls . '","anchor":"' . esc_attr($b['id']) . '"} --><div' . $id . ' class="wp-block-group alignfull ' . $cls . '">'
                  . h_render($b['blocks'], $lang, $ids, $pages, $BOOKING, $EMAIL, $depth + 1) . '</div><!-- /wp:group -->';
                break;
            case 'include':
                $src = $pages[$b['page']];
                $inner = array_merge([['t' => 'h2', 'x' => $src['title'][$lang]]], $src['blocks'][$lang]);
                $out .= h_render([['t' => 'section', 'id' => $b['id'], 'tone' => $b['tone'], 'blocks' => $inner]], $lang, $ids, $pages, $BOOKING, $EMAIL, $depth + 1);
                break;
            case 'h2': $out .= '<!-- wp:heading --><h2 class="wp-block-heading">' . h_e($b['x']) . '</h2><!-- /wp:heading -->'; break;
            case 'h3': $out .= '<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">' . h_e($b['x']) . '</h3><!-- /wp:heading -->'; break;
            case 'p':
                $t = $b['x'];
                if (strpos($t, '{{EMAIL}}') !== false) {
                    if (!$EMAIL) break;
                    $out .= '<!-- wp:paragraph --><p>' . h_e(str_replace('{{EMAIL}}', '', $t)) . '<a href="mailto:' . esc_attr($EMAIL) . '">' . h_e($EMAIL) . '</a></p><!-- /wp:paragraph -->';
                } else $out .= '<!-- wp:paragraph --><p>' . h_e($t) . '</p><!-- /wp:paragraph -->';
                break;
            case 'quote': $out .= '<!-- wp:paragraph {"align":"center","className":"hata-quote"} --><p class="has-text-align-center hata-quote">' . h_e($b['x']) . '</p><!-- /wp:paragraph -->'; break;
            case 'note': $out .= '<!-- wp:paragraph {"className":"hata-note"} --><p class="hata-note">' . h_e($b['x']) . '</p><!-- /wp:paragraph -->'; break;
            case 'todo': $out .= '<!-- wp:paragraph {"className":"hata-draft"} --><p class="hata-draft">TODO: ' . h_e($b['x']) . '</p><!-- /wp:paragraph -->'; break;
            case 'btn':
                $url = h_link($b['to'], $lang, $ids, $BOOKING); $ext = !empty($b['ext']);
                $out .= '<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"' . ($depth || $b['t'] === 'btn' ? 'center' : 'left') . '"}} --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url($url) . '"'
                  . ($ext ? ' target="_blank" rel="noopener noreferrer"' : '') . '>' . h_e($b['x']) . '</a></div><!-- /wp:button --></div><!-- /wp:buttons -->';
                break;
            case 'cols':
                $out .= '<!-- wp:columns --><div class="wp-block-columns">';
                foreach ($b['items'] as $it) {
                    $image = h_image_block($it['image'] ?? '', '', 'hata-placeholder-image hata-service-card-image');
                    $out .= '<!-- wp:column --><div class="wp-block-column">' . $image . '<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">' . h_e($it['h']) . '</h3><!-- /wp:heading -->'
                      . '<!-- wp:paragraph --><p>' . h_e($it['x']) . '</p><!-- /wp:paragraph -->'
                      . '<!-- wp:paragraph --><p><a href="' . esc_url(h_link($it['to'], $lang, $ids, $BOOKING)) . '">' . h_e($it['more']) . '</a></p><!-- /wp:paragraph --></div><!-- /wp:column -->';
                }
                $out .= '</div><!-- /wp:columns -->';
                break;
            case 'faq':
                foreach ($b['items'] as $it) {
                    $out .= '<!-- wp:details --><details class="wp-block-details"><summary>' . h_e($it['q']) . '</summary><!-- wp:paragraph --><p>' . h_e($it['a']) . '</p><!-- /wp:paragraph --></details><!-- /wp:details -->';
                }
                break;
        }
    }
    return $out;
}

/* ---- Pass 2: content + Kadence layout meta + SEO meta ---- */
$homeDesc = [
    'nl' => 'Een zachte plek voor vrouwen om te vertragen en weer contact te maken met je hart. Reiki, AromaTouch en ademwerk.',
    'en' => 'A gentle space for women to slow down and reconnect with your heart. Reiki, AromaTouch and breathwork.',
    'uk' => 'М’який простір для жінок, щоб сповільнитися і відновити зв’язок зі своїм серцем. Рейкі, AromaTouch та дихальні практики.',
];
$homeTag = ['nl' => 'Kom terug bij jezelf', 'en' => 'Come back to yourself', 'uk' => 'Повернись до себе'];
foreach ($ids as $key => $byLang) {
    foreach ($byLang as $lang => $id) {
        $p = $pages[$key];
        $blocks = $p['blocks'][$lang];
        wp_update_post(['ID' => $id, 'post_content' => h_render($blocks, $lang, $ids, $pages, $BOOKING, $EMAIL)]);
        $isHome = $key === 'home';
        update_post_meta($id, '_kad_post_layout', $isHome ? 'fullwidth' : 'narrow');
        update_post_meta($id, '_kad_post_content_style', 'unboxed');
        update_post_meta($id, '_kad_post_title', $isHome ? 'hide' : 'show');
        update_post_meta($id, '_kad_post_vertical_padding', $isHome ? 'hide' : 'show');
        if ($isHome) { update_post_meta($id, '_genesis_title', 'HATA Studio · ' . $homeTag[$lang]); update_post_meta($id, '_genesis_description', $homeDesc[$lang]); }
    }
}

/* ---- Front page ---- */
if ($postStatus === 'publish') {
    update_option('show_on_front', 'page');
    update_option('page_on_front', $ids['home']['nl']);
    update_option('wp_page_for_privacy_policy', $ids['privacy']['nl']);
} else {
    WP_CLI::warning('Pages remain drafts. Set the static front page after review and publication.');
}

/* ---- Menus ---- */
$labels = function ($key, $lang) use ($pages) { return $pages[$key]['menu'][$lang] ?? $pages[$key]['title'][$lang]; };
$opts = get_option('polylang'); $theme = get_stylesheet();
foreach ($L as $lang) {
    $home = pll_home_url($lang);
    foreach (['primary' => 'Main', 'footer' => 'Footer'] as $loc => $name) {
        $mname = "$name-$lang";
        $m = wp_get_nav_menu_object($mname);
        if ($m) wp_delete_nav_menu($m->term_id);
        $mid = wp_create_nav_menu($mname);
        $add = function ($args) use ($mid) { return wp_update_nav_menu_item($mid, 0, $args + ['menu-item-status' => 'publish']); };
        $pg = function ($key, $parent = 0, $cls = '') use ($add, $ids, $lang, $labels) {
            if (!isset($ids[$key][$lang])) return 0;
            return $add(['menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $ids[$key][$lang],
                'menu-item-title' => $labels($key, $lang), 'menu-item-parent-id' => $parent, 'menu-item-classes' => $cls]);
        };
        $anchor = function ($frag, $title) use ($add, $home) { return $add(['menu-item-type' => 'custom', 'menu-item-url' => $home . '#' . $frag, 'menu-item-title' => $title]); };
        if ($loc === 'primary') {
            $pg('about'); $s = $pg('services'); $pg('reiki', $s); $pg('aromatouch', $s); $pg('breath', $s);
            $pg('faq'); $pg('contact'); $pg('book', 0, 'hata-menu-book');
        } else { $pg('privacy'); $pg('cookies'); }
        $opts['nav_menus'][$theme][$loc][$lang] = $mid;
        if ($loc === 'primary') $opts['nav_menus'][$theme]['mobile'][$lang] = $mid;
    }
}
update_option('polylang', $opts);
$nl_locs = get_theme_mod('nav_menu_locations', []);
foreach (['primary', 'mobile', 'footer'] as $loc) { $nl_locs[$loc] = $opts['nav_menus'][$theme][$loc]['nl'] ?? 0; }
set_theme_mod('nav_menu_locations', $nl_locs);
set_theme_mod('footer_html', '© {year} HATA Studio');

WP_CLI::success("Seeded HATA B as $postStatus: " . count($ids) . ' pages x 3 languages.');
