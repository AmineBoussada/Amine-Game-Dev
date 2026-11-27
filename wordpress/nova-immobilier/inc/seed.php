<?php
/**
 * WP-CLI: `wp nova seed` — imports the demo properties, creates the pages and the main menu.
 * Safe to run twice: existing references are skipped.
 */

defined('ABSPATH') || exit;

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

WP_CLI::add_command('nova seed', function ($args, $assoc) {
    $data = json_decode((string) file_get_contents(__DIR__ . '/seed-data.json'), true);
    $created = 0;
    foreach ($data as $p) {
        $exists = get_posts(['post_type' => 'property', 'meta_key' => '_nova_reference', 'meta_value' => $p['reference'], 'post_status' => 'any', 'numberposts' => 1]);
        if ($exists) {
            continue;
        }
        $content = implode("\n\n", array_map(fn($para) => '<p>' . esc_html($para) . '</p>', $p['description']));
        $id = wp_insert_post([
            'post_type' => 'property',
            'post_status' => 'publish',
            'post_title' => $p['title'],
            'post_name' => $p['slug'],
            'post_content' => $content,
            'post_excerpt' => $p['excerpt'],
            'post_date' => $p['createdAt'] . ' 10:00:00',
        ], true);
        if (is_wp_error($id)) {
            WP_CLI::warning($id->get_error_message());
            continue;
        }
        $map = [
            'reference' => $p['reference'], 'price' => $p['price'], 'transaction' => $p['transaction'], 'type' => $p['type'],
            'typology' => $p['typology'], 'wilaya' => $p['wilaya'], 'commune' => $p['commune'], 'quartier' => $p['quartier'] ?? '',
            'surface' => $p['surface'], 'land_surface' => $p['landSurface'] ?? '', 'rooms' => $p['rooms'], 'bedrooms' => $p['bedrooms'],
            'bathrooms' => $p['bathrooms'], 'floor' => $p['floor'] ?? '', 'year_built' => $p['yearBuilt'] ?? '',
            'features' => $p['features'], 'featured' => $p['featured'] ? 1 : 0, 'status' => $p['status'], 'gallery' => '',
        ];
        $map = array_merge($map, $p['meta'] ?? []); // extra fields, e.g. Arabic texts
        foreach ($map as $k => $v) {
            update_post_meta($id, "_nova_{$k}", $v);
        }
        $created++;
    }
    WP_CLI::log("Biens créés : {$created}");

    $pages = [
        'accueil' => ['Accueil', ''],
        'a-propos' => ['À propos', ''],
        'contact' => ['Contact', ''],
        'favoris' => ['Mes favoris', ''],
        'politique-de-confidentialite' => ['Politique de confidentialité', nova_privacy_content()],
    ];
    $ids = [];
    foreach ($pages as $slug => [$title, $content]) {
        $page = get_page_by_path($slug);
        $ids[$slug] = $page ? $page->ID : wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_content' => $content]);
    }
    update_option('show_on_front', 'page');
    update_option('page_on_front', $ids['accueil']);
    update_option('wp_page_for_privacy_policy', $ids['politique-de-confidentialite']);

    if (!wp_get_nav_menu_object('Menu principal')) {
        $menu = wp_create_nav_menu('Menu principal');
        foreach (nova_default_nav() as $i => $item) {
            wp_update_nav_menu_item($menu, 0, ['menu-item-title' => $item['label'], 'menu-item-url' => $item['url'], 'menu-item-status' => 'publish', 'menu-item-type' => 'custom', 'menu-item-position' => $i + 1]);
        }
        $locations = get_theme_mod('nav_menu_locations', []);
        $locations['primary'] = $menu;
        set_theme_mod('nav_menu_locations', $locations);
    }
    flush_rewrite_rules();
    WP_CLI::success('Contenu de démonstration installé.');
});

function nova_privacy_content(): string
{
    return <<<HTML
<p>NOVA IMMOBILIER attache une grande importance à la protection de vos données personnelles, conformément à la loi n° 18-07 du 10 juin 2018 relative à la protection des personnes physiques dans le traitement des données à caractère personnel.</p>
<h2>1. Données collectées</h2>
<p>Lorsque vous utilisez nos formulaires, nous collectons uniquement les informations nécessaires au traitement de votre demande : nom, téléphone, adresse email, sujet et contenu de votre message, ainsi que la référence du bien concerné.</p>
<h2>2. Finalités</h2>
<p>Ces données sont utilisées exclusivement pour vous recontacter, organiser des visites et vous accompagner dans votre projet immobilier. Elles ne sont jamais revendues à des tiers.</p>
<h2>3. Durée de conservation</h2>
<p>Vos données sont conservées pendant une durée maximale de trois ans à compter de notre dernier échange.</p>
<h2>4. Favoris</h2>
<p>La liste de vos biens favoris est enregistrée uniquement dans votre navigateur.</p>
<h2>5. Vos droits</h2>
<p>Vous disposez d’un droit d’accès, de rectification et de suppression de vos données. Pour l’exercer, contactez-nous via la page Contact.</p>
HTML;
}
