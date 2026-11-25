<?php
/**
 * "Riviera" layout: front-end in French or Arabic (right-to-left), light or dark display.
 * Loaded by functions.php. The back office stays the one of the base theme, plus optional
 * Arabic fields on each property and Arabic texts in the agency settings.
 */

defined('ABSPATH') || exit;

/* ---------------- Language ---------------- */

/** Current visitor language: ?lang=ar|fr, then the remembered choice (cookie), then French. */
function bahia_lang(): string
{
    static $lang = null;
    if ($lang === null) {
        $q = isset($_GET['lang']) ? sanitize_key(wp_unslash($_GET['lang'])) : '';
        $c = isset($_COOKIE['bahia_lang']) ? sanitize_key(wp_unslash($_COOKIE['bahia_lang'])) : '';
        $lang = in_array($q, ['fr', 'ar'], true) ? $q : (in_array($c, ['fr', 'ar'], true) ? $c : 'fr');
        if (is_admin()) {
            $lang = 'fr';
        }
    }
    return $lang;
}

function bahia_is_ar(): bool
{
    return bahia_lang() === 'ar';
}

/** Remember the choice made with ?lang= for the next pages. */
add_action('init', function () {
    if (is_admin() || !isset($_GET['lang']) || headers_sent()) {
        return;
    }
    setcookie('bahia_lang', bahia_lang(), ['expires' => time() + YEAR_IN_SECONDS, 'path' => COOKIEPATH ?: '/', 'samesite' => 'Lax']);
});

/**
 * Translate a French interface string. French is the key; Arabic comes from inc/lang-ar.php.
 * Unknown strings (e.g. a commune typed by the owner) are returned unchanged.
 */
function bahia_t(string $fr): string
{
    static $ar = null;
    if (!bahia_is_ar()) {
        return $fr;
    }
    if ($ar === null) {
        $ar = (array) require __DIR__ . '/lang-ar.php';
    }
    return $ar[$fr] ?? $fr;
}

/** Translated label lists (values stay French: they are stored in the database). */
function bahia_t_list(array $labels): array
{
    return array_map('bahia_t', $labels);
}

/** URL of the current page in the other language. */
function bahia_lang_switch_url(): string
{
    return add_query_arg('lang', bahia_is_ar() ? 'fr' : 'ar', remove_query_arg(['lang', 'envoye', 'erreur', 'nom']));
}

add_filter('language_attributes', function ($output) {
    if (is_admin()) {
        return $output;
    }
    return bahia_is_ar() ? 'lang="ar" dir="rtl"' : 'lang="fr-DZ" dir="ltr"';
});

add_filter('body_class', function ($classes) {
    $classes[] = 'layout-riviera';
    $classes[] = 'lang-' . bahia_lang();
    return $classes;
});

/* ---------------- Assets ---------------- */

add_filter('bahia_fonts_url', fn() => 'https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap');
add_filter('bahia_styles', fn() => ['riviera']);

add_action('wp_enqueue_scripts', function () {
    $js = '/assets/js/riviera.js';
    wp_enqueue_script('bahia-riviera', BAHIA_URI . $js, ['bahia-theme'], (string) @filemtime(BAHIA_DIR . $js), ['strategy' => 'defer', 'in_footer' => true]);
});

/** Light/dark choice applied before the page is painted (no flash), plus translated JS messages. */
add_action('wp_head', function () {
    echo "<script>(function(){var t;try{t=localStorage.getItem('bahia_theme')}catch(e){}if(!t){var m=document.cookie.match(/(?:^|; )bahia_theme=(\\w+)/);t=m&&m[1]}if(t==='sombre')document.documentElement.setAttribute('data-theme','sombre')})()</script>\n";
    echo '<meta name="color-scheme" content="light dark">' . "\n";
    if (bahia_is_ar()) {
        $keys = [
            'menuOpen' => 'Ouvrir le menu', 'menuClose' => 'Fermer le menu',
            'favAdd' => 'Ajouter aux favoris', 'favRemove' => 'Retirer des favoris',
            'errName' => 'Veuillez indiquer votre nom complet.',
            'errPhone' => 'Numéro invalide. Exemple : 0550 12 34 56 ou +213 550 12 34 56.',
            'errEmail' => 'Veuillez saisir une adresse email valide.',
            'errSubject' => 'Veuillez choisir un sujet.',
            'errMessage' => 'Votre message doit contenir au moins 10 caractères.',
            'errConsent' => 'Votre accord est nécessaire pour être recontacté(e).',
            'sending' => 'Envoi en cours…',
        ];
        echo '<script>window.BAHIA_I18N=' . wp_json_encode(array_map('bahia_t', $keys), JSON_UNESCAPED_UNICODE) . "</script>\n";
    }
}, 2);

/* ---------------- Illustrations shipped with the layout ---------------- */

add_filter('bahia_illustration_src', function ($src, $scene) {
    $map = [
        'villa-pool' => 'piscine', 'villa' => 'villa', 'living' => 'sejour', 'kitchen' => 'sejour',
        'bedroom' => 'chambre', 'bathroom' => 'chambre', 'building' => 'promenade', 'land' => 'terrain',
        'shop' => 'boutique', 'office' => 'boutique', 'promenade' => 'promenade',
    ];
    return BAHIA_URI . '/assets/img/riviera-' . ($map[$scene] ?? 'sejour') . '.svg';
}, 10, 2);

/* ---------------- Arabic fields on each property ---------------- */

add_filter('bahia_property_fields', function (array $fields) {
    $help = 'Facultatif. Laissé vide, le texte français s’affiche sur la version arabe du site.';
    $fields[] = ['key' => 'title_ar', 'label' => 'Titre en arabe', 'type' => 'text', 'group' => 'arabe', 'dir' => 'rtl', 'help' => $help];
    $fields[] = ['key' => 'excerpt_ar', 'label' => 'Résumé en arabe', 'type' => 'textarea', 'group' => 'arabe', 'dir' => 'rtl', 'rows' => 2];
    $fields[] = ['key' => 'content_ar', 'label' => 'Description en arabe', 'type' => 'textarea', 'group' => 'arabe', 'dir' => 'rtl', 'rows' => 7, 'help' => 'Laissez une ligne vide entre deux paragraphes.'];
    return $fields;
});

add_filter('bahia_field_groups', function (array $groups) {
    $groups['arabe'] = 'Version arabe du site';
    return $groups;
});

/** On the Arabic site, use the Arabic texts when they are filled in. */
add_filter('bahia_property', function (array $p, $post) {
    $p['title_lang'] = $p['text_lang'] = 'fr';
    if (!bahia_is_ar()) {
        return $p;
    }
    $title = trim((string) bahia_meta($p['id'], 'title_ar'));
    $excerpt = trim((string) bahia_meta($p['id'], 'excerpt_ar'));
    if ($title !== '') {
        $p['title'] = $title;
        $p['title_lang'] = 'ar';
    }
    if ($excerpt !== '') {
        $p['excerpt'] = $excerpt;
        $p['text_lang'] = 'ar';
    }
    foreach ($p['images'] as $i => $img) {
        if (!empty($img['scene'])) {
            $p['images'][$i]['alt'] = $p['title'] . ' — ' . bahia_t('visuel d’illustration') . ' ' . ($i + 1);
        }
    }
    return $p;
}, 10, 2);

/** Description paragraphs in the current language (Arabic field, else the WordPress content). */
function bahia_property_paragraphs(array $p): array
{
    if (bahia_is_ar()) {
        $ar = trim((string) bahia_meta($p['id'], 'content_ar'));
        if ($ar !== '') {
            return ['lang' => 'ar', 'html' => wpautop(esc_html($ar))];
        }
    }
    $post = get_post($p['id']);
    return ['lang' => 'fr', 'html' => apply_filters('the_content', $post->post_content)];
}

/** lang/dir attributes for a French text shown on the Arabic site. */
function bahia_lang_attr(string $text_lang): string
{
    return bahia_is_ar() && $text_lang === 'fr' ? ' lang="fr" dir="ltr"' : '';
}

/* ---------------- Arabic texts in the agency settings ---------------- */

add_filter('bahia_settings_defaults', function (array $d) {
    return array_merge($d, [
        'description_ar' => 'وكالة عقارية في وهران: بيع وشراء وكراء الشقق والفيلات والأراضي والمحلات.',
        'hero_title_ar' => 'المتوسط، بعنوان يليق بكم.',
        'hero_text_ar' => 'شقق مطلة على الخليج، وفيلات في مرتفعات كاناستيل: تشكيلة قصيرة يزورها مستشارونا ويتحققون منها قبل عرضها عليكم.',
        'about_title_ar' => 'وكالة وهرانية تهتم بكل التفاصيل.',
        'about_text_ar' => 'في باهية للعقارات، يرافقكم مستشار واحد من أول زيارة إلى تسليم المفاتيح. نزور كل عقار ونتحقق من وثائقه قبل أن نعرضه عليكم.',
        'address_ar' => 'شارع الواجهة البحرية (عنوان تجريبي)',
        'city_ar' => 'الواجهة البحرية، وهران',
        'stat1_label_ar' => 'سنوات في وهران',
        'stat2_label_ar' => 'زبون رافقناه',
        'stat3_label_ar' => 'حيًّا نغطيه',
    ]);
});

add_filter('bahia_settings_schema', function (array $schema) {
    $schema['arabe'] = ['Textes de la version arabe', [
        'hero_title_ar' => ['Titre principal (arabe)', 'text'],
        'hero_text_ar' => ['Texte d’introduction (arabe)', 'textarea'],
        'about_title_ar' => ['Titre « L’agence » (arabe)', 'text'],
        'about_text_ar' => ['Texte « L’agence » (arabe)', 'textarea'],
        'description_ar' => ['Présentation courte, pied de page (arabe)', 'textarea'],
        'address_ar' => ['Adresse (arabe)', 'text'],
        'city_ar' => ['Commune, ville (arabe)', 'text'],
        'stat1_label_ar' => ['Libellé chiffre clé 1 (arabe)', 'text'],
        'stat2_label_ar' => ['Libellé chiffre clé 2 (arabe)', 'text'],
        'stat3_label_ar' => ['Libellé chiffre clé 3 (arabe)', 'text', 'Laissé vide, le texte français s’affiche.'],
    ]];
    return $schema;
});

/** A setting in the current language (Arabic version when filled in). */
function bahia_s(string $key): string
{
    if (bahia_is_ar()) {
        $ar = trim((string) bahia_setting($key . '_ar'));
        if ($ar !== '') {
            return $ar;
        }
    }
    return (string) bahia_setting($key);
}

/* ---------------- Formatting ---------------- */

/** Price with the currency of the current language. Arabic uses no-break spaces so digit groups stay in order. */
function bahia_price(float $value): string
{
    if (bahia_is_ar()) {
        return number_format($value, 0, ',', "\u{00A0}") . "\u{00A0}د.ج";
    }
    return bahia_format_price($value);
}

function bahia_price_short(float $value): string
{
    if (bahia_is_ar()) {
        return $value >= 1000000 ? number_format($value / 1000000, 0, ',', "\u{00A0}") . "\u{00A0}مليون د.ج" : bahia_price($value);
    }
    return bahia_format_price_short($value);
}

function bahia_suffix(array $p): string
{
    return $p['transaction'] === 'location' ? ' ' . bahia_t('/ mois') : '';
}

function bahia_centimes(float $value): string
{
    if (!bahia_is_ar()) {
        return bahia_format_centimes($value);
    }
    $centimes = $value * 100;
    if ($centimes >= 1e9) {
        return '≈ ' . rtrim(rtrim(number_format($centimes / 1e9, 2, ',', ''), '0'), ',') . ' مليار سنتيم';
    }
    return '≈ ' . number_format($centimes / 1e6, 0, ',', "\u{00A0}") . ' مليون سنتيم';
}

/** "Appartement F3", "Villa", … in the current language. */
function bahia_kind(array $p): string
{
    $type = bahia_t(bahia_type_labels()[$p['type']] ?? '');
    if ($p['type'] === 'appartement') {
        return trim($type . ' ' . $p['typology']);
    }
    return $p['typology'] && !bahia_is_ar() ? $p['typology'] : $type;
}

/** Short specs line: "128 m² · 4 pièces". */
function bahia_specs_line(array $p): string
{
    $parts = [];
    if ($p['surface']) {
        $parts[] = bahia_num($p['surface']) . ' ' . bahia_t('m²');
    }
    if ($p['rooms']) {
        $parts[] = $p['rooms'] . ' ' . bahia_t($p['rooms'] > 1 ? 'pièces' : 'pièce');
    }
    if ($p['type'] === 'terrain') {
        $parts[] = bahia_t('Constructible');
    }
    return implode(' · ', $parts);
}

function bahia_num($n): string
{
    $n = (float) $n;
    return number_format($n, floor($n) == $n ? 0 : 1, ',', bahia_is_ar() ? "\u{00A0}" : ' ');
}

/** "Canastel, Oran" with known place names translated. */
function bahia_place(array $p): string
{
    return trim(bahia_t($p['commune']) . (bahia_is_ar() ? '، ' : ', ') . bahia_t($p['wilaya']), ', ،');
}

/** Opening hours with translated days. */
function bahia_hours_i18n(): array
{
    return array_map(function ($h) {
        $time = bahia_is_ar() ? str_replace('h', ':', $h['time']) : $h['time'];
        return ['days' => bahia_t($h['days']), 'time' => bahia_t($time)];
    }, bahia_hours());
}

/** Navigation from the WordPress menu, labels translated when known. "Accueil" is the logo. */
function bahia_rv_nav(): array
{
    $items = array_values(array_filter(bahia_nav_items(), fn($i) => rtrim((string) wp_parse_url($i['url'], PHP_URL_PATH), '/') !== rtrim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/')));
    foreach ($items as &$item) {
        $item['label'] = bahia_t($item['label']);
    }
    return $items;
}

/** Communes with published properties, most listings first: [name, count, cover image]. */
function bahia_top_communes(int $limit = 5): array
{
    global $wpdb;
    $rows = $wpdb->get_results(
        "SELECT pm.meta_value AS commune, COUNT(*) AS n, MIN(p.ID) AS first_id FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = '_bahia_commune' AND p.post_type = 'property' AND p.post_status = 'publish' AND pm.meta_value <> ''
         GROUP BY pm.meta_value ORDER BY n DESC, commune ASC LIMIT " . (int) $limit
    );
    return array_map(fn($r) => ['name' => $r->commune, 'count' => (int) $r->n, 'image' => bahia_get_property((int) $r->first_id)['images'][0]], $rows ?: []);
}

/** All communes with published properties (filters). */
function bahia_used_communes(): array
{
    global $wpdb;
    $rows = $wpdb->get_col(
        "SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = '_bahia_commune' AND p.post_type = 'property' AND p.post_status = 'publish' AND pm.meta_value <> ''"
    );
    sort($rows);
    return $rows;
}

/** Marinière stripes (decorative). */
function bahia_stripes(string $class = ''): string
{
    return '<span class="rv-stripes ' . esc_attr($class) . '" aria-hidden="true"></span>';
}

function bahia_rv_logo(bool $light = false): string
{
    return '<a href="' . esc_url(home_url('/')) . '" class="rv-logo' . ($light ? ' rv-logo--light' : '') . '" aria-label="' . esc_attr(bahia_t('Bahia Immobilier') . ' — ' . bahia_t('Accueil')) . '">'
        . '<span class="rv-logo__name">BAHIA</span>'
        . '<span class="rv-logo__sub">' . esc_html(bahia_t('IMMOBILIER') . ' · ' . bahia_t('Oran')) . '</span></a>';
}
