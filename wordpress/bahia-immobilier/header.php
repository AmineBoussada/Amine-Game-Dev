<?php
defined('ABSPATH') || exit;
$nav = bahia_nav_items();
$over_hero = is_front_page();
$phone = bahia_setting('phone');
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

<header class="site-header <?php echo $over_hero ? 'is-transparent' : 'is-solid'; ?>" data-header <?php echo $over_hero ? 'data-over-hero' : ''; ?>>
    <div class="container site-header__inner">
        <span data-logo-dark <?php echo $over_hero ? 'hidden' : ''; ?>><?php echo bahia_logo(false); ?></span>
        <?php if ($over_hero) : ?><span data-logo-light><?php echo bahia_logo(true); ?></span><?php endif; ?>

        <nav class="main-nav" aria-label="<?php echo esc_attr(bahia_t('Navigation principale')); ?>">
            <ul>
                <?php foreach ($nav as $item) : ?>
                    <li><a href="<?php echo esc_url($item['url']); ?>" class="main-nav__link<?php echo $item['active'] ? ' is-active' : ''; ?>"<?php echo $item['active'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html(bahia_t($item['label'])); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="site-header__actions">
            <a href="<?php echo esc_url(bahia_lang_switch_url()); ?>" class="icon-btn header-lang" hreflang="<?php echo bahia_is_ar() ? 'fr' : 'ar'; ?>" lang="<?php echo bahia_is_ar() ? 'fr' : 'ar'; ?>" aria-label="<?php echo esc_attr(bahia_is_ar() ? 'Afficher le site en français' : 'عرض الموقع بالعربية'); ?>"><?php echo bahia_is_ar() ? 'FR' : 'ع'; ?></a>
            <button type="button" class="icon-btn header-theme" data-theme-toggle aria-pressed="false" aria-label="<?php echo esc_attr(bahia_t('Mode sombre')); ?>">
                <svg class="header-theme__moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5Z"/></svg>
                <svg class="header-theme__sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            </button>
            <a href="<?php echo esc_url(home_url('/favoris/')); ?>" class="icon-btn header-fav" aria-label="<?php echo esc_attr(bahia_t('Mes favoris')); ?>">
                <?php echo bahia_icon('heart'); ?>
                <span class="header-fav__count" data-fav-count hidden></span>
            </a>
            <?php if ($phone) : ?>
                <a href="<?php echo esc_attr(bahia_tel_href($phone)); ?>" class="header-phone"><?php echo bahia_icon('phone', 16); ?><bdi dir="ltr"><?php echo esc_html($phone); ?></bdi></a>
            <?php endif; ?>
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn btn--primary btn--sm header-cta"><?php echo esc_html(bahia_t('Nous contacter')); ?></a>
            <button type="button" class="icon-btn menu-toggle" aria-expanded="false" aria-controls="mobile-nav" aria-label="<?php echo esc_attr(bahia_t('Ouvrir le menu')); ?>" data-menu-toggle>
                <span data-icon-open><?php echo bahia_icon('menu', 22); ?></span>
                <span data-icon-close hidden><?php echo bahia_icon('close', 22); ?></span>
            </button>
        </div>
    </div>
</header>

<div id="mobile-nav" class="mobile-nav" aria-hidden="true" data-mobile-nav>
    <nav aria-label="<?php echo esc_attr(bahia_t('Navigation mobile')); ?>">
        <ul>
            <?php foreach ($nav as $i => $item) : ?>
                <li style="--i:<?php echo (int) $i; ?>"><a href="<?php echo esc_url($item['url']); ?>" class="<?php echo $item['active'] ? 'is-active' : ''; ?>" tabindex="-1"><?php echo esc_html(bahia_t($item['label'])); ?><?php echo bahia_icon('arrow', 18, 'rtl-flip'); ?></a></li>
            <?php endforeach; ?>
            <li style="--i:<?php echo count($nav); ?>"><a href="<?php echo esc_url(home_url('/favoris/')); ?>" tabindex="-1"><?php echo esc_html(bahia_t('Mes favoris')); ?><?php echo bahia_icon('heart', 18); ?></a></li>
        </ul>
    </nav>
    <div class="mobile-nav__footer">
        <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn btn--primary btn--block" tabindex="-1"><?php echo esc_html(bahia_t('Nous contacter')); ?></a>
        <div class="mobile-nav__contacts">
            <?php if ($phone) : ?><a href="<?php echo esc_attr(bahia_tel_href($phone)); ?>" tabindex="-1"><?php echo bahia_icon('phone', 16); ?> <bdi dir="ltr"><?php echo esc_html($phone); ?></bdi></a><?php endif; ?>
            <a href="mailto:<?php echo esc_attr(bahia_setting('email')); ?>" tabindex="-1"><?php echo bahia_icon('mail', 16); ?> <?php echo esc_html(bahia_setting('email')); ?></a>
        </div>
    </div>
</div>

<main id="main">
