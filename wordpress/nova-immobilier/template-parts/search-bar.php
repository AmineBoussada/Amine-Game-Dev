<?php
/** Homepage search: a GET form to the /biens/ archive. Budgets switch with the tab (theme.js). */
defined('ABSPATH') || exit;
$archive = get_post_type_archive_link('property');
?>
<form class="search-bar" action="<?php echo esc_url($archive); ?>" method="get" role="search" aria-label="<?php echo esc_attr(nova_t('Rechercher un bien')); ?>" data-search-bar>
    <div class="search-bar__tabs" role="radiogroup" aria-label="<?php echo esc_attr(nova_t('Type de transaction')); ?>">
        <label class="is-active"><input type="radio" name="transaction" value="vente" checked class="sr-only"><?php echo esc_html(nova_t('Acheter')); ?></label>
        <label><input type="radio" name="transaction" value="location" class="sr-only"><?php echo esc_html(nova_t('Louer')); ?></label>
    </div>
    <div class="search-bar__fields">
        <label class="field"><span class="field__label"><?php echo esc_html(nova_t('Type de bien')); ?></span>
            <select name="bien">
                <option value=""><?php echo esc_html(nova_t('Tous les biens')); ?></option>
                <?php foreach (nova_type_labels() as $k => $v) : ?><option value="<?php echo esc_attr($k); ?>"><?php echo esc_html(nova_t($v)); ?></option><?php endforeach; ?>
            </select>
        </label>
        <label class="field"><span class="field__label"><?php echo esc_html(nova_t('Wilaya')); ?></span>
            <select name="wilaya">
                <option value=""><?php echo esc_html(nova_t('Toutes les wilayas')); ?></option>
                <?php foreach (nova_used_wilayas() as $w) : ?><option value="<?php echo esc_attr($w); ?>"><?php echo esc_html(nova_t($w)); ?></option><?php endforeach; ?>
            </select>
        </label>
        <?php foreach (['prix_min' => 'Budget min.', 'prix_max' => 'Budget max.'] as $name => $label) : ?>
            <label class="field"><span class="field__label"><?php echo esc_html(nova_t($label)); ?></span>
                <select name="<?php echo esc_attr($name); ?>" data-budget>
                    <option value=""><?php echo esc_html(nova_t('Indifférent')); ?></option>
                    <?php foreach (['vente', 'location'] as $tx) : foreach (nova_budget_options($tx) as $b) : ?>
                        <option value="<?php echo esc_attr($b); ?>" data-tx="<?php echo esc_attr($tx); ?>" <?php echo $tx === 'location' ? 'hidden disabled' : ''; ?>><?php echo esc_html(nova_price_short($b)); ?></option>
                    <?php endforeach; endforeach; ?>
                </select>
            </label>
        <?php endforeach; ?>
        <button type="submit" class="btn btn--primary search-bar__submit"><?php echo nova_icon('search', 18); ?> <?php echo esc_html(nova_t('Rechercher')); ?></button>
    </div>
    <p class="search-bar__error" role="alert" hidden data-budget-error><?php echo esc_html(nova_t('Le budget minimum doit être inférieur au budget maximum.')); ?></p>
</form>
