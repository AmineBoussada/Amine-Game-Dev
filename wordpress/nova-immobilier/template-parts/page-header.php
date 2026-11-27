<?php
/** Page header with breadcrumb. $args: title, subtitle, crumbs [[label, url|null]]. */
defined('ABSPATH') || exit;
?>
<section class="page-header">
    <div class="container">
        <nav class="breadcrumb" aria-label="<?php echo esc_attr(nova_t('Fil d’Ariane')); ?>">
            <ol>
                <li><a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(nova_t('Accueil')); ?></a></li>
                <?php foreach ($args['crumbs'] ?? [] as [$label, $url]) : ?>
                    <li><?php if ($url) : ?><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a><?php else : ?><span aria-current="page"><?php echo esc_html($label); ?></span><?php endif; ?></li>
                <?php endforeach; ?>
            </ol>
        </nav>
        <h1><?php echo esc_html($args['title']); ?></h1>
        <?php if (!empty($args['subtitle'])) : ?><p class="lead"><?php echo esc_html($args['subtitle']); ?></p><?php endif; ?>
    </div>
</section>
