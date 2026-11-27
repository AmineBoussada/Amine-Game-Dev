<?php
/**
 * Property field registry — the single source of truth for every property detail
 * (admin form, saving/sanitising, REST exposure and front-end helpers).
 * Mirrors src/types/property.ts in the React demo.
 */

defined('ABSPATH') || exit;

function bahia_wilayas(): array
{
    return [
        'Adrar', 'Chlef', 'Laghouat', 'Oum El Bouaghi', 'Batna', 'Béjaïa', 'Biskra', 'Béchar', 'Blida', 'Bouira',
        'Tamanrasset', 'Tébessa', 'Tlemcen', 'Tiaret', 'Tizi Ouzou', 'Alger', 'Djelfa', 'Jijel', 'Sétif', 'Saïda',
        'Skikda', 'Sidi Bel Abbès', 'Annaba', 'Guelma', 'Constantine', 'Médéa', 'Mostaganem', "M'Sila", 'Mascara', 'Ouargla',
        'Oran', 'El Bayadh', 'Illizi', 'Bordj Bou Arréridj', 'Boumerdès', 'El Tarf', 'Tindouf', 'Tissemsilt', 'El Oued', 'Khenchela',
        'Souk Ahras', 'Tipaza', 'Mila', 'Aïn Defla', 'Naâma', 'Aïn Témouchent', 'Ghardaïa', 'Relizane', 'Timimoun', 'Bordj Badji Mokhtar',
        'Ouled Djellal', 'Béni Abbès', 'In Salah', 'In Guezzam', 'Touggourt', 'Djanet', "El M'Ghair", 'El Meniaa',
    ];
}

function bahia_transaction_labels(): array
{
    return ['vente' => 'Vente', 'location' => 'Location'];
}

function bahia_type_labels(): array
{
    return ['appartement' => 'Appartement', 'villa' => 'Villa', 'terrain' => 'Terrain', 'local' => 'Local commercial'];
}

function bahia_type_plural_labels(): array
{
    return ['appartement' => 'Appartements', 'villa' => 'Villas', 'terrain' => 'Terrains', 'local' => 'Locaux commerciaux'];
}

function bahia_status_labels(): array
{
    return ['disponible' => 'Disponible', 'sous-compromis' => 'Sous compromis', 'vendu' => 'Vendu', 'loue' => 'Loué'];
}

function bahia_feature_options(): array
{
    return [
        'Ascenseur', 'Parking', 'Garage', 'Climatisation', 'Chauffage central', 'Cuisine équipée', 'Meublé', 'Balcon',
        'Terrasse', 'Jardin', 'Piscine', 'Gardiennage', 'Résidence fermée', 'Interphone', 'Double vitrage', 'Bâche à eau',
        'Vue mer', 'Proche transports', 'Acte notarié', 'Livret foncier',
    ];
}

/**
 * Field definitions. `group` controls the admin layout; `key` is stored as post meta `_bahia_{key}`.
 */
function bahia_property_fields(): array
{
    $wilayas = array_combine(bahia_wilayas(), bahia_wilayas());
    return apply_filters('bahia_property_fields', [
        ['key' => 'reference', 'label' => 'Référence', 'type' => 'text', 'group' => 'essentiel', 'help' => 'Laissez vide pour générer automatiquement (ex. BHA-1024).'],
        ['key' => 'transaction', 'label' => 'Transaction', 'type' => 'select', 'group' => 'essentiel', 'options' => bahia_transaction_labels(), 'default' => 'vente', 'required' => true],
        ['key' => 'type', 'label' => 'Type de bien', 'type' => 'select', 'group' => 'essentiel', 'options' => bahia_type_labels(), 'default' => 'appartement', 'required' => true],
        ['key' => 'typology', 'label' => 'Typologie', 'type' => 'text', 'group' => 'essentiel', 'placeholder' => 'F3, Villa R+1, Terrain…'],
        ['key' => 'price', 'label' => 'Prix (DA)', 'type' => 'number', 'group' => 'essentiel', 'required' => true, 'help' => 'Pour une location : loyer mensuel en dinars.', 'min' => 0],
        ['key' => 'status', 'label' => 'Disponibilité', 'type' => 'select', 'group' => 'essentiel', 'options' => bahia_status_labels(), 'default' => 'disponible'],
        ['key' => 'featured', 'label' => 'Bien à la une (affiché sur l’accueil)', 'type' => 'checkbox', 'group' => 'essentiel'],

        ['key' => 'wilaya', 'label' => 'Wilaya', 'type' => 'select', 'group' => 'localisation', 'options' => $wilayas, 'default' => 'Oran', 'required' => true],
        ['key' => 'commune', 'label' => 'Commune', 'type' => 'text', 'group' => 'localisation', 'required' => true, 'placeholder' => 'Canastel, Front de mer, Akid Lotfi…'],
        ['key' => 'quartier', 'label' => 'Quartier / repère', 'type' => 'text', 'group' => 'localisation', 'placeholder' => 'Proche du Paradou'],

        ['key' => 'surface', 'label' => 'Surface (m²)', 'type' => 'number', 'group' => 'caracteristiques', 'required' => true, 'min' => 0, 'help' => 'Surface habitable, ou superficie pour un terrain.'],
        ['key' => 'land_surface', 'label' => 'Terrain (m²)', 'type' => 'number', 'group' => 'caracteristiques', 'min' => 0, 'help' => 'Pour les villas.'],
        ['key' => 'rooms', 'label' => 'Pièces', 'type' => 'number', 'group' => 'caracteristiques', 'min' => 0],
        ['key' => 'bedrooms', 'label' => 'Chambres', 'type' => 'number', 'group' => 'caracteristiques', 'min' => 0],
        ['key' => 'bathrooms', 'label' => 'Salles de bain', 'type' => 'number', 'group' => 'caracteristiques', 'min' => 0],
        ['key' => 'floor', 'label' => 'Étage', 'type' => 'text', 'group' => 'caracteristiques', 'placeholder' => '4ᵉ étage sur 6 — ascenseur'],
        ['key' => 'year_built', 'label' => 'Année de construction', 'type' => 'number', 'group' => 'caracteristiques', 'min' => 1800, 'max' => 2100],

        ['key' => 'features', 'label' => 'Équipements et prestations', 'type' => 'features', 'group' => 'equipements'],
        ['key' => 'gallery', 'label' => 'Photos', 'type' => 'gallery', 'group' => 'photos'],
    ]);
}

function bahia_field_groups(): array
{
    return apply_filters('bahia_field_groups', [
        'essentiel' => 'Prix et statut',
        'localisation' => 'Localisation',
        'caracteristiques' => 'Caractéristiques',
        'equipements' => 'Équipements',
    ]);
}

/** Read one property field with its default. */
function bahia_meta(int $post_id, string $key)
{
    $value = get_post_meta($post_id, "_bahia_{$key}", true);
    if ($value === '' || $value === null) {
        foreach (bahia_property_fields() as $f) {
            if ($f['key'] === $key) {
                return $f['default'] ?? ($f['type'] === 'features' ? [] : '');
            }
        }
    }
    return $value;
}

/** Register meta so it is typed and available to the REST API (headless option). */
add_action('init', function () {
    foreach (bahia_property_fields() as $f) {
        $type = match ($f['type']) {
            'number' => 'number',
            'checkbox' => 'boolean',
            'features' => 'array',
            default => 'string',
        };
        $args = [
            'type' => $type,
            'single' => true,
            'auth_callback' => fn() => current_user_can('edit_posts'),
            'show_in_rest' => true,
        ];
        if ($type === 'array') {
            $args['show_in_rest'] = ['schema' => ['type' => 'array', 'items' => ['type' => 'string']]];
        }
        register_post_meta('property', "_bahia_{$f['key']}", $args);
    }
});
