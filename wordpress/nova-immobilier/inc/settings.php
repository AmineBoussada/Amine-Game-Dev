<?php
/**
 * "Réglages de l'agence": contact details, social links and homepage texts,
 * editable by the owner (mirrors src/data/site.ts in the React demo).
 */

defined('ABSPATH') || exit;

function nova_settings_defaults(): array
{
    return apply_filters('nova_settings_defaults', [
        'phone' => '+213 23 00 00 00',
        'mobile' => '+213 550 00 00 00',
        'whatsapp' => '213550000000',
        'email' => 'contact@nova-immobilier.dz',
        'address' => '12, chemin des Glycines (adresse de démonstration)',
        'city' => 'Hydra, Alger',
        'hours' => "Dimanche – Jeudi | 08h30 – 17h30\nSamedi | 09h00 – 13h00\nVendredi | Fermé",
        'facebook' => 'https://www.facebook.com/',
        'instagram' => 'https://www.instagram.com/',
        'linkedin' => 'https://www.linkedin.com/',
        'youtube' => 'https://www.youtube.com/',
        'description' => 'Agence immobilière basée à Alger, NOVA IMMOBILIER accompagne particuliers et entreprises dans l’achat, la vente et la location de biens en Algérie.',
        'hero_title' => 'Le bien qui vous ressemble, au bon endroit.',
        'hero_text' => 'Découvrez une sélection de biens immobiliers soigneusement choisis pour concrétiser vos projets en Algérie.',
        'hero_image' => 0,
        'about_title' => 'Une agence à taille humaine, exigeante sur chaque détail.',
        'about_text' => 'Chez NOVA IMMOBILIER, nous accompagnons nos clients dans chaque étape de leur projet immobilier. Notre mission est de vous aider à trouver le bien idéal grâce à une approche personnalisée, transparente et professionnelle.',
        'about_image' => 0,
        'stat1_value' => '12', 'stat1_label' => 'ans d’expérience',
        'stat2_value' => '850+', 'stat2_label' => 'clients accompagnés',
        'stat3_value' => '24', 'stat3_label' => 'communes couvertes',
        'hide_unavailable' => 0,
        'demo_notice' => 1,
    ]);
}

function nova_setting(string $key)
{
    static $values = null;
    if ($values === null) {
        $values = wp_parse_args((array) get_option('nova_settings', []), nova_settings_defaults());
    }
    return $values[$key] ?? '';
}

/** Sections and fields of the settings page. */
function nova_settings_schema(): array
{
    return apply_filters('nova_settings_schema', [
        'contact' => ['Coordonnées de l’agence', [
            'phone' => ['Téléphone fixe', 'text'],
            'mobile' => ['Mobile (affiché)', 'text'],
            'whatsapp' => ['Numéro WhatsApp', 'text', 'Format international sans + ni espaces, ex. 213550123456.'],
            'email' => ['Email de contact', 'email', 'Les demandes du site sont envoyées à cette adresse.'],
            'address' => ['Adresse', 'text'],
            'city' => ['Commune, ville', 'text'],
            'hours' => ['Horaires', 'textarea', 'Une ligne par période : Jours | Horaires'],
            'description' => ['Présentation courte (pied de page)', 'textarea'],
        ]],
        'social' => ['Réseaux sociaux', [
            'facebook' => ['Facebook', 'url'],
            'instagram' => ['Instagram', 'url'],
            'linkedin' => ['LinkedIn', 'url'],
            'youtube' => ['YouTube', 'url', 'Laissez vide pour masquer un réseau.'],
        ]],
        'home' => ['Page d’accueil', [
            'hero_title' => ['Titre principal', 'text'],
            'hero_text' => ['Texte d’introduction', 'textarea'],
            'hero_image' => ['Image principale', 'image'],
            'about_title' => ['Titre « L’agence »', 'text'],
            'about_text' => ['Texte « L’agence »', 'textarea'],
            'about_image' => ['Image « L’agence »', 'image'],
            'stat1_value' => ['Chiffre clé 1', 'text'], 'stat1_label' => ['Libellé 1', 'text'],
            'stat2_value' => ['Chiffre clé 2', 'text'], 'stat2_label' => ['Libellé 2', 'text'],
            'stat3_value' => ['Chiffre clé 3', 'text'], 'stat3_label' => ['Libellé 3', 'text'],
        ]],
        'listing' => ['Affichage des biens', [
            'hide_unavailable' => ['Masquer les biens vendus ou loués de la liste', 'checkbox'],
            'demo_notice' => ['Afficher la mention « site de démonstration »', 'checkbox'],
        ]],
    ]);
}

add_action('admin_menu', function () {
    add_menu_page('Réglages de l’agence', 'Réglages agence', 'manage_options', 'nova-settings', 'nova_render_settings_page', 'dashicons-admin-home', 58);
});

add_action('admin_init', function () {
    register_setting('nova_settings', 'nova_settings', [
        'type' => 'array',
        'sanitize_callback' => function ($input) {
            $out = [];
            foreach (nova_settings_schema() as [$title, $fields]) {
                foreach ($fields as $key => $def) {
                    $v = $input[$key] ?? '';
                    $out[$key] = match ($def[1]) {
                        'email' => sanitize_email($v),
                        'url' => esc_url_raw($v),
                        'textarea' => sanitize_textarea_field($v),
                        'image' => absint($v),
                        'checkbox' => empty($v) ? 0 : 1,
                        default => sanitize_text_field($v),
                    };
                }
            }
            $out['whatsapp'] = preg_replace('/\D+/', '', $out['whatsapp']);
            return $out;
        },
    ]);
});

function nova_render_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    wp_enqueue_media();
    ?>
    <div class="wrap nova-admin">
        <h1>Réglages de l’agence</h1>
        <?php if (!empty($_GET['settings-updated'])) : ?>
            <div class="notice notice-success is-dismissible" id="setting-error-settings_updated"><p><strong>Réglages enregistrés.</strong> Le site est à jour.</p></div>
        <?php endif; ?>
        <p class="description">Ces informations apparaissent sur tout le site : en-tête, pied de page, page contact, liens WhatsApp et page d’accueil.</p>
        <form method="post" action="options.php">
            <?php settings_fields('nova_settings'); ?>
            <?php foreach (nova_settings_schema() as $section => [$title, $fields]) : ?>
                <div class="nova-card">
                    <h2><?php echo esc_html($title); ?></h2>
                    <table class="form-table" role="presentation">
                        <?php foreach ($fields as $key => $def) :
                            $value = nova_setting($key);
                            $id = "nova-{$key}";
                            $name = "nova_settings[{$key}]"; ?>
                            <tr>
                                <th scope="row"><label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($def[0]); ?></label></th>
                                <td>
                                    <?php if ($def[1] === 'textarea') : ?>
                                        <textarea id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" rows="4" class="large-text"><?php echo esc_textarea($value); ?></textarea>
                                    <?php elseif ($def[1] === 'checkbox') : ?>
                                        <input type="hidden" name="<?php echo esc_attr($name); ?>" value="0">
                                        <input type="checkbox" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" value="1" <?php checked($value, 1); ?>>
                                    <?php elseif ($def[1] === 'image') : ?>
                                        <div class="nova-image-field" data-nova-image>
                                            <input type="hidden" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>">
                                            <div class="nova-image-preview"><?php echo $value ? wp_get_attachment_image((int) $value, 'medium') : ''; ?></div>
                                            <button type="button" class="button" data-nova-image-pick>Choisir une image</button>
                                            <button type="button" class="button-link-delete" data-nova-image-clear <?php echo $value ? '' : 'hidden'; ?>>Retirer</button>
                                            <p class="description">Sans image, une illustration est affichée.</p>
                                        </div>
                                    <?php else : ?>
                                        <input type="<?php echo esc_attr($def[1]); ?>" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" class="regular-text">
                                    <?php endif; ?>
                                    <?php if (!empty($def[2])) : ?><p class="description"><?php echo esc_html($def[2]); ?></p><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endforeach; ?>
            <?php submit_button('Enregistrer les réglages'); ?>
        </form>
    </div>
    <?php
}
