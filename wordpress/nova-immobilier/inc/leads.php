<?php
/**
 * Contact form handling: validation, spam protection, storage as "Demandes" and email notification.
 */

defined('ABSPATH') || exit;

function nova_subjects(): array
{
    return ['Achat d’un bien', 'Location d’un bien', 'Demande de visite', 'Vendre ou louer mon bien', 'Estimation de mon bien', 'Recherche personnalisée', 'Autre demande'];
}

function nova_validate_lead(array $v): array
{
    $e = [];
    if (mb_strlen(trim($v['name'])) < 3) $e['name'] = 'Veuillez indiquer votre nom complet.';
    if (!preg_match('/^(?:(?:\+|00)213|0)(?:[5-7]\d{8}|[2-4]\d{7})$/', preg_replace('/[\s.\-]/', '', $v['phone']))) {
        $e['phone'] = 'Numéro invalide. Exemple : 0550 12 34 56 ou +213 550 12 34 56.';
    }
    if (!is_email($v['email'])) $e['email'] = 'Veuillez saisir une adresse email valide.';
    if (!in_array($v['subject'], nova_subjects(), true)) $e['subject'] = 'Veuillez choisir un sujet.';
    if (mb_strlen(trim($v['message'])) < 10) $e['message'] = 'Votre message doit contenir au moins 10 caractères.';
    if (empty($v['consent'])) $e['consent'] = 'Votre accord est nécessaire pour être recontacté(e).';
    return $e;
}

function nova_handle_lead(): void
{
    $back = wp_get_referer() ?: home_url('/contact/');
    $back = remove_query_arg(['envoye', 'erreur'], $back);
    $back = preg_replace('/#.*$/', '', $back);

    if (!isset($_POST['nova_lead_nonce']) || !wp_verify_nonce($_POST['nova_lead_nonce'], 'nova_lead')) {
        wp_safe_redirect(add_query_arg('erreur', 'session', $back) . '#formulaire');
        exit;
    }
    // Honeypot: bots fill every field.
    if (!empty($_POST['website'])) {
        wp_safe_redirect(add_query_arg('envoye', '1', $back) . '#formulaire');
        exit;
    }

    $in = wp_unslash($_POST);
    $v = [
        'name' => sanitize_text_field($in['name'] ?? ''),
        'phone' => sanitize_text_field($in['phone'] ?? ''),
        'email' => sanitize_email($in['email'] ?? ''),
        'subject' => sanitize_text_field($in['subject'] ?? ''),
        'message' => sanitize_textarea_field($in['message'] ?? ''),
        'consent' => !empty($in['consent']),
        'property_id' => absint($in['property_id'] ?? 0),
    ];
    $errors = nova_validate_lead($v);
    if ($errors) {
        set_transient('nova_lead_' . md5($_SERVER['REMOTE_ADDR'] ?? ''), ['values' => $v, 'errors' => $errors], 300);
        wp_safe_redirect(add_query_arg('erreur', 'champs', $back) . '#formulaire');
        exit;
    }

    $ref = $v['property_id'] ? nova_meta($v['property_id'], 'reference') : '';
    $lead_id = wp_insert_post([
        'post_type' => 'nova_lead',
        'post_status' => 'publish',
        'post_title' => sprintf('%s — %s%s', $v['name'], $v['subject'], $ref ? " ({$ref})" : ''),
    ]);
    foreach (['name', 'phone', 'email', 'subject', 'message', 'property_id'] as $k) {
        update_post_meta($lead_id, "_lead_{$k}", $v[$k]);
    }

    $to = nova_setting('email') ?: get_option('admin_email');
    $body = "Nouvelle demande depuis le site :\n\n"
        . "Nom : {$v['name']}\nTéléphone : {$v['phone']}\nEmail : {$v['email']}\nSujet : {$v['subject']}\n"
        . ($ref ? "Bien : {$ref} — " . get_permalink($v['property_id']) . "\n" : '')
        . "\nMessage :\n{$v['message']}\n\n— Voir toutes les demandes : " . admin_url('edit.php?post_type=nova_lead');
    // Mail delivery depends on the host (configure SMTP in production); the request is saved either way.
    @wp_mail($to, "[NOVA] {$v['subject']} — {$v['name']}", $body, ['Reply-To: ' . $v['name'] . ' <' . $v['email'] . '>']);

    wp_safe_redirect(add_query_arg(['envoye' => '1', 'nom' => rawurlencode(strtok($v['name'], ' '))], $back) . '#formulaire');
    exit;
}
add_action('admin_post_nova_lead', 'nova_handle_lead');
add_action('admin_post_nopriv_nova_lead', 'nova_handle_lead');

/** Values/errors to re-display after a failed server-side validation. */
function nova_lead_flash(): array
{
    if (($_GET['erreur'] ?? '') !== 'champs') {
        return ['values' => [], 'errors' => []];
    }
    $key = 'nova_lead_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    $data = get_transient($key) ?: ['values' => [], 'errors' => []];
    delete_transient($key);
    return $data;
}

/* ---------- Admin: lead list + detail ---------- */

add_filter('manage_nova_lead_posts_columns', fn($c) => [
    'cb' => $c['cb'], 'title' => 'Demande', 'lead_phone' => 'Téléphone', 'lead_email' => 'Email', 'lead_property' => 'Bien', 'date' => 'Reçue le',
]);

add_action('manage_nova_lead_posts_custom_column', function ($col, $id) {
    switch ($col) {
        case 'lead_phone':
            $ph = get_post_meta($id, '_lead_phone', true);
            echo '<a href="' . esc_attr(nova_tel_href($ph)) . '">' . esc_html($ph) . '</a>';
            break;
        case 'lead_email':
            $em = get_post_meta($id, '_lead_email', true);
            echo '<a href="mailto:' . esc_attr($em) . '">' . esc_html($em) . '</a>';
            break;
        case 'lead_property':
            $pid = (int) get_post_meta($id, '_lead_property_id', true);
            echo $pid ? '<a href="' . esc_url(get_edit_post_link($pid)) . '">' . esc_html(nova_meta($pid, 'reference')) . '</a>' : '—';
            break;
    }
}, 10, 2);

add_action('add_meta_boxes_nova_lead', function () {
    add_meta_box('nova-lead', 'Détails de la demande', function (WP_Post $post) {
        $g = fn($k) => get_post_meta($post->ID, "_lead_{$k}", true);
        $pid = (int) $g('property_id');
        $wa = 'https://wa.me/' . preg_replace(['/\D+/', '/^0/'], ['', '213'], $g('phone'));
        echo '<table class="form-table nova-lead-table">';
        echo '<tr><th>Nom</th><td>' . esc_html($g('name')) . '</td></tr>';
        echo '<tr><th>Téléphone</th><td><a href="' . esc_attr(nova_tel_href($g('phone'))) . '">' . esc_html($g('phone')) . '</a> · <a href="' . esc_url($wa) . '" target="_blank" rel="noopener">Répondre sur WhatsApp</a></td></tr>';
        echo '<tr><th>Email</th><td><a href="mailto:' . esc_attr($g('email')) . '">' . esc_html($g('email')) . '</a></td></tr>';
        echo '<tr><th>Sujet</th><td>' . esc_html($g('subject')) . '</td></tr>';
        if ($pid) {
            echo '<tr><th>Bien concerné</th><td><a href="' . esc_url(get_permalink($pid)) . '" target="_blank">' . esc_html(get_the_title($pid)) . '</a> (' . esc_html(nova_meta($pid, 'reference')) . ')</td></tr>';
        }
        echo '<tr><th>Message</th><td>' . nl2br(esc_html($g('message'))) . '</td></tr>';
        echo '<tr><th>Reçue le</th><td>' . esc_html(get_the_date('d/m/Y à H:i', $post)) . '</td></tr>';
        echo '</table>';
    }, 'nova_lead', 'normal', 'high');
});
