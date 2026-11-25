<?php
/**
 * "Réglages de l'agence": contact details, social links and homepage texts,
 * editable by the owner (mirrors src/data/site.ts in the React demo).
 */

defined('ABSPATH') || exit;

function bahia_settings_defaults(): array
{
    return apply_filters('bahia_settings_defaults', [
        'phone' => '+213 41 00 00 00',
        'mobile' => '+213 661 00 00 00',
        'whatsapp' => '213661000000',
        'email' => 'contact@bahia-immobilier.dz',
        'address' => 'Boulevard du Front de mer (adresse de démonstration)',
        'city' => 'Front de mer, Oran',
        'hours' => "Dimanche – Jeudi | 09h00 – 17h00\nSamedi | 09h00 – 13h00\nVendredi | Fermé",
        'facebook' => 'https://www.facebook.com/',
        'instagram' => 'https://www.instagram.com/',
        'linkedin' => '',
        'youtube' => '',
        'description' => 'Agence immobilière à Oran : achat, vente et location d’appartements, villas, terrains et locaux.',
        'hero_title' => 'La Méditerranée, côté adresse.',
        'hero_text' => 'Appartements face à la baie, villas sur les hauteurs de Canastel : une sélection courte, visitée et vérifiée par nos conseillers.',
        'hero_image' => 0,
        'about_title' => 'Une agence oranaise, attentive à chaque détail.',
        'about_text' => 'Chez Bahia Immobilier, un seul conseiller vous suit de la première visite à la remise des clés. Nous visitons chaque bien et vérifions ses documents avant de vous le proposer.',
        'about_image' => 0,
        'stat1_value' => '10', 'stat1_label' => 'ans à Oran',
        'stat2_value' => '600+', 'stat2_label' => 'clients accompagnés',
        'stat3_value' => '12', 'stat3_label' => 'quartiers couverts',
        'hide_unavailable' => 0,
        'demo_notice' => 1,
    ]);
}

function bahia_setting(string $key)
{
    static $values = null;
    if ($values === null) {
        $values = wp_parse_args((array) get_option('bahia_settings', []), bahia_settings_defaults());
    }
    return $values[$key] ?? '';
}

/** Sections and fields of the settings page. */
function bahia_settings_schema(): array
{
    return apply_filters('bahia_settings_schema', [
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
    add_menu_page('Réglages de l’agence', 'Réglages agence', 'manage_options', 'bahia-settings', 'bahia_render_settings_page', 'dashicons-admin-home', 58);
});

add_action('admin_init', function () {
    register_setting('bahia_settings', 'bahia_settings', [
        'type' => 'array',
        'sanitize_callback' => function ($input) {
            $out = [];
            foreach (bahia_settings_schema() as [$title, $fields]) {
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

function bahia_render_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    wp_enqueue_media();
    ?>
    <div class="wrap bahia-admin">
        <h1>Réglages de l’agence</h1>
        <?php if (!empty($_GET['settings-updated'])) : ?>
            <div class="notice notice-success is-dismissible" id="setting-error-settings_updated"><p><strong>Réglages enregistrés.</strong> Le site est à jour.</p></div>
        <?php endif; ?>
        <p class="description">Ces informations apparaissent sur tout le site : en-tête, pied de page, page contact, liens WhatsApp et page d’accueil.</p>
        <form method="post" action="options.php">
            <?php settings_fields('bahia_settings'); ?>
            <?php foreach (bahia_settings_schema() as $section => [$title, $fields]) : ?>
                <div class="bahia-card">
                    <h2><?php echo esc_html($title); ?></h2>
                    <table class="form-table" role="presentation">
                        <?php foreach ($fields as $key => $def) :
                            $value = bahia_setting($key);
                            $id = "bahia-{$key}";
                            $name = "bahia_settings[{$key}]"; ?>
                            <tr>
                                <th scope="row"><label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($def[0]); ?></label></th>
                                <td>
                                    <?php if ($def[1] === 'textarea') : ?>
                                        <textarea id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" rows="4" class="large-text"><?php echo esc_textarea($value); ?></textarea>
                                    <?php elseif ($def[1] === 'checkbox') : ?>
                                        <input type="hidden" name="<?php echo esc_attr($name); ?>" value="0">
                                        <input type="checkbox" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" value="1" <?php checked($value, 1); ?>>
                                    <?php elseif ($def[1] === 'image') : ?>
                                        <div class="bahia-image-field" data-bahia-image>
                                            <input type="hidden" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>">
                                            <div class="bahia-image-preview"><?php echo $value ? wp_get_attachment_image((int) $value, 'medium') : ''; ?></div>
                                            <button type="button" class="button" data-bahia-image-pick>Choisir une image</button>
                                            <button type="button" class="button-link-delete" data-bahia-image-clear <?php echo $value ? '' : 'hidden'; ?>>Retirer</button>
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
