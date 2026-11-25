<?php
defined('ABSPATH') || exit;
get_header();
the_post();
$p = bahia_get_property();
$ar = bahia_is_ar();
$archive = get_post_type_archive_link('property');
$rent = $p['transaction'] === 'location';
$status_label = bahia_t(bahia_status_labels()[$p['status']] ?? '');
$wa = bahia_whatsapp_link(sprintf(bahia_t('Bonjour %1$s, je suis intéressé(e) par le bien « %2$s » (réf. %3$s). Pouvez-vous me recontacter ?'), bahia_t('Bahia Immobilier'), $p['title'], $p['reference']));
$phone = bahia_setting('phone');
$text = bahia_property_paragraphs($p);

$specs = [[$p['type'] === 'terrain' ? 'Superficie' : 'Surface', bahia_num($p['surface']) . ' ' . bahia_t('m²')]];
if ($p['land_surface']) $specs[] = ['Terrain', bahia_num($p['land_surface']) . ' ' . bahia_t('m²')];
if ($p['rooms']) $specs[] = ['Pièces', $p['rooms']];
if ($p['bedrooms']) $specs[] = ['Chambres', $p['bedrooms']];
if ($p['bathrooms']) $specs[] = ['Salles de bain', $p['bathrooms']];
if ($p['year_built']) $specs[] = ['Construction', $p['year_built']];

$details = [
    ['Référence', $p['reference']],
    ['Transaction', bahia_t(bahia_transaction_labels()[$p['transaction']])],
    ['Type de bien', bahia_kind($p)],
    ['Étage', $p['floor']],
    ['Quartier', $p['quartier']],
    ['Disponibilité', $status_label],
];
?>
<article class="rv-wrap rv-detail">
    <nav class="rv-crumbs" aria-label="<?php echo esc_attr(bahia_t('Fil d’Ariane')); ?>">
        <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(bahia_t('Accueil')); ?></a> <span aria-hidden="true">—</span>
        <a href="<?php echo esc_url($archive); ?>"><?php echo esc_html(bahia_t('Nos biens')); ?></a> <span aria-hidden="true">—</span>
        <span aria-current="page"><bdi dir="ltr"><?php echo esc_html($p['reference']); ?></bdi></span>
    </nav>

    <header class="rv-detail__head">
        <div class="rv-stack">
            <div class="detail__badges rv-badges">
                <span class="rv-badge rv-badge--static<?php echo $rent ? ' rv-badge--outline' : ''; ?>"><?php echo esc_html(bahia_t(bahia_transaction_labels()[$p['transaction']])); ?></span>
                <span class="rv-badge rv-badge--static rv-badge--outline"><?php echo esc_html(bahia_kind($p)); ?></span>
                <?php if ($p['status'] !== 'disponible') : ?><span class="rv-badge rv-badge--static rv-badge--status"><?php echo esc_html($status_label); ?></span><?php endif; ?>
                <span class="rv-muted"><?php echo esc_html(bahia_t('Réf.')); ?> <bdi dir="ltr"><?php echo esc_html($p['reference']); ?></bdi></span>
            </div>
            <h1 class="rv-h1"<?php echo bahia_lang_attr($p['title_lang']); ?>><?php echo esc_html($p['title']); ?></h1>
            <p class="rv-muted"><?php echo esc_html(bahia_place($p)); ?></p>
        </div>
        <div class="rv-detail__price">
            <p class="detail__price rv-price rv-price--xl"><?php echo esc_html(bahia_price($p['price'])); ?><?php if ($rent) : ?><small><?php echo esc_html(bahia_suffix($p)); ?></small><?php endif; ?></p>
            <?php if (!$rent && $p['price'] > 0) : ?><p class="rv-muted"><?php echo esc_html(bahia_centimes($p['price']) . ' · ' . bahia_t('prix indicatif')); ?></p><?php endif; ?>
            <div class="rv-detail__tools">
                <button type="button" class="rv-icon" data-fav="<?php echo esc_attr($p['id']); ?>" aria-pressed="false" aria-label="<?php echo esc_attr(bahia_t('Ajouter aux favoris')); ?>" title="<?php echo esc_attr(bahia_t('Ajouter aux favoris')); ?>"><?php echo bahia_icon('heart', 18); ?></button>
                <button type="button" class="rv-icon" data-share aria-label="<?php echo esc_attr(bahia_t('Partager ce bien')); ?>"><?php echo bahia_icon('share', 18); ?></button>
                <span class="rv-toast" role="status" hidden data-share-toast><?php echo esc_html(bahia_t('Lien copié')); ?></span>
            </div>
        </div>
    </header>

    <?php get_template_part('template-parts/gallery', null, ['p' => $p]); ?>

    <div class="rv-detail__layout">
        <div class="rv-detail__content">
            <dl class="rv-specs" aria-label="<?php echo esc_attr(bahia_t('Caractéristiques principales')); ?>">
                <?php foreach ($specs as [$label, $value]) : ?>
                    <div><dt><?php echo esc_html(bahia_t($label)); ?></dt><dd><?php echo esc_html($value); ?></dd></div>
                <?php endforeach; ?>
            </dl>

            <section class="rv-detail__section" aria-labelledby="desc-title">
                <h2 id="desc-title" class="rv-h3"><?php echo esc_html(bahia_t('Description')); ?></h2>
                <?php if ($p['excerpt']) : ?><p class="rv-lead"<?php echo bahia_lang_attr($p['text_lang']); ?>><?php echo esc_html($p['excerpt']); ?></p><?php endif; ?>
                <div class="rv-prose"<?php echo bahia_lang_attr($text['lang']); ?>><?php echo $text['html']; ?></div>
            </section>

            <?php if ($p['features']) : ?>
                <section class="rv-detail__section" aria-labelledby="feat-title">
                    <h2 id="feat-title" class="rv-h3"><?php echo esc_html(bahia_t('Équipements')); ?></h2>
                    <ul class="rv-features">
                        <?php foreach ($p['features'] as $feat) : ?><li><span class="rv-rule" aria-hidden="true"></span><?php echo esc_html(bahia_t($feat)); ?></li><?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <section class="rv-detail__section" aria-labelledby="specs-title">
                <h2 id="specs-title" class="rv-h3"><?php echo esc_html(bahia_t('Caractéristiques')); ?></h2>
                <dl class="rv-table">
                    <?php foreach ($details as [$k, $v]) : if ($v === '' || $v === null) continue; ?>
                        <div><dt><?php echo esc_html(bahia_t($k)); ?></dt><dd><?php echo esc_html(in_array($k, ['Étage', 'Quartier'], true) ? bahia_t($v) : $v); ?></dd></div>
                    <?php endforeach; ?>
                </dl>
            </section>

            <section class="rv-area-box" aria-labelledby="loc-title">
                <?php echo bahia_image(['id' => 0, 'alt' => '', 'scene' => 'promenade'], 'bahia-card', 'rv-area-box__img', false, ''); ?>
                <div class="rv-stack">
                    <span class="rv-eyebrow"><?php echo esc_html(bahia_t('Le quartier')); ?></span>
                    <h2 id="loc-title" class="rv-h3"><?php echo esc_html(bahia_place($p)); ?></h2>
                    <p><?php echo esc_html(bahia_t('L’adresse exacte est communiquée lors de la prise de rendez-vous, pour préserver la confidentialité du propriétaire.')); ?></p>
                    <a class="rv-link" href="<?php echo esc_url(bahia_maps_link($p['commune'] . ', ' . $p['wilaya'] . ', Algérie')); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(bahia_t('Voir le secteur sur la carte')); ?></a>
                </div>
            </section>

            <section class="rv-detail__section" id="formulaire" aria-labelledby="form-title">
                <h2 id="form-title" class="rv-h3"><?php echo esc_html(bahia_t('Ce bien vous intéresse ?')); ?></h2>
                <p class="rv-muted"><?php echo esc_html(bahia_t('Laissez-nous vos coordonnées : un conseiller vous recontacte rapidement pour répondre à vos questions ou organiser une visite.')); ?></p>
                <?php get_template_part('template-parts/contact-form', null, [
                    'subject' => $rent ? 'Location d’un bien' : 'Achat d’un bien',
                    'property_id' => $p['id'],
                    'message' => sprintf(bahia_t('Bonjour, je suis intéressé(e) par le bien réf. %s.'), $p['reference']) . ' ',
                    'submit_label' => bahia_t('Envoyer ma demande'),
                ]); ?>
            </section>
        </div>

        <aside class="agent-card rv-visit" aria-labelledby="visit-title" data-visit data-visit-ref="<?php echo esc_attr($p['reference']); ?>">
            <?php echo bahia_stripes('rv-stripes--visit'); ?>
            <span class="rv-eyebrow rv-eyebrow--gold"><?php echo esc_html(bahia_t('Visite privée')); ?></span>
            <h2 id="visit-title" class="rv-h3"><?php echo esc_html(bahia_t('Planifier une visite')); ?></h2>
            <p class="rv-visit__text"><?php echo esc_html(bahia_t('Choisissez un jour et un créneau ; un conseiller confirme par téléphone.')); ?></p>
            <div class="rv-visit__days" role="radiogroup" aria-label="<?php echo esc_attr(bahia_t('Jour de la visite')); ?>" data-visit-days></div>
            <div class="rv-visit__slots" role="radiogroup" aria-label="<?php echo esc_attr(bahia_t('Créneau')); ?>" data-visit-slots></div>
            <p class="rv-visit__summary" data-visit-summary><?php echo esc_html(bahia_t('Choisissez un jour, puis un créneau.')); ?></p>
            <button type="button" class="rv-btn rv-btn--light" data-request-visit><?php echo esc_html(bahia_t('Demander cette visite')); ?></button>
            <div class="rv-visit__contact">
                <a href="<?php echo esc_url($wa); ?>" target="_blank" rel="noopener noreferrer" class="rv-btn rv-btn--line"><?php echo bahia_icon('whatsapp', 16); ?> <?php echo esc_html(bahia_t('WhatsApp')); ?></a>
                <?php if ($phone) : ?><a href="<?php echo esc_attr(bahia_tel_href($phone)); ?>" class="rv-btn rv-btn--line"><?php echo bahia_icon('phone', 16); ?> <?php echo esc_html(bahia_t('Appeler')); ?></a><?php endif; ?>
            </div>
            <script type="application/json" data-visit-i18n><?php echo wp_json_encode([
                'dows' => bahia_t_list(['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam']),
                'days' => bahia_t_list(['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi']),
                'months' => bahia_t_list(['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre']),
                'pick' => bahia_t('Choisissez un créneau pour le %s.'),
                'summary' => bahia_t('Visite le %1$s à %2$s.'),
                'message' => bahia_t('Bonjour, je souhaite visiter le bien réf. %1$s le %2$s à %3$s.'),
            ], JSON_UNESCAPED_UNICODE); ?></script>
        </aside>
    </div>

    <?php $similar = bahia_similar_properties($p); if ($similar) : ?>
        <section class="rv-similar" aria-labelledby="similar-title">
            <h2 id="similar-title" class="rv-h2"><?php echo esc_html(sprintf(bahia_t('À voir aussi à %s'), bahia_t('Oran'))); ?></h2>
            <div class="property-grid rv-grid">
                <?php foreach ($similar as $s) get_template_part('template-parts/property-card', null, ['p' => $s]); ?>
            </div>
        </section>
    <?php endif; ?>
</article>

<div class="rv-mobile-bar">
    <?php if ($phone) : ?><a href="<?php echo esc_attr(bahia_tel_href($phone)); ?>" class="rv-btn rv-btn--ghost rv-mobile-bar__call" aria-label="<?php echo esc_attr(bahia_t('Appeler l’agence')); ?>"><?php echo bahia_icon('phone', 18); ?></a><?php endif; ?>
    <a href="<?php echo esc_url($wa); ?>" target="_blank" rel="noopener noreferrer" class="rv-btn rv-btn--ghost"><?php echo bahia_icon('whatsapp', 16); ?> <?php echo esc_html(bahia_t('WhatsApp')); ?></a>
    <button type="button" class="rv-btn" data-request-visit><?php echo esc_html(bahia_t('Visite')); ?></button>
</div>
<?php get_footer();
