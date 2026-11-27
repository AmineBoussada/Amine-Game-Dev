<?php
/**
 * Contact form. $args: subject, message, property_id, submit_label.
 * Client-side validation in theme.js; the same rules run on the server (inc/leads.php).
 */
defined('ABSPATH') || exit;

if (($_GET['envoye'] ?? '') === '1') : ?>
    <div class="form-success" role="status">
        <span class="form-success__icon"><?php echo nova_icon('check', 28); ?></span>
        <h3><?php echo esc_html(nova_t('Merci, votre message a bien été envoyé.')); ?></h3>
        <p><?php echo esc_html(nova_t('Un conseiller vous recontactera sous 24 h ouvrées.')); ?></p>
        <a href="<?php echo esc_url(remove_query_arg(['envoye', 'nom']) . '#formulaire'); ?>" class="btn btn--outline btn--sm"><?php echo esc_html(nova_t('Envoyer un autre message')); ?></a>
    </div>
<?php return; endif;

$flash = nova_lead_flash();
$val = wp_parse_args($flash['values'], [
    'name' => '', 'phone' => '', 'email' => '',
    'subject' => $args['subject'] ?? '', 'message' => $args['message'] ?? '', 'consent' => false,
]);
$err = array_map('nova_t', $flash['errors']);
$uid = 'cf' . wp_rand(100, 999);
$field = function (string $key) use ($uid, $err) {
    return sprintf('id="%1$s-%2$s" name="%2$s" aria-invalid="%3$s"%4$s', $uid, $key, isset($err[$key]) ? 'true' : 'false', isset($err[$key]) ? " aria-describedby=\"{$uid}-{$key}-err\"" : '');
};
$error = function (string $key) use ($uid, $err) {
    return '<span class="field__error" id="' . esc_attr("{$uid}-{$key}-err") . '"' . (isset($err[$key]) ? '' : ' hidden') . '>' . esc_html($err[$key] ?? '') . '</span>';
};
?>
<?php if (($_GET['erreur'] ?? '') === 'session') : ?>
    <p class="field__error" role="alert"><?php echo esc_html(nova_t('La session a expiré. Merci de renvoyer le formulaire.')); ?></p>
<?php endif; ?>
<form class="contact-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" novalidate data-contact-form>
    <input type="hidden" name="action" value="nova_lead">
    <?php wp_nonce_field('nova_lead', 'nova_lead_nonce'); ?>
    <?php if (!empty($args['property_id'])) : ?><input type="hidden" name="property_id" value="<?php echo (int) $args['property_id']; ?>"><?php endif; ?>
    <div class="hp-field" aria-hidden="true"><label>Site web <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

    <div class="contact-form__grid">
        <div class="field<?php echo isset($err['name']) ? ' has-error' : ''; ?>" data-field="name">
            <label class="field__label" for="<?php echo $uid; ?>-name"><?php echo esc_html(nova_t('Nom complet')); ?> *</label>
            <input type="text" autocomplete="name" value="<?php echo esc_attr($val['name']); ?>" <?php echo $field('name'); ?>>
            <?php echo $error('name'); ?>
        </div>
        <div class="field<?php echo isset($err['phone']) ? ' has-error' : ''; ?>" data-field="phone">
            <label class="field__label" for="<?php echo $uid; ?>-phone"><?php echo esc_html(nova_t('Téléphone')); ?> *</label>
            <input type="tel" dir="ltr" inputmode="tel" autocomplete="tel" placeholder="0550 12 34 56" value="<?php echo esc_attr($val['phone']); ?>" <?php echo $field('phone'); ?>>
            <?php echo $error('phone'); ?>
        </div>
        <div class="field<?php echo isset($err['email']) ? ' has-error' : ''; ?>" data-field="email">
            <label class="field__label" for="<?php echo $uid; ?>-email"><?php echo esc_html(nova_t('Email')); ?> *</label>
            <input type="email" dir="ltr" inputmode="email" autocomplete="email" placeholder="vous@exemple.com" value="<?php echo esc_attr($val['email']); ?>" <?php echo $field('email'); ?>>
            <?php echo $error('email'); ?>
        </div>
        <div class="field<?php echo isset($err['subject']) ? ' has-error' : ''; ?>" data-field="subject">
            <label class="field__label" for="<?php echo $uid; ?>-subject"><?php echo esc_html(nova_t('Sujet')); ?> *</label>
            <select <?php echo $field('subject'); ?> data-subject>
                <option value=""><?php echo esc_html(nova_t('Choisir un sujet')); ?></option>
                <?php foreach (nova_subjects() as $s) : ?><option value="<?php echo esc_attr($s); ?>" <?php selected($val['subject'], $s); ?>><?php echo esc_html(nova_t($s)); ?></option><?php endforeach; ?>
            </select>
            <?php echo $error('subject'); ?>
        </div>
        <div class="field field--full<?php echo isset($err['message']) ? ' has-error' : ''; ?>" data-field="message">
            <label class="field__label" for="<?php echo $uid; ?>-message"><?php echo esc_html(nova_t('Message')); ?> *</label>
            <textarea rows="6" placeholder="<?php echo esc_attr(nova_t('Décrivez votre projet, vos disponibilités pour une visite…')); ?>" <?php echo $field('message'); ?>><?php echo esc_textarea($val['message']); ?></textarea>
            <?php echo $error('message'); ?>
        </div>
        <div class="field field--full<?php echo isset($err['consent']) ? ' has-error' : ''; ?>" data-field="consent">
            <label class="checkbox" for="<?php echo $uid; ?>-consent">
                <input type="checkbox" value="1" <?php checked($val['consent']); ?> <?php echo $field('consent'); ?>>
                <span><?php echo esc_html(sprintf(nova_t('J’accepte que %s utilise mes coordonnées pour me recontacter au sujet de ma demande.'), nova_t('NOVA IMMOBILIER'))); ?> <a href="<?php echo esc_url(get_privacy_policy_url() ?: home_url('/politique-de-confidentialite/')); ?>"><?php echo esc_html(nova_t('Politique de confidentialité')); ?></a></span>
            </label>
            <?php echo $error('consent'); ?>
        </div>
    </div>
    <button type="submit" class="btn btn--primary contact-form__submit"><span data-submit-label><?php echo esc_html($args['submit_label'] ?? nova_t('Envoyer le message')); ?> <?php echo nova_icon('arrow', 18, 'rtl-flip'); ?></span></button>
</form>
