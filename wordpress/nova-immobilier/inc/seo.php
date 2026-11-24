<?php
/**
 * Titles, meta descriptions, Open Graph and structured data.
 */

defined('ABSPATH') || exit;

add_filter('document_title_separator', fn() => '|');

add_filter('document_title_parts', function ($parts) {
    if (is_post_type_archive('property')) {
        $tx = nova_current_filters()['transaction'];
        $parts['title'] = $tx === 'vente' ? 'Biens à vendre' : ($tx === 'location' ? 'Biens à louer' : 'Nos biens immobiliers');
    }
    if (is_404()) {
        $parts['title'] = 'Page introuvable';
    }
    if (is_front_page()) {
        $parts = ['title' => 'NOVA IMMOBILIER', 'tagline' => 'Agence immobilière à Alger'];
    }
    return $parts;
});

function nova_meta_description(): string
{
    if (is_singular('property')) {
        $p = nova_get_property();
        return sprintf('%s à %s (%s) — %s m², %s%s. %s', nova_type_label($p), $p['commune'], $p['wilaya'], $p['surface'], nova_format_price($p['price']), nova_price_suffix($p), $p['excerpt']);
    }
    if (is_post_type_archive('property')) {
        return 'Consultez les appartements, villas, terrains et locaux commerciaux à vendre et à louer proposés par NOVA IMMOBILIER en Algérie.';
    }
    if (is_page('contact')) {
        return 'Contactez NOVA IMMOBILIER : téléphone, WhatsApp, email et formulaire de contact pour vos projets d’achat, de vente ou de location.';
    }
    if (is_singular() && has_excerpt()) {
        return get_the_excerpt();
    }
    return 'NOVA IMMOBILIER, agence immobilière à Alger : appartements, villas, terrains et locaux commerciaux à vendre et à louer en Algérie.';
}

add_action('wp_head', function () {
    $desc = wp_strip_all_tags(nova_meta_description());
    echo '<meta name="description" content="' . esc_attr(mb_substr($desc, 0, 300)) . "\">\n";
    echo '<meta name="theme-color" content="#F7F5F0">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr(wp_get_document_title()) . "\">\n";
    echo '<meta property="og:description" content="' . esc_attr(mb_substr($desc, 0, 200)) . "\">\n";
    if (is_singular('property') && has_post_thumbnail()) {
        echo '<meta property="og:image" content="' . esc_url(get_the_post_thumbnail_url(null, 'nova-large')) . "\">\n";
    }

    if (is_singular('property')) {
        $p = nova_get_property();
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'RealEstateListing',
            'name' => $p['title'],
            'description' => $p['excerpt'],
            'url' => $p['url'],
            'datePosted' => $p['date'],
            'offers' => [
                '@type' => 'Offer',
                'price' => $p['price'],
                'priceCurrency' => 'DZD',
                'businessFunction' => $p['transaction'] === 'location' ? 'http://purl.org/goodrelations/v1#LeaseOut' : 'http://purl.org/goodrelations/v1#Sell',
            ],
            'about' => [
                '@type' => $p['type'] === 'appartement' ? 'Apartment' : ($p['type'] === 'villa' ? 'House' : 'Place'),
                'floorSize' => ['@type' => 'QuantitativeValue', 'value' => $p['surface'], 'unitCode' => 'MTK'],
                'numberOfRooms' => $p['rooms'],
                'address' => ['@type' => 'PostalAddress', 'addressLocality' => $p['commune'], 'addressRegion' => $p['wilaya'], 'addressCountry' => 'DZ'],
            ],
        ];
        echo '<script type="application/ld+json">' . wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
    }
    if (is_front_page()) {
        $org = [
            '@context' => 'https://schema.org',
            '@type' => 'RealEstateAgent',
            'name' => 'NOVA IMMOBILIER',
            'url' => home_url('/'),
            'telephone' => nova_setting('phone'),
            'email' => nova_setting('email'),
            'address' => ['@type' => 'PostalAddress', 'streetAddress' => nova_setting('address'), 'addressLocality' => nova_setting('city'), 'addressCountry' => 'DZ'],
        ];
        echo '<script type="application/ld+json">' . wp_json_encode($org, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
    }
}, 2);
