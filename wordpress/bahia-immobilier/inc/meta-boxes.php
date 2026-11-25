<?php
/**
 * Property edit screen: "Détails du bien" and "Photos du bien" meta boxes.
 */

defined('ABSPATH') || exit;

add_action('add_meta_boxes_property', function () {
    remove_meta_box('postimagediv', 'property', 'side'); // photos are managed in our gallery box
    add_meta_box('bahia-photos', 'Photos du bien', 'bahia_render_photos_box', 'property', 'normal', 'high');
    add_meta_box('bahia-details', 'Détails du bien', 'bahia_render_details_box', 'property', 'normal', 'high');
    add_meta_box('postexcerpt', 'Résumé (affiché sur les cartes et en tête de fiche)', 'post_excerpt_meta_box', 'property', 'normal', 'default');
});

add_action('admin_enqueue_scripts', function ($hook) {
    $screen = get_current_screen();
    $is_property = $screen && in_array($screen->post_type, ['property', 'bahia_lead'], true);
    $is_settings = $hook === 'toplevel_page_bahia-settings';
    if (!$is_property && !$is_settings) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_style('bahia-admin', BAHIA_URI . '/assets/css/admin.css', [], (string) @filemtime(BAHIA_DIR . '/assets/css/admin.css'));
    wp_enqueue_script('bahia-admin', BAHIA_URI . '/assets/js/admin.js', ['jquery', 'jquery-ui-sortable'], (string) @filemtime(BAHIA_DIR . '/assets/js/admin.js'), true);
});

function bahia_render_field(array $f, int $post_id): void
{
    $key = $f['key'];
    $id = "bahia-field-{$key}";
    $name = "bahia[{$key}]";
    $value = bahia_meta($post_id, $key);
    $req = !empty($f['required']);
    echo '<div class="bahia-field bahia-field--' . esc_attr($f['type']) . '" data-field="' . esc_attr($key) . '">';

    if ($f['type'] === 'checkbox') {
        printf(
            '<label class="bahia-switch" for="%1$s"><input type="hidden" name="%2$s" value="0"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s> <span>%4$s</span></label>',
            esc_attr($id), esc_attr($name), checked((bool) $value, true, false), esc_html($f['label'])
        );
    } else {
        printf('<label for="%s">%s%s</label>', esc_attr($id), esc_html($f['label']), $req ? ' <span class="bahia-req" aria-hidden="true">*</span>' : '');
        switch ($f['type']) {
            case 'select':
                printf('<select id="%s" name="%s"%s>', esc_attr($id), esc_attr($name), $req ? ' required' : '');
                foreach ($f['options'] as $v => $label) {
                    printf('<option value="%s" %s>%s</option>', esc_attr($v), selected((string) $value, (string) $v, false), esc_html($label));
                }
                echo '</select>';
                break;
            case 'number':
                printf(
                    '<input type="number" id="%s" name="%s" value="%s" step="any" %s %s %s>',
                    esc_attr($id), esc_attr($name), esc_attr($value),
                    isset($f['min']) ? 'min="' . esc_attr($f['min']) . '"' : '',
                    isset($f['max']) ? 'max="' . esc_attr($f['max']) . '"' : '',
                    $req ? 'required' : ''
                );
                if ($key === 'price') {
                    echo '<span class="bahia-price-preview" data-price-preview></span>';
                }
                break;
            case 'textarea':
                printf(
                    '<textarea id="%s" name="%s" rows="%d" placeholder="%s"%s%s>%s</textarea>',
                    esc_attr($id), esc_attr($name), (int) ($f['rows'] ?? 4), esc_attr($f['placeholder'] ?? ''),
                    !empty($f['dir']) ? ' dir="' . esc_attr($f['dir']) . '"' : '', $req ? ' required' : '', esc_textarea((string) $value)
                );
                break;
            default:
                printf(
                    '<input type="text" id="%s" name="%s" value="%s" placeholder="%s"%s%s>',
                    esc_attr($id), esc_attr($name), esc_attr($value), esc_attr($f['placeholder'] ?? ''),
                    !empty($f['dir']) ? ' dir="' . esc_attr($f['dir']) . '"' : '', $req ? ' required' : ''
                );
        }
    }
    if (!empty($f['help'])) {
        echo '<p class="description">' . esc_html($f['help']) . '</p>';
    }
    echo '</div>';
}

function bahia_render_details_box(WP_Post $post): void
{
    wp_nonce_field('bahia_save_property', 'bahia_property_nonce');
    $fields = bahia_property_fields();
    echo '<div class="bahia-admin">';
    foreach (bahia_field_groups() as $group => $title) {
        echo '<fieldset class="bahia-group bahia-group--' . esc_attr($group) . '"><legend>' . esc_html($title) . '</legend><div class="bahia-grid">';
        foreach ($fields as $f) {
            if ($f['group'] !== $group) {
                continue;
            }
            if ($f['type'] === 'features') {
                bahia_render_features($post->ID);
            } else {
                bahia_render_field($f, $post->ID);
            }
        }
        echo '</div></fieldset>';
    }
    echo '<p class="bahia-legend"><span class="bahia-req">*</span> Champs obligatoires</p></div>';
}

function bahia_render_features(int $post_id): void
{
    $selected = (array) bahia_meta($post_id, 'features');
    $options = bahia_feature_options();
    $custom = array_values(array_diff($selected, $options));
    echo '<div class="bahia-field bahia-field--features"><div class="bahia-checks">';
    foreach ($options as $opt) {
        printf(
            '<label><input type="checkbox" name="bahia[features][]" value="%s" %s> %s</label>',
            esc_attr($opt), checked(in_array($opt, $selected, true), true, false), esc_html($opt)
        );
    }
    echo '</div>';
    printf(
        '<label for="bahia-field-features-custom">Autres équipements (un par ligne)</label><textarea id="bahia-field-features-custom" name="bahia[features_custom]" rows="3" placeholder="Vitrine 9 m&#10;Hauteur sous plafond 4,5 m">%s</textarea>',
        esc_textarea(implode("\n", $custom))
    );
    echo '</div>';
}

function bahia_render_photos_box(WP_Post $post): void
{
    $ids = array_filter(array_map('absint', explode(',', (string) bahia_meta($post->ID, 'gallery'))));
    ?>
    <div class="bahia-admin bahia-gallery" data-bahia-gallery>
        <input type="hidden" name="bahia[gallery]" value="<?php echo esc_attr(implode(',', $ids)); ?>" data-gallery-input>
        <ul class="bahia-gallery__list" data-gallery-list>
            <?php foreach ($ids as $att) : ?>
                <li data-id="<?php echo esc_attr($att); ?>">
                    <?php echo wp_get_attachment_image($att, 'thumbnail'); ?>
                    <button type="button" class="bahia-gallery__remove" aria-label="Retirer cette photo">×</button>
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="bahia-gallery__empty" data-gallery-empty <?php echo $ids ? 'hidden' : ''; ?>>
            Aucune photo pour le moment : une illustration sera affichée sur le site.
        </p>
        <p>
            <button type="button" class="button button-primary" data-gallery-add>Ajouter des photos</button>
            <span class="description">Glissez les photos pour changer l’ordre. La première photo est la photo principale.</span>
        </p>
    </div>
    <?php
}

add_action('save_post_property', function (int $post_id, WP_Post $post) {
    if (!isset($_POST['bahia_property_nonce']) || !wp_verify_nonce($_POST['bahia_property_nonce'], 'bahia_save_property')) {
        return;
    }
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id) || wp_is_post_revision($post_id)) {
        return;
    }
    $input = wp_unslash($_POST['bahia'] ?? []);
    $missing = [];

    foreach (bahia_property_fields() as $f) {
        $key = $f['key'];
        $raw = $input[$key] ?? null;
        switch ($f['type']) {
            case 'number':
                $value = ($raw === '' || $raw === null) ? '' : max((float) ($f['min'] ?? 0), (float) str_replace([' ', ','], ['', '.'], (string) $raw));
                break;
            case 'checkbox':
                $value = !empty($raw) ? 1 : 0;
                break;
            case 'select':
                $value = array_key_exists((string) $raw, $f['options']) ? (string) $raw : ($f['default'] ?? '');
                break;
            case 'features':
                $value = array_map('sanitize_text_field', (array) ($raw ?? []));
                $custom = preg_split('/\r?\n/', (string) ($input['features_custom'] ?? ''));
                $value = array_values(array_unique(array_filter(array_merge($value, array_map('sanitize_text_field', $custom)))));
                break;
            case 'gallery':
                $value = implode(',', array_filter(array_map('absint', explode(',', (string) $raw))));
                break;
            case 'textarea':
                $value = sanitize_textarea_field((string) $raw);
                break;
            default:
                $value = sanitize_text_field((string) $raw);
        }
        if (!empty($f['required']) && ($value === '' || $value === null)) {
            $missing[] = $f['label'];
        }
        update_post_meta($post_id, "_bahia_{$key}", $value);
    }

    if (bahia_meta($post_id, 'reference') === '') {
        update_post_meta($post_id, '_bahia_reference', 'BHA-' . (1000 + $post_id));
    }

    // First gallery photo becomes the featured image (used by social sharing and admin list).
    $gallery = array_filter(explode(',', (string) bahia_meta($post_id, 'gallery')));
    if ($gallery) {
        set_post_thumbnail($post_id, (int) $gallery[0]);
    } else {
        delete_post_thumbnail($post_id);
    }

    if ($missing) {
        set_transient('bahia_missing_' . get_current_user_id(), $missing, 60);
    }
}, 10, 2);

add_action('admin_notices', function () {
    $key = 'bahia_missing_' . get_current_user_id();
    if ($missing = get_transient($key)) {
        delete_transient($key);
        echo '<div class="notice notice-warning is-dismissible"><p><strong>Bien enregistré, mais des informations manquent :</strong> '
            . esc_html(implode(', ', $missing)) . '. Complétez-les pour un affichage correct sur le site.</p></div>';
    }
});
