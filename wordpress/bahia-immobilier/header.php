<?php
defined('ABSPATH') || exit;
$nav = bahia_rv_nav();
$phone = bahia_setting('phone');
$ar = bahia_is_ar();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a href="#main" class="skip-link"><?php echo esc_html(bahia_t('Aller au contenu')); ?></a>
<header class="site-header" data-header>
    <?php echo bahia_stripes('rv-stripes--top'); ?>
    <div class="rv-head">
        <nav class="rv-nav" aria-label="<?php echo esc_attr(bahia_t('Navigation principale')); ?>">
            <?php foreach ($nav as $item) : ?>
                <a href="<?php echo esc_url($item['url']); ?>" class="rv-link"<?php echo $item['active'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html($item['label']); ?></a>
            <?php endforeach; ?>
        </nav>

        <?php echo bahia_rv_logo(); ?>

        <div class="rv-head__actions">
            <?php if ($phone) : ?>
                <a href="<?php echo esc_attr(bahia_tel_href($phone)); ?>" class="rv-phone"><bdi dir="ltr"><?php echo esc_html($phone); ?></bdi></a>
            <?php endif; ?>
            <a href="<?php echo esc_url(bahia_lang_switch_url()); ?>" class="rv-icon rv-lang" hreflang="<?php echo $ar ? 'fr' : 'ar'; ?>" lang="<?php echo $ar ? 'fr' : 'ar'; ?>" aria-label="<?php echo esc_attr($ar ? 'Afficher le site en français' : 'عرض الموقع بالعربية'); ?>"><?php echo $ar ? 'FR' : 'ع'; ?></a>
            <button type="button" class="rv-icon rv-theme" data-theme-toggle aria-pressed="false" aria-label="<?php echo esc_attr(bahia_t('Mode sombre')); ?>">
                <svg class="rv-theme__moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5Z"/></svg>
                <svg class="rv-theme__sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            </button>
            <a href="<?php echo esc_url(home_url('/favoris/')); ?>" class="rv-icon rv-favs" aria-label="<?php echo esc_attr(bahia_t('Mes favoris')); ?>">
                <?php echo bahia_icon('heart', 18); ?>
                <span class="rv-favs__count" data-fav-count hidden></span>
            </a>
            <a href="<?php echo esc_url(bahia_contact_url('visite', 'formulaire')); ?>" class="rv-btn rv-cta"><?php echo esc_html(bahia_t('Rendez-vous')); ?></a>
            <button type="button" class="rv-icon rv-burger" aria-expanded="false" aria-controls="mobile-nav" aria-label="<?php echo esc_attr(bahia_t('Ouvrir le menu')); ?>" data-menu-toggle>
                <span data-icon-open><?php echo bahia_icon('menu', 20); ?></span>
                <span data-icon-close hidden><?php echo bahia_icon('close', 20); ?></span>
            </button>
        </div>
    </div>
</header>

<div id="mobile-nav" class="rv-mobile-nav" aria-hidden="true" data-mobile-nav>
    <nav aria-label="<?php echo esc_attr(bahia_t('Navigation principale')); ?>">
        <?php foreach ($nav as $item) : ?>
            <a href="<?php echo esc_url($item['url']); ?>" tabindex="-1"<?php echo $item['active'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html($item['label']); ?><span aria-hidden="true" class="rv-arrow"></span></a>
        <?php endforeach; ?>
        <a href="<?php echo esc_url(home_url('/favoris/')); ?>" tabindex="-1"><?php echo esc_html(bahia_t('Mes favoris')); ?><span aria-hidden="true" class="rv-arrow"></span></a>
    </nav>
    <a href="<?php echo esc_url(bahia_contact_url('visite', 'formulaire')); ?>" class="rv-btn rv-btn--block" tabindex="-1"><?php echo esc_html(bahia_t('Rendez-vous')); ?></a>
    <?php if ($phone) : ?><a href="<?php echo esc_attr(bahia_tel_href($phone)); ?>" class="rv-mobile-nav__phone" tabindex="-1"><?php echo bahia_icon('phone', 16); ?> <bdi dir="ltr"><?php echo esc_html($phone); ?></bdi></a><?php endif; ?>
</div>

<main id="main">
