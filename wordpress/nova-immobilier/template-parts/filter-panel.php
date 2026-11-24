<?php
/** Listing filters (GET form #nova-filters, auto-submitted by theme.js on change). */
defined('ABSPATH') || exit;
$f = nova_current_filters();
$wilayas = array_unique(array_filter(array_merge(nova_used_wilayas(), [$f['wilaya']])));
sort($wilayas);
$budgets = nova_budget_options($f['transaction']);
?>
<form id="nova-filters" class="filters" action="<?php echo esc_url(get_post_type_archive_link('property')); ?>" method="get" data-filters>
    <fieldset class="filters__group">
        <legend class="field__label">Transaction</legend>
        <div class="segmented">
            <?php foreach (['' => 'Tous', 'vente' => 'Vente', 'location' => 'Location'] as $v => $label) : ?>
                <label class="<?php echo $f['transaction'] === $v ? 'is-active' : ''; ?>"><input type="radio" name="transaction" value="<?php echo esc_attr($v); ?>" class="sr-only" data-reset-budget <?php checked($f['transaction'], $v); ?>><?php echo esc_html($label); ?></label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <label class="field" for="f-bien"><span class="field__label">Type de bien</span>
        <select id="f-bien" name="bien">
            <option value="">Tous les types</option>
            <?php foreach (nova_type_labels() as $k => $v) : ?><option value="<?php echo esc_attr($k); ?>" <?php selected($f['bien'], $k); ?>><?php echo esc_html($v); ?></option><?php endforeach; ?>
        </select>
    </label>

    <label class="field" for="f-wilaya"><span class="field__label">Wilaya</span>
        <select id="f-wilaya" name="wilaya">
            <option value="">Toutes les wilayas</option>
            <?php foreach ($wilayas as $w) : ?><option value="<?php echo esc_attr($w); ?>" <?php selected($f['wilaya'], $w); ?>><?php echo esc_html($w); ?></option><?php endforeach; ?>
        </select>
    </label>

    <div class="filters__row">
        <?php foreach (['prix_min' => ['Prix min.', 'Min.'], 'prix_max' => ['Prix max.', 'Max.']] as $name => [$label, $ph]) : ?>
            <label class="field" for="f-<?php echo esc_attr($name); ?>"><span class="field__label"><?php echo esc_html($label); ?></span>
                <select id="f-<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>">
                    <option value=""><?php echo esc_html($ph); ?></option>
                    <?php foreach ($budgets as $b) : ?><option value="<?php echo esc_attr($b); ?>" <?php selected($f[$name], $b); ?>><?php echo esc_html(nova_format_price_short($b)); ?></option><?php endforeach; ?>
                </select>
            </label>
        <?php endforeach; ?>
    </div>
    <?php if ($f['prix_min'] && $f['prix_max'] && $f['prix_min'] > $f['prix_max']) : ?>
        <p class="field__error" role="alert">Le prix minimum est supérieur au prix maximum.</p>
    <?php endif; ?>

    <label class="field" for="f-surface"><span class="field__label">Surface minimum</span>
        <select id="f-surface" name="surface">
            <option value="">Indifférente</option>
            <?php foreach ([50, 80, 100, 150, 200, 300, 500] as $s) : ?><option value="<?php echo $s; ?>" <?php selected($f['surface'], $s); ?>><?php echo $s; ?> m² et plus</option><?php endforeach; ?>
        </select>
    </label>

    <fieldset class="filters__group">
        <legend class="field__label">Pièces</legend>
        <div class="chips">
            <label class="chip <?php echo !$f['pieces'] ? 'is-active' : ''; ?>"><input type="radio" name="pieces" value="" class="sr-only" <?php checked($f['pieces'], 0); ?>>Tous</label>
            <?php foreach ([1, 2, 3, 4, 5] as $r) : ?>
                <label class="chip <?php echo $f['pieces'] === $r ? 'is-active' : ''; ?>"><input type="radio" name="pieces" value="<?php echo $r; ?>" class="sr-only" <?php checked($f['pieces'], $r); ?>><?php echo $r . ($r === 5 ? '+' : ''); ?><span class="sr-only"> pièce<?php echo $r > 1 ? 's' : ''; ?> minimum</span></label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <?php if ($f['tri'] !== 'recent') : ?><input type="hidden" name="tri" value="<?php echo esc_attr($f['tri']); ?>"><?php endif; ?>
    <noscript><button type="submit" class="btn btn--primary btn--block btn--sm">Appliquer</button></noscript>
    <a href="<?php echo esc_url(get_post_type_archive_link('property')); ?>" class="btn btn--outline btn--block btn--sm">Réinitialiser les filtres</a>
</form>
