<?php
/**
 * Contact form. $args: subject, message, property_id, submit_label.
 * Client-side validation in theme.js; the same rules run on the server (inc/leads.php).
 */
defined('ABSPATH') || exit;

if (($_GET['envoye'] ?? '') === '1') : ?>
    <div class="form-success" role="status">
        <span class="form-success__icon"><?php echo nova_icon('check', 28); ?></span>
        <h3>Merci<?php echo !empty($_GET['nom']) ? ' ' . esc_html(sanitize_text_field(wp_unslash($_GET['nom']))) : ''; ?>, votre message a bien été envoyé.</h3>
        <p>Un conseiller NOVA IMMOBILIER vous recontactera sous 24 h ouvrées.</p>
        <a href="<?php echo esc_url(remove_query_arg(['envoye', 'nom']) . '#formulaire'); ?>" class="btn btn--outline btn--sm">Envoyer un autre message</a>
    </div>
<?php return; endif;

$flash = nova_lead_flash();
$val = wp_parse_args($flash['values'], [
    'name' => '', 'phone' => '', 'email' => '',
    'subject' => $args['subject'] ?? '', 'message' => $args['message'] ?? '', 'consent' => false,
]);
$err = $flash['errors'];
$uid = 'cf' . wp_rand(100, 999);
$field = function (string $key) use ($uid, $err) {
    return sprintf('id="%1$s-%2$s" name="%2$s" aria-invalid="%3$s"%4$s', $uid, $key, isset($err[$key]) ? 'true' : 'false', isset($err[$key]) ? " aria-describedby=\"{$uid}-{$key}-err\"" : '');
};
$error = function (string $key) use ($uid, $err) {
    return '<span class="field__error" id="' . esc_attr("{$uid}-{$key}-err") . '"' . (isset($err[$key]) ? '' : ' hidden') . '>' . esc_html($err[$key] ?? '') . '</span>';
};
?>
<?php if (($_GET['erreur'] ?? '') === 'session') : ?>
    <p class="field__error" role="alert">La session a expiré. Merci de renvoyer le formulaire.</p>
<?php endif; ?>
<form class="contact-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" novalidate data-contact-form>
    <input type="hidden" name="action" value="nova_lead">
    <?php wp_nonce_field('nova_lead', 'nova_lead_nonce'); ?>
    <?php if (!empty($args['property_id'])) : ?><input type="hidden" name="property_id" value="<?php echo (int) $args['property_id']; ?>"><?php endif; ?>
    <div class="hp-field" aria-hidden="true"><label>Site web <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

    <div class="contact-form__grid">
        <div class="field<?php echo isset($err['name']) ? ' has-error' : ''; ?>" data-field="name">
            <label class="field__label" for="<?php echo $uid; ?>-name">Nom complet *</label>
            <input type="text" autocomplete="name" value="<?php echo esc_attr($val['name']); ?>" <?php echo $field('name'); ?>>
            <?php echo $error('name'); ?>
        </div>
        <div class="field<?php echo isset($err['phone']) ? ' has-error' : ''; ?>" data-field="phone">
            <label class="field__label" for="<?php echo $uid; ?>-phone">Téléphone *</label>
            <input type="tel" inputmode="tel" autocomplete="tel" placeholder="0550 12 34 56" value="<?php echo esc_attr($val['phone']); ?>" <?php echo $field('phone'); ?>>
            <?php echo $error('phone'); ?>
        </div>
        <div class="field<?php echo isset($err['email']) ? ' has-error' : ''; ?>" data-field="email">
            <label class="field__label" for="<?php echo $uid; ?>-email">Email *</label>
            <input type="email" inputmode="email" autocomplete="email" placeholder="vous@exemple.com" value="<?php echo esc_attr($val['email']); ?>" <?php echo $field('email'); ?>>
            <?php echo $error('email'); ?>
        </div>
        <div class="field<?php echo isset($err['subject']) ? ' has-error' : ''; ?>" data-field="subject">
            <label class="field__label" for="<?php echo $uid; ?>-subject">Sujet *</label>
            <select <?php echo $field('subject'); ?> data-subject>
                <option value="">Choisir un sujet</option>
                <?php foreach (nova_subjects() as $s) : ?><option value="<?php echo esc_attr($s); ?>" <?php selected($val['subject'], $s); ?>><?php echo esc_html($s); ?></option><?php endforeach; ?>
            </select>
            <?php echo $error('subject'); ?>
        </div>
        <div class="field field--full<?php echo isset($err['message']) ? ' has-error' : ''; ?>" data-field="message">
            <label class="field__label" for="<?php echo $uid; ?>-message">Message *</label>
            <textarea rows="6" placeholder="Décrivez votre projet, vos disponibilités pour une visite…" <?php echo $field('message'); ?>><?php echo esc_textarea($val['message']); ?></textarea>
            <?php echo $error('message'); ?>
        </div>
        <div class="field field--full<?php echo isset($err['consent']) ? ' has-error' : ''; ?>" data-field="consent">
            <label class="checkbox" for="<?php echo $uid; ?>-consent">
                <input type="checkbox" value="1" <?php checked($val['consent']); ?> <?php echo $field('consent'); ?>>
                <span>J’accepte que NOVA IMMOBILIER utilise mes coordonnées pour me recontacter au sujet de ma demande. Voir la <a href="<?php echo esc_url(get_privacy_policy_url() ?: home_url('/politique-de-confidentialite/')); ?>">politique de confidentialité</a>.</span>
            </label>
            <?php echo $error('consent'); ?>
        </div>
    </div>
    <button type="submit" class="btn btn--primary contact-form__submit"><span data-submit-label><?php echo esc_html($args['submit_label'] ?? 'Envoyer le message'); ?> <?php echo nova_icon('arrow', 18); ?></span></button>
</form>
