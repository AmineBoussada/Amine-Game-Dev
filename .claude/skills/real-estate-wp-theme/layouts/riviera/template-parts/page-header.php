<?php
/** Page header: breadcrumb, title and intro. $args: title, subtitle, crumbs [[label, url|null]]. */
defined('ABSPATH') || exit;
?>
<section class="rv-wrap rv-pagehead">
    <div class="rv-pagehead__main">
        <nav class="rv-crumbs" aria-label="<?php echo esc_attr(nova_t('Fil d’Ariane')); ?>">
            <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(nova_t('Accueil')); ?></a>
            <?php foreach ($args['crumbs'] ?? [] as [$label, $url]) : ?>
                <span aria-hidden="true">—</span>
                <?php if ($url) : ?><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a><?php else : ?><span aria-current="page"><?php echo esc_html($label); ?></span><?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <h1 class="rv-h1"><?php echo esc_html($args['title']); ?></h1>
    </div>
    <?php if (!empty($args['subtitle'])) : ?><p class="rv-lead"><?php echo esc_html($args['subtitle']); ?></p><?php endif; ?>
</section>
