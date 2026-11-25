<?php
defined('ABSPATH') || exit;
get_header();
the_post();
$p = bahia_get_property();
$rent = $p['transaction'] === 'location';
$tx_label = bahia_t(bahia_transaction_labels()[$p['transaction']]);
$status_label = bahia_t(bahia_status_labels()[$p['status']] ?? '');
$wa = bahia_whatsapp_link(sprintf(bahia_t('Bonjour %1$s, je suis intéressé(e) par le bien « %2$s » (réf. %3$s). Pouvez-vous me recontacter ?'), bahia_t('Bahia Immobilier'), $p['title'], $p['reference']));
$phone = bahia_setting('phone');
$archive = get_post_type_archive_link('property');
$text = bahia_property_paragraphs($p);
$m2 = ' ' . bahia_t('m²');

$specs = [['area', bahia_t($p['type'] === 'terrain' ? 'Superficie' : 'Surface habitable'), bahia_num($p['surface']) . $m2]];
if ($p['land_surface']) $specs[] = ['layers', bahia_t('Terrain'), bahia_num($p['land_surface']) . $m2];
if ($p['rooms']) $specs[] = ['rooms', bahia_t('Pièces'), $p['rooms']];
if ($p['bedrooms']) $specs[] = ['bed', bahia_t('Chambres'), $p['bedrooms']];
if ($p['bathrooms']) $specs[] = ['bath', bahia_t('Salles de bain'), $p['bathrooms']];
if ($p['floor']) $specs[] = ['floor', bahia_t('Étage'), bahia_t($p['floor'])];
if ($p['year_built']) $specs[] = ['calendar', bahia_t('Construction'), $p['year_built']];

$price_txt = bahia_price($p['price']) . bahia_suffix($p);
$details = [
    ['Référence', $p['reference']],
    ['Transaction', $tx_label],
    ['Type de bien', bahia_t(bahia_type_labels()[$p['type']] ?? '')],
    ['Typologie', $p['typology']],
    ['Wilaya', bahia_t($p['wilaya'])],
    ['Commune', bahia_t($p['commune'])],
    ['Disponibilité', $status_label],
    [$rent ? 'Loyer mensuel' : 'Prix', $price_txt],
];
$default_subject = $rent ? 'Location d’un bien' : 'Achat d’un bien';
?>
<article class="detail">
    <div class="container">
        <nav class="breadcrumb detail__crumbs" aria-label="<?php echo esc_attr(bahia_t('Fil d’Ariane')); ?>">
            <ol>
                <li><a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(bahia_t('Accueil')); ?></a></li>
                <li><a href="<?php echo esc_url($archive); ?>"><?php echo esc_html(bahia_t('Nos biens')); ?></a></li>
                <li><a href="<?php echo esc_url(add_query_arg('transaction', $p['transaction'], $archive)); ?>"><?php echo esc_html(bahia_t($rent ? 'Louer' : 'Acheter')); ?></a></li>
                <li><span aria-current="page"><bdi dir="ltr"><?php echo esc_html($p['reference']); ?></bdi></span></li>
            </ol>
        </nav>

        <header class="detail__header">
            <div>
                <div class="detail__badges">
                    <span class="badge badge--<?php echo esc_attr($p['transaction']); ?>"><?php echo esc_html($tx_label); ?></span>
                    <?php if ($p['typology']) : ?><span class="badge badge--soft"><?php echo esc_html(bahia_t($p['typology'])); ?></span><?php endif; ?>
                    <?php if ($p['status'] !== 'disponible') : ?><span class="badge badge--status badge--<?php echo esc_attr($p['status']); ?>"><?php echo esc_html($status_label); ?></span><?php endif; ?>
                    <span class="detail__ref"><?php echo esc_html(bahia_t('Réf.')); ?> <bdi dir="ltr"><?php echo esc_html($p['reference']); ?></bdi></span>
                </div>
                <h1<?php echo bahia_lang_attr($p['title_lang']); ?>><?php echo esc_html($p['title']); ?></h1>
                <p class="detail__location"><?php echo bahia_icon('pin', 18); ?> <?php echo esc_html(($p['quartier'] ? bahia_t($p['quartier']) . ' · ' : '') . bahia_place($p)); ?></p>
            </div>
            <div class="detail__price-block">
                <p class="detail__price"><?php echo esc_html(bahia_price($p['price'])); ?><?php if ($rent) : ?><small><?php echo esc_html(bahia_suffix($p)); ?></small><?php endif; ?></p>
                <?php if (!$rent && $p['price'] > 0) : ?><p class="detail__centimes"><?php echo esc_html(bahia_centimes($p['price'])); ?></p><?php endif; ?>
                <div class="detail__actions">
                    <button type="button" class="fav-btn fav-btn--outline" data-fav="<?php echo esc_attr($p['id']); ?>" aria-pressed="false" aria-label="<?php echo esc_attr(bahia_t('Ajouter aux favoris')); ?>" title="<?php echo esc_attr(bahia_t('Ajouter aux favoris')); ?>"><?php echo bahia_icon('heart', 18); ?></button>
                    <button type="button" class="fav-btn fav-btn--outline" data-share aria-label="<?php echo esc_attr(bahia_t('Partager ce bien')); ?>" title="<?php echo esc_attr(bahia_t('Partager')); ?>"><?php echo bahia_icon('share', 18); ?></button>
                    <span class="detail__toast" role="status" hidden data-share-toast><?php echo esc_html(bahia_t('Lien copié')); ?></span>
                </div>
            </div>
        </header>

        <?php get_template_part('template-parts/gallery', null, ['p' => $p]); ?>

        <div class="detail__layout">
            <div class="detail__content">
                <ul class="detail__specs" aria-label="<?php echo esc_attr(bahia_t('Caractéristiques principales')); ?>">
                    <?php foreach ($specs as [$icon, $label, $value]) : ?>
                        <li><?php echo bahia_icon($icon, 22); ?><span><small><?php echo esc_html($label); ?></small><strong><?php echo esc_html($value); ?></strong></span></li>
                    <?php endforeach; ?>
                </ul>

                <section class="detail__section" aria-labelledby="desc-title">
                    <h2 id="desc-title"><?php echo esc_html(bahia_t('Description')); ?></h2>
                    <?php if ($p['excerpt']) : ?><p class="detail__excerpt"<?php echo bahia_lang_attr($p['text_lang']); ?>><?php echo esc_html($p['excerpt']); ?></p><?php endif; ?>
                    <div class="detail__text"<?php echo bahia_lang_attr($text['lang']); ?>><?php echo $text['html']; ?></div>
                </section>

                <section class="detail__section" aria-labelledby="specs-title">
                    <h2 id="specs-title"><?php echo esc_html(bahia_t('Caractéristiques')); ?></h2>
                    <dl class="detail__table">
                        <?php foreach ($details as [$k, $v]) : if ($v === '' || $v === null) continue; ?>
                            <div><dt><?php echo esc_html(bahia_t($k)); ?></dt><dd><?php echo $k === 'Référence' ? '<bdi dir="ltr">' . esc_html($v) . '</bdi>' : esc_html($v); ?></dd></div>
                        <?php endforeach; ?>
                    </dl>
                </section>

                <?php if ($p['features']) : ?>
                    <section class="detail__section" aria-labelledby="feat-title">
                        <h2 id="feat-title"><?php echo esc_html(bahia_t('Équipements et prestations')); ?></h2>
                        <ul class="detail__features">
                            <?php foreach ($p['features'] as $feat) : ?><li><?php echo bahia_icon('check', 16); ?> <?php echo esc_html(bahia_t($feat)); ?></li><?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <section class="detail__section" aria-labelledby="loc-title">
                    <h2 id="loc-title"><?php echo esc_html(bahia_t('Localisation')); ?></h2>
                    <div class="detail__map">
                        <div class="detail__map-visual" aria-hidden="true"><span class="detail__map-pin"><?php echo bahia_icon('pin', 26); ?></span></div>
                        <div class="detail__map-info">
                            <p><strong><?php echo esc_html(bahia_place($p)); ?></strong><?php if ($p['quartier']) : ?><span><?php echo esc_html(bahia_t($p['quartier'])); ?></span><?php endif; ?></p>
                            <p class="muted"><?php echo esc_html(bahia_t('Pour préserver la confidentialité du propriétaire, l’adresse exacte est communiquée lors de la prise de rendez-vous.')); ?></p>
                            <a href="<?php echo esc_url(bahia_maps_link($p['commune'] . ', ' . $p['wilaya'] . ', Algérie')); ?>" target="_blank" rel="noopener noreferrer" class="link-arrow"><?php echo esc_html(bahia_t('Voir le secteur sur Google Maps')); ?> <?php echo bahia_icon('arrow', 16, 'rtl-flip'); ?></a>
                        </div>
                    </div>
                </section>

                <section class="detail__section detail__form" id="formulaire" aria-labelledby="form-title">
                    <h2 id="form-title"><?php echo esc_html(bahia_t('Ce bien vous intéresse ?')); ?></h2>
                    <p class="muted"><?php echo esc_html(bahia_t('Laissez-nous vos coordonnées : un conseiller vous recontacte rapidement pour répondre à vos questions ou organiser une visite.')); ?></p>
                    <?php get_template_part('template-parts/contact-form', null, [
                        'subject' => $default_subject,
                        'property_id' => $p['id'],
                        'message' => sprintf(bahia_t('Bonjour, je suis intéressé(e) par le bien réf. %s.'), $p['reference']) . ' ',
                        'submit_label' => bahia_t('Envoyer ma demande'),
                    ]); ?>
                </section>
            </div>

            <aside class="detail__aside" aria-label="<?php echo esc_attr(bahia_t('Contacter l’agence')); ?>">
                <div class="agent-card">
                    <p class="agent-card__price"><?php echo esc_html(bahia_price($p['price'])); ?><?php if ($rent) : ?><small><?php echo esc_html(bahia_suffix($p)); ?></small><?php endif; ?></p>
                    <p class="agent-card__status agent-card__status--<?php echo esc_attr($p['status']); ?>"><span class="status-dot"></span> <?php echo esc_html($status_label); ?></p>
                    <div class="agent-card__agent">
                        <span class="agent-card__avatar" aria-hidden="true">BI</span>
                        <div><strong><?php echo esc_html(bahia_t('Votre conseiller BAHIA')); ?></strong><span><?php echo esc_html(bahia_t('Réponse sous 24 h ouvrées')); ?></span></div>
                    </div>
                    <div class="agent-card__actions">
                        <button type="button" class="btn btn--primary btn--block" data-request-visit><?php echo bahia_icon('calendar', 18); ?> <?php echo esc_html(bahia_t('Demander une visite')); ?></button>
                        <a href="<?php echo esc_url($wa); ?>" target="_blank" rel="noopener noreferrer" class="btn btn--whatsapp btn--block"><?php echo bahia_icon('whatsapp', 18); ?> <?php echo esc_html(bahia_t('Contacter sur WhatsApp')); ?></a>
                        <?php if ($phone) : ?><a href="<?php echo esc_attr(bahia_tel_href($phone)); ?>" class="btn btn--outline btn--block"><?php echo bahia_icon('phone', 18); ?> <bdi dir="ltr"><?php echo esc_html($phone); ?></bdi></a><?php endif; ?>
                    </div>
                    <p class="agent-card__note"><?php echo esc_html(bahia_t('Prix indicatif. Honoraires d’agence communiqués sur demande.')); ?></p>
                </div>
            </aside>
        </div>
    </div>

    <?php $similar = bahia_similar_properties($p); if ($similar) : ?>
        <section class="section section--white detail__similar" aria-labelledby="similar-title">
            <div class="container">
                <div class="section-heading">
                    <div><span class="eyebrow"><?php echo esc_html(bahia_t('À découvrir aussi')); ?></span><h2 id="similar-title" class="section-heading__title"><?php echo esc_html(bahia_t('Biens similaires')); ?></h2></div>
                    <a href="<?php echo esc_url(add_query_arg('bien', $p['type'], $archive)); ?>" class="link-arrow"><?php echo esc_html(bahia_t('Tous les biens de ce type')); ?> <?php echo bahia_icon('arrow', 16, 'rtl-flip'); ?></a>
                </div>
                <div class="property-grid">
                    <?php foreach ($similar as $s) get_template_part('template-parts/property-card', null, ['p' => $s]); ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <div class="detail__mobile-bar">
        <?php if ($phone) : ?><a href="<?php echo esc_attr(bahia_tel_href($phone)); ?>" class="btn btn--outline" aria-label="<?php echo esc_attr(bahia_t('Appeler l’agence')); ?>"><?php echo bahia_icon('phone', 18); ?></a><?php endif; ?>
        <a href="<?php echo esc_url($wa); ?>" target="_blank" rel="noopener noreferrer" class="btn btn--whatsapp"><?php echo bahia_icon('whatsapp', 18); ?> <?php echo esc_html(bahia_t('WhatsApp')); ?></a>
        <button type="button" class="btn btn--primary" data-request-visit><?php echo esc_html(bahia_t('Visite')); ?></button>
    </div>
</article>
<?php get_footer();
