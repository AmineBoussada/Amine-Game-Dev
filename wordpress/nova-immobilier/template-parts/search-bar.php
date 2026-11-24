<?php
/** Homepage search: a GET form to the /biens/ archive. Budgets switch with the tab (theme.js). */
defined('ABSPATH') || exit;
$archive = get_post_type_archive_link('property');
?>
<form class="search-bar" action="<?php echo esc_url($archive); ?>" method="get" role="search" aria-label="Rechercher un bien" data-search-bar>
    <div class="search-bar__tabs" role="radiogroup" aria-label="Type de transaction">
        <label class="is-active"><input type="radio" name="transaction" value="vente" checked class="sr-only">Acheter</label>
        <label><input type="radio" name="transaction" value="location" class="sr-only">Louer</label>
    </div>
    <div class="search-bar__fields">
        <label class="field"><span class="field__label">Type de bien</span>
            <select name="bien">
                <option value="">Tous les biens</option>
                <?php foreach (nova_type_labels() as $k => $v) : ?><option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option><?php endforeach; ?>
            </select>
        </label>
        <label class="field"><span class="field__label">Wilaya</span>
            <select name="wilaya">
                <option value="">Toutes les wilayas</option>
                <?php foreach (nova_used_wilayas() as $w) : ?><option value="<?php echo esc_attr($w); ?>"><?php echo esc_html($w); ?></option><?php endforeach; ?>
            </select>
        </label>
        <?php foreach (['prix_min' => 'Budget min.', 'prix_max' => 'Budget max.'] as $name => $label) : ?>
            <label class="field"><span class="field__label"><?php echo esc_html($label); ?></span>
                <select name="<?php echo esc_attr($name); ?>" data-budget>
                    <option value="">Indifférent</option>
                    <?php foreach (['vente', 'location'] as $tx) : foreach (nova_budget_options($tx) as $b) : ?>
                        <option value="<?php echo esc_attr($b); ?>" data-tx="<?php echo esc_attr($tx); ?>" <?php echo $tx === 'location' ? 'hidden disabled' : ''; ?>><?php echo esc_html(nova_format_price_short($b)); ?></option>
                    <?php endforeach; endforeach; ?>
                </select>
            </label>
        <?php endforeach; ?>
        <button type="submit" class="btn btn--primary search-bar__submit"><?php echo nova_icon('search', 18); ?> Rechercher</button>
    </div>
    <p class="search-bar__error" role="alert" hidden data-budget-error>Le budget minimum doit être inférieur au budget maximum.</p>
</form>
