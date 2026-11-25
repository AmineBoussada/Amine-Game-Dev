<?php
/** Listing filter bar (GET form #bahia-filters, submitted by theme.js on every change). */
defined('ABSPATH') || exit;
$f = bahia_current_filters();
$communes = array_unique(array_filter(array_merge(bahia_used_communes(), [$f['commune']])));
sort($communes);
$budgets = bahia_budget_options($f['transaction']);
$archive = get_post_type_archive_link('property');
?>
<div class="rv-filterbar">
    <form id="bahia-filters" class="rv-wrap rv-filters" action="<?php echo esc_url($archive); ?>" method="get" role="search" aria-label="<?php echo esc_attr(bahia_t('Filtrer les biens')); ?>" data-filters>
        <fieldset class="rv-row segmented">
            <legend class="sr-only"><?php echo esc_html(bahia_t('Transaction')); ?></legend>
            <?php foreach (['' => 'Tous', 'vente' => 'Vente', 'location' => 'Location'] as $v => $label) : ?>
                <label class="rv-tab<?php echo $f['transaction'] === $v ? ' is-active' : ''; ?>"><input type="radio" name="transaction" value="<?php echo esc_attr($v); ?>" class="sr-only" data-reset-budget <?php checked($f['transaction'], $v); ?>><?php echo esc_html(bahia_t($label)); ?></label>
            <?php endforeach; ?>
        </fieldset>
        <span class="rv-sep" aria-hidden="true"></span>
        <fieldset class="rv-row rv-row--types">
            <legend class="sr-only"><?php echo esc_html(bahia_t('Type de bien')); ?></legend>
            <label class="rv-tab rv-tab--soft<?php echo $f['bien'] === '' ? ' is-active' : ''; ?>"><input type="radio" name="bien" value="" class="sr-only" <?php checked($f['bien'], ''); ?>><?php echo esc_html(bahia_t('Tous les types')); ?></label>
            <?php foreach (bahia_type_plural_labels() as $k => $v) : ?>
                <label class="rv-tab rv-tab--soft<?php echo $f['bien'] === $k ? ' is-active' : ''; ?>"><input type="radio" name="bien" value="<?php echo esc_attr($k); ?>" class="sr-only" <?php checked($f['bien'], $k); ?>><?php echo esc_html(bahia_t($v)); ?></label>
            <?php endforeach; ?>
        </fieldset>
        <div class="rv-selects">
            <label class="rv-select"><span><?php echo esc_html(bahia_t('Quartier')); ?></span>
                <select name="commune">
                    <option value=""><?php echo esc_html(bahia_t('Tous les quartiers')); ?></option>
                    <?php foreach ($communes as $c) : ?><option value="<?php echo esc_attr($c); ?>" <?php selected($f['commune'], $c); ?>><?php echo esc_html(bahia_t($c)); ?></option><?php endforeach; ?>
                </select>
            </label>
            <label class="rv-select"><span><?php echo esc_html(bahia_t('Budget')); ?></span>
                <select name="prix_max">
                    <option value=""><?php echo esc_html(bahia_t('Indifférent')); ?></option>
                    <?php foreach ($budgets as $b) : ?><option value="<?php echo esc_attr($b); ?>" <?php selected($f['prix_max'], $b); ?>><?php echo esc_html(sprintf(bahia_t('Jusqu’à %s'), bahia_price_short($b))); ?></option><?php endforeach; ?>
                </select>
            </label>
            <label class="rv-select sort-select"><span><?php echo esc_html(bahia_t('Trier')); ?></span>
                <select name="tri">
                    <?php foreach (bahia_sort_options() as $k => $v) : ?><option value="<?php echo esc_attr($k); ?>" <?php selected($f['tri'], $k); ?>><?php echo esc_html(bahia_t($v)); ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <?php foreach (['wilaya', 'prix_min', 'surface', 'pieces'] as $keep) : if ($f[$keep]) : ?>
            <input type="hidden" name="<?php echo esc_attr($keep); ?>" value="<?php echo esc_attr($f[$keep]); ?>">
        <?php endif; endforeach; ?>
        <noscript><button type="submit" class="rv-btn"><?php echo esc_html(bahia_t('Appliquer')); ?></button></noscript>
    </form>
</div>
