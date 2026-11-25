<?php defined('ABSPATH') || exit; ?>
<ul class="stats">
    <?php foreach ([1, 2, 3] as $i) : if (!nova_setting("stat{$i}_value")) continue; ?>
        <li><strong><bdi><?php echo esc_html(nova_setting("stat{$i}_value")); ?></bdi></strong><span><?php echo esc_html(nova_s("stat{$i}_label")); ?></span></li>
    <?php endforeach; ?>
</ul>
