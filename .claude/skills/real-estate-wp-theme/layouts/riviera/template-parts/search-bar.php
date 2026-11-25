<?php
/** Homepage search: a GET form to the /biens/ archive. The budget list follows the tab (theme.js). */
defined('ABSPATH') || exit;
$archive = get_post_type_archive_link('property');
?>
<form class="search-bar rv-search" action="<?php echo esc_url($archive); ?>" method="get" role="search" aria-label="<?php echo esc_attr(nova_t('Rechercher un bien')); ?>" data-search-bar>
    <div class="search-bar__tabs rv-tabs" role="radiogroup" aria-label="<?php echo esc_attr(nova_t('Transaction')); ?>">
        <label class="rv-tab is-active"><input type="radio" name="transaction" value="vente" checked class="sr-only"><?php echo esc_html(nova_t('Acheter')); ?></label>
        <label class="rv-tab"><input type="radio" name="transaction" value="location" class="sr-only"><?php echo esc_html(nova_t('Louer')); ?></label>
    </div>
    <div class="rv-search__fields">
        <label class="rv-search__field"><span class="rv-label"><?php echo esc_html(nova_t('Bien')); ?></span>
            <select name="bien">
                <option value=""><?php echo esc_html(nova_t('Tous les biens')); ?></option>
                <?php foreach (nova_type_labels() as $k => $v) : ?><option value="<?php echo esc_attr($k); ?>"><?php echo esc_html(nova_t($v)); ?></option><?php endforeach; ?>
            </select>
        </label>
        <label class="rv-search__field"><span class="rv-label"><?php echo esc_html(nova_t('Quartier')); ?></span>
            <select name="commune">
                <option value=""><?php echo esc_html(nova_t('Tous les quartiers')); ?></option>
                <?php foreach (nova_used_communes() as $c) : ?><option value="<?php echo esc_attr($c); ?>"><?php echo esc_html(nova_t($c)); ?></option><?php endforeach; ?>
            </select>
        </label>
        <label class="rv-search__field"><span class="rv-label"><?php echo esc_html(nova_t('Budget')); ?></span>
            <select name="prix_max" data-budget>
                <option value=""><?php echo esc_html(nova_t('Indifférent')); ?></option>
                <?php foreach (['vente', 'location'] as $tx) : foreach (nova_budget_options($tx) as $b) : ?>
                    <option value="<?php echo esc_attr($b); ?>" data-tx="<?php echo esc_attr($tx); ?>" <?php echo $tx === 'location' ? 'hidden disabled' : ''; ?>><?php echo esc_html(sprintf(nova_t('Jusqu’à %s'), nova_price_short($b))); ?></option>
                <?php endforeach; endforeach; ?>
            </select>
        </label>
        <button type="submit" class="rv-btn search-bar__submit"><?php echo esc_html(nova_t('Rechercher')); ?></button>
    </div>
    <p class="rv-error" role="alert" hidden data-budget-error><?php echo esc_html(nova_t('Le budget minimum doit être inférieur au budget maximum.')); ?></p>
</form>
