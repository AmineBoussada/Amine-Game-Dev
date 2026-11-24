<?php
defined('ABSPATH') || exit;
$nav = nova_nav_items();
$over_hero = is_front_page();
$phone = nova_setting('phone');
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a href="#main" class="skip-link">Aller au contenu</a>

<header class="site-header <?php echo $over_hero ? 'is-transparent' : 'is-solid'; ?>" data-header <?php echo $over_hero ? 'data-over-hero' : ''; ?>>
    <div class="container site-header__inner">
        <span data-logo-dark <?php echo $over_hero ? 'hidden' : ''; ?>><?php echo nova_logo(false); ?></span>
        <?php if ($over_hero) : ?><span data-logo-light><?php echo nova_logo(true); ?></span><?php endif; ?>

        <nav class="main-nav" aria-label="Navigation principale">
            <ul>
                <?php foreach ($nav as $item) : ?>
                    <li><a href="<?php echo esc_url($item['url']); ?>" class="main-nav__link<?php echo $item['active'] ? ' is-active' : ''; ?>"<?php echo $item['active'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html($item['label']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="site-header__actions">
            <a href="<?php echo esc_url(home_url('/favoris/')); ?>" class="icon-btn header-fav" aria-label="Mes favoris">
                <?php echo nova_icon('heart'); ?>
                <span class="header-fav__count" data-fav-count hidden></span>
            </a>
            <?php if ($phone) : ?>
                <a href="<?php echo esc_attr(nova_tel_href($phone)); ?>" class="header-phone"><?php echo nova_icon('phone', 16); ?><span><?php echo esc_html($phone); ?></span></a>
            <?php endif; ?>
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn btn--primary btn--sm header-cta">Nous contacter</a>
            <button type="button" class="icon-btn menu-toggle" aria-expanded="false" aria-controls="mobile-nav" aria-label="Ouvrir le menu" data-menu-toggle>
                <span data-icon-open><?php echo nova_icon('menu', 22); ?></span>
                <span data-icon-close hidden><?php echo nova_icon('close', 22); ?></span>
            </button>
        </div>
    </div>
</header>

<div id="mobile-nav" class="mobile-nav" aria-hidden="true" data-mobile-nav>
    <nav aria-label="Navigation mobile">
        <ul>
            <?php foreach ($nav as $i => $item) : ?>
                <li style="--i:<?php echo (int) $i; ?>"><a href="<?php echo esc_url($item['url']); ?>" class="<?php echo $item['active'] ? 'is-active' : ''; ?>" tabindex="-1"><?php echo esc_html($item['label']); ?><?php echo nova_icon('arrow', 18); ?></a></li>
            <?php endforeach; ?>
            <li style="--i:<?php echo count($nav); ?>"><a href="<?php echo esc_url(home_url('/favoris/')); ?>" tabindex="-1">Mes favoris<?php echo nova_icon('heart', 18); ?></a></li>
        </ul>
    </nav>
    <div class="mobile-nav__footer">
        <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn btn--primary btn--block" tabindex="-1">Nous contacter</a>
        <div class="mobile-nav__contacts">
            <?php if ($phone) : ?><a href="<?php echo esc_attr(nova_tel_href($phone)); ?>" tabindex="-1"><?php echo nova_icon('phone', 16); ?> <?php echo esc_html($phone); ?></a><?php endif; ?>
            <a href="mailto:<?php echo esc_attr(nova_setting('email')); ?>" tabindex="-1"><?php echo nova_icon('mail', 16); ?> <?php echo esc_html(nova_setting('email')); ?></a>
        </div>
    </div>
</div>

<main id="main">
