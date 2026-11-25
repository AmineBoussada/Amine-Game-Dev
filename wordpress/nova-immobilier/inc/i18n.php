<?php
/**
 * French / Arabic site (right to left) and light / dark display, for every layout.
 * The visitor chooses with the ع / FR and moon buttons; both choices are remembered.
 * Arabic texts: optional fields on each property, a section in the agency settings, and inc/lang-ar.php for the interface.
 */

defined('ABSPATH') || exit;

/* ---------------- Language ---------------- */

/** Current visitor language: ?lang=ar|fr, then the remembered choice (cookie), then French. */
function nova_lang(): string
{
    static $lang = null;
    if ($lang === null) {
        $q = isset($_GET['lang']) ? sanitize_key(wp_unslash($_GET['lang'])) : '';
        $c = isset($_COOKIE['nova_lang']) ? sanitize_key(wp_unslash($_COOKIE['nova_lang'])) : '';
        $lang = in_array($q, ['fr', 'ar'], true) ? $q : (in_array($c, ['fr', 'ar'], true) ? $c : 'fr');
        if (is_admin()) {
            $lang = 'fr';
        }
    }
    return $lang;
}

function nova_is_ar(): bool
{
    return nova_lang() === 'ar';
}

/** Remember the choice made with ?lang= for the next pages. */
add_action('init', function () {
    if (is_admin() || !isset($_GET['lang']) || headers_sent()) {
        return;
    }
    setcookie('nova_lang', nova_lang(), ['expires' => time() + YEAR_IN_SECONDS, 'path' => COOKIEPATH ?: '/', 'samesite' => 'Lax']);
});

/**
 * Translate a French interface string. French is the key; Arabic comes from inc/lang-ar.php.
 * Unknown strings (e.g. a commune typed by the owner) are returned unchanged.
 */
function nova_t(string $fr): string
{
    static $ar = null;
    if (!nova_is_ar()) {
        return $fr;
    }
    if ($ar === null) {
        $ar = (array) require __DIR__ . '/lang-ar.php';
    }
    return isset($ar[$fr]) ? str_replace('%ville%', nova_city_ar(), $ar[$fr]) : $fr;
}

/** The agency's city in Arabic (default wilaya of the property form), used inside translated sentences. */
function nova_city_ar(): string
{
    static $city = null;
    if ($city === null) {
        $city = 'الجزائر';
        foreach (nova_property_fields() as $f) {
            if ($f['key'] === 'wilaya' && !empty($f['default'])) {
                $ar = (array) require __DIR__ . '/lang-ar.php';
                $city = $ar[$f['default']] ?? $f['default'];
            }
        }
    }
    return $city;
}

/** Translated label lists (values stay French: they are stored in the database). */
function nova_t_list(array $labels): array
{
    return array_map('nova_t', $labels);
}

/** URL of the current page in the other language. */
function nova_lang_switch_url(): string
{
    return add_query_arg('lang', nova_is_ar() ? 'fr' : 'ar', remove_query_arg(['lang', 'envoye', 'erreur', 'nom']));
}

add_filter('language_attributes', function ($output) {
    if (is_admin()) {
        return $output;
    }
    return nova_is_ar() ? 'lang="ar" dir="rtl"' : 'lang="fr-DZ" dir="ltr"';
});


add_filter('body_class', function ($classes) {
    $classes[] = 'lang-' . nova_lang();
    return $classes;
});

/* ---------------- Assets ---------------- */

/** Arabic font, loaded only on the Arabic site. */
add_action('wp_enqueue_scripts', function () {
    if (nova_is_ar()) {
        wp_enqueue_style('nova-font-ar', 'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap', [], null);
    }
});

/** Light/dark choice applied before the page is painted (no flash), plus translated JS messages. */
add_action('wp_head', function () {
    echo "<script>(function(){var t;try{t=localStorage.getItem('nova_theme')}catch(e){}if(!t){var m=document.cookie.match(/(?:^|; )nova_theme=(\\w+)/);t=m&&m[1]}if(t==='sombre')document.documentElement.setAttribute('data-theme','sombre')})()</script>\n";
    echo '<meta name="color-scheme" content="light dark">' . "\n";
    if (nova_is_ar()) {
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
        echo '<script>window.NOVA_I18N=' . wp_json_encode(array_map('nova_t', $keys), JSON_UNESCAPED_UNICODE) . "</script>\n";
    }
}, 2);

/* ---------------- Arabic fields on each property ---------------- */

add_filter('nova_property_fields', function (array $fields) {
    $help = 'Facultatif. Laissé vide, le texte français s’affiche sur la version arabe du site.';
    $fields[] = ['key' => 'title_ar', 'label' => 'Titre en arabe', 'type' => 'text', 'group' => 'arabe', 'dir' => 'rtl', 'help' => $help];
    $fields[] = ['key' => 'excerpt_ar', 'label' => 'Résumé en arabe', 'type' => 'textarea', 'group' => 'arabe', 'dir' => 'rtl', 'rows' => 2];
    $fields[] = ['key' => 'content_ar', 'label' => 'Description en arabe', 'type' => 'textarea', 'group' => 'arabe', 'dir' => 'rtl', 'rows' => 7, 'help' => 'Laissez une ligne vide entre deux paragraphes.'];
    return $fields;
});

add_filter('nova_field_groups', function (array $groups) {
    $groups['arabe'] = 'Version arabe du site';
    return $groups;
});

/** On the Arabic site, use the Arabic texts when they are filled in. */
add_filter('nova_property', function (array $p, $post) {
    $p['title_lang'] = $p['text_lang'] = 'fr';
    if (!nova_is_ar()) {
        return $p;
    }
    $title = trim((string) nova_meta($p['id'], 'title_ar'));
    $excerpt = trim((string) nova_meta($p['id'], 'excerpt_ar'));
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
            $p['images'][$i]['alt'] = $p['title'] . ' — ' . nova_t('visuel d’illustration') . ' ' . ($i + 1);
        }
    }
    return $p;
}, 10, 2);

/** Description paragraphs in the current language (Arabic field, else the WordPress content). */
function nova_property_paragraphs(array $p): array
{
    if (nova_is_ar()) {
        $ar = trim((string) nova_meta($p['id'], 'content_ar'));
        if ($ar !== '') {
            return ['lang' => 'ar', 'html' => wpautop(esc_html($ar))];
        }
    }
    $post = get_post($p['id']);
    return ['lang' => 'fr', 'html' => apply_filters('the_content', $post->post_content)];
}

/** lang/dir attributes for a French text shown on the Arabic site. */
function nova_lang_attr(string $text_lang): string
{
    return nova_is_ar() && $text_lang === 'fr' ? ' lang="fr" dir="ltr"' : '';
}

/* ---------------- Arabic texts in the agency settings ---------------- */

add_filter('nova_settings_defaults', function (array $d) {
    return array_merge($d, [
        'description_ar' => 'وكالة عقارية: بيع وشراء وكراء الشقق والفيلات والأراضي والمحلات في الجزائر.',
        'hero_title_ar' => 'العقار الذي يناسبكم، في المكان المناسب.',
        'hero_text_ar' => 'تشكيلة مختارة من العقارات، يزورها مستشارونا ويتحققون منها قبل عرضها عليكم.',
        'about_title_ar' => 'وكالة بحجم إنساني، تهتم بكل التفاصيل.',
        'about_text_ar' => 'نرافق زبائننا في كل مراحل مشروعهم العقاري، بأسلوب شخصي وشفاف ومهني.',
        'address_ar' => '',
        'city_ar' => '',
        'stat1_label_ar' => 'سنوات من الخبرة',
        'stat2_label_ar' => 'زبون رافقناه',
        'stat3_label_ar' => 'بلدية نغطيها',
    ]);
});

add_filter('nova_settings_schema', function (array $schema) {
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
function nova_s(string $key): string
{
    if (nova_is_ar()) {
        $ar = trim((string) nova_setting($key . '_ar'));
        if ($ar !== '') {
            return $ar;
        }
    }
    return (string) nova_setting($key);
}

/* ---------------- Formatting ---------------- */

/** Price with the currency of the current language. Arabic uses no-break spaces so digit groups stay in order. */
function nova_price(float $value): string
{
    if (nova_is_ar()) {
        return number_format($value, 0, ',', "\u{00A0}") . "\u{00A0}د.ج";
    }
    return nova_format_price($value);
}

function nova_price_short(float $value): string
{
    if (nova_is_ar()) {
        return $value >= 1000000 ? number_format($value / 1000000, 0, ',', "\u{00A0}") . "\u{00A0}مليون د.ج" : nova_price($value);
    }
    return nova_format_price_short($value);
}

function nova_suffix(array $p): string
{
    return $p['transaction'] === 'location' ? ' ' . nova_t('/ mois') : '';
}

function nova_centimes(float $value): string
{
    if (!nova_is_ar()) {
        return nova_format_centimes($value);
    }
    $centimes = $value * 100;
    if ($centimes >= 1e9) {
        return '≈ ' . rtrim(rtrim(number_format($centimes / 1e9, 2, ',', ''), '0'), ',') . ' مليار سنتيم';
    }
    return '≈ ' . number_format($centimes / 1e6, 0, ',', "\u{00A0}") . ' مليون سنتيم';
}

/** "Appartement F3", "Villa", … in the current language. */
function nova_kind(array $p): string
{
    $type = nova_t(nova_type_labels()[$p['type']] ?? '');
    if ($p['type'] === 'appartement') {
        return trim($type . ' ' . $p['typology']);
    }
    return $p['typology'] && !nova_is_ar() ? $p['typology'] : $type;
}

/** Short specs line: "128 m² · 4 pièces". */
function nova_specs_line(array $p): string
{
    $parts = [];
    if ($p['surface']) {
        $parts[] = nova_num($p['surface']) . ' ' . nova_t('m²');
    }
    if ($p['rooms']) {
        $parts[] = $p['rooms'] . ' ' . nova_t($p['rooms'] > 1 ? 'pièces' : 'pièce');
    }
    if ($p['type'] === 'terrain') {
        $parts[] = nova_t('Constructible');
    }
    return implode(' · ', $parts);
}

function nova_num($n): string
{
    $n = (float) $n;
    return number_format($n, floor($n) == $n ? 0 : 1, ',', nova_is_ar() ? "\u{00A0}" : ' ');
}

/** "Canastel, Oran" with known place names translated. */
function nova_place(array $p): string
{
    $parts = array_filter([nova_t((string) $p['commune']), nova_t((string) $p['wilaya'])], 'strlen');
    return implode(nova_is_ar() ? '، ' : ', ', $parts);
}

/** Opening hours with translated days. */
function nova_hours_i18n(): array
{
    return array_map(function ($h) {
        $time = nova_is_ar() ? str_replace('h', ':', $h['time']) : $h['time'];
        return ['days' => nova_t($h['days']), 'time' => nova_t($time)];
    }, nova_hours());
}

/** Navigation from the WordPress menu, labels translated when known. "Accueil" is the logo. */
