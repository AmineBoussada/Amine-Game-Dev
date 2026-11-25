<?php
/** Homepage search: a GET form to the /biens/ archive. The budget list follows the tab (theme.js). */
defined('ABSPATH') || exit;
$archive = get_post_type_archive_link('property');
?>
<form class="search-bar rv-search" action="<?php echo esc_url($archive); ?>" method="get" role="search" aria-label="<?php echo esc_attr(bahia_t('Rechercher un bien')); ?>" data-search-bar>
    <div class="search-bar__tabs rv-tabs" role="radiogroup" aria-label="<?php echo esc_attr(bahia_t('Transaction')); ?>">
        <label class="rv-tab is-active"><input type="radio" name="transaction" value="vente" checked class="sr-only"><?php echo esc_html(bahia_t('Acheter')); ?></label>
        <label class="rv-tab"><input type="radio" name="transaction" value="location" class="sr-only"><?php echo esc_html(bahia_t('Louer')); ?></label>
    </div>
    <div class="rv-search__fields">
        <label class="rv-search__field"><span class="rv-label"><?php echo esc_html(bahia_t('Bien')); ?></span>
            <select name="bien">
                <option value=""><?php echo esc_html(bahia_t('Tous les biens')); ?></option>
                <?php foreach (bahia_type_labels() as $k => $v) : ?><option value="<?php echo esc_attr($k); ?>"><?php echo esc_html(bahia_t($v)); ?></option><?php endforeach; ?>
            </select>
        </label>
        <label class="rv-search__field"><span class="rv-label"><?php echo esc_html(bahia_t('Quartier')); ?></span>
            <select name="commune">
                <option value=""><?php echo esc_html(bahia_t('Tous les quartiers')); ?></option>
                <?php foreach (bahia_used_communes() as $c) : ?><option value="<?php echo esc_attr($c); ?>"><?php echo esc_html(bahia_t($c)); ?></option><?php endforeach; ?>
            </select>
        </label>
        <label class="rv-search__field"><span class="rv-label"><?php echo esc_html(bahia_t('Budget')); ?></span>
            <select name="prix_max" data-budget>
                <option value=""><?php echo esc_html(bahia_t('Indifférent')); ?></option>
                <?php foreach (['vente', 'location'] as $tx) : foreach (bahia_budget_options($tx) as $b) : ?>
                    <option value="<?php echo esc_attr($b); ?>" data-tx="<?php echo esc_attr($tx); ?>" <?php echo $tx === 'location' ? 'hidden disabled' : ''; ?>><?php echo esc_html(sprintf(bahia_t('Jusqu’à %s'), bahia_price_short($b))); ?></option>
                <?php endforeach; endforeach; ?>
            </select>
        </label>
        <button type="submit" class="rv-btn search-bar__submit"><?php echo esc_html(bahia_t('Rechercher')); ?></button>
    </div>
    <p class="rv-error" role="alert" hidden data-budget-error><?php echo esc_html(bahia_t('Le budget minimum doit être inférieur au budget maximum.')); ?></p>
</form>
