<?php
defined('ABSPATH') || exit;
get_header();
the_post();
$p = nova_get_property();
$tx_label = nova_transaction_labels()[$p['transaction']];
$status_label = nova_status_labels()[$p['status']] ?? '';
$wa = nova_whatsapp_link(nova_property_whatsapp_message($p));
$phone = nova_setting('phone');
$archive = get_post_type_archive_link('property');

$specs = [['area', $p['type'] === 'terrain' ? 'Superficie' : 'Surface habitable', $p['surface'] . ' m²']];
if ($p['land_surface']) $specs[] = ['layers', 'Terrain', $p['land_surface'] . ' m²'];
if ($p['rooms']) $specs[] = ['rooms', 'Pièces', $p['rooms']];
if ($p['bedrooms']) $specs[] = ['bed', 'Chambres', $p['bedrooms']];
if ($p['bathrooms']) $specs[] = ['bath', 'Salles de bain', $p['bathrooms']];
if ($p['floor']) $specs[] = ['floor', 'Étage', $p['floor']];
if ($p['year_built']) $specs[] = ['calendar', 'Construction', $p['year_built']];

$price_txt = nova_format_price($p['price']) . nova_price_suffix($p);
$details = [
    ['Référence', $p['reference']],
    ['Transaction', $tx_label],
    ['Type de bien', nova_type_labels()[$p['type']] ?? ''],
    ['Typologie', $p['typology']],
    ['Wilaya', $p['wilaya']],
    ['Commune', $p['commune']],
    ['Disponibilité', $status_label],
    [$p['transaction'] === 'location' ? 'Loyer mensuel' : 'Prix', $price_txt],
];
$default_subject = $p['transaction'] === 'location' ? 'Location d’un bien' : 'Achat d’un bien';
?>
<article class="detail">
    <div class="container">
        <nav class="breadcrumb detail__crumbs" aria-label="Fil d’Ariane">
            <ol>
                <li><a href="<?php echo esc_url(home_url('/')); ?>">Accueil</a></li>
                <li><a href="<?php echo esc_url($archive); ?>">Nos biens</a></li>
                <li><a href="<?php echo esc_url(add_query_arg('transaction', $p['transaction'], $archive)); ?>"><?php echo $p['transaction'] === 'vente' ? 'Acheter' : 'Louer'; ?></a></li>
                <li><span aria-current="page"><?php echo esc_html($p['reference']); ?></span></li>
            </ol>
        </nav>

        <header class="detail__header">
            <div>
                <div class="detail__badges">
                    <span class="badge badge--<?php echo esc_attr($p['transaction']); ?>"><?php echo esc_html($tx_label); ?></span>
                    <?php if ($p['typology']) : ?><span class="badge badge--soft"><?php echo esc_html($p['typology']); ?></span><?php endif; ?>
                    <?php if ($p['status'] !== 'disponible') : ?><span class="badge badge--status badge--<?php echo esc_attr($p['status']); ?>"><?php echo esc_html($status_label); ?></span><?php endif; ?>
                    <span class="detail__ref">Réf. <?php echo esc_html($p['reference']); ?></span>
                </div>
                <h1><?php echo esc_html($p['title']); ?></h1>
                <p class="detail__location"><?php echo nova_icon('pin', 18); ?> <?php echo esc_html(($p['quartier'] ? $p['quartier'] . ' · ' : '') . trim($p['commune'] . ', ' . $p['wilaya'], ', ')); ?></p>
            </div>
            <div class="detail__price-block">
                <p class="detail__price"><?php echo esc_html(nova_format_price($p['price'])); ?><?php if (nova_price_suffix($p)) : ?><small><?php echo esc_html(nova_price_suffix($p)); ?></small><?php endif; ?></p>
                <?php if ($p['transaction'] === 'vente' && $p['price'] > 0) : ?><p class="detail__centimes"><?php echo esc_html(nova_format_centimes($p['price'])); ?></p><?php endif; ?>
                <div class="detail__actions">
                    <button type="button" class="fav-btn fav-btn--outline" data-fav="<?php echo esc_attr($p['id']); ?>" aria-pressed="false" aria-label="Ajouter aux favoris" title="Ajouter aux favoris"><?php echo nova_icon('heart', 18); ?></button>
                    <button type="button" class="fav-btn fav-btn--outline" data-share aria-label="Partager ce bien" title="Partager"><?php echo nova_icon('share', 18); ?></button>
                    <span class="detail__toast" role="status" hidden data-share-toast>Lien copié</span>
                </div>
            </div>
        </header>

        <?php get_template_part('template-parts/gallery', null, ['p' => $p]); ?>

        <div class="detail__layout">
            <div class="detail__content">
                <ul class="detail__specs" aria-label="Caractéristiques principales">
                    <?php foreach ($specs as [$icon, $label, $value]) : ?>
                        <li><?php echo nova_icon($icon, 22); ?><span><small><?php echo esc_html($label); ?></small><strong><?php echo esc_html($value); ?></strong></span></li>
                    <?php endforeach; ?>
                </ul>

                <section class="detail__section" aria-labelledby="desc-title">
                    <h2 id="desc-title">Description</h2>
                    <?php if (has_excerpt()) : ?><p class="detail__excerpt"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
                    <div class="detail__text"><?php the_content(); ?></div>
                </section>

                <section class="detail__section" aria-labelledby="specs-title">
                    <h2 id="specs-title">Caractéristiques</h2>
                    <dl class="detail__table">
                        <?php foreach ($details as [$k, $v]) : if ($v === '' || $v === null) continue; ?>
                            <div><dt><?php echo esc_html($k); ?></dt><dd><?php echo esc_html($v); ?></dd></div>
                        <?php endforeach; ?>
                    </dl>
                </section>

                <?php if ($p['features']) : ?>
                    <section class="detail__section" aria-labelledby="feat-title">
                        <h2 id="feat-title">Équipements et prestations</h2>
                        <ul class="detail__features">
                            <?php foreach ($p['features'] as $feat) : ?><li><?php echo nova_icon('check', 16); ?> <?php echo esc_html($feat); ?></li><?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <section class="detail__section" aria-labelledby="loc-title">
                    <h2 id="loc-title">Localisation</h2>
                    <div class="detail__map">
                        <div class="detail__map-visual" aria-hidden="true"><span class="detail__map-pin"><?php echo nova_icon('pin', 26); ?></span></div>
                        <div class="detail__map-info">
                            <p><strong><?php echo esc_html(trim($p['commune'] . ', ' . $p['wilaya'], ', ')); ?></strong><?php if ($p['quartier']) : ?><span><?php echo esc_html($p['quartier']); ?></span><?php endif; ?></p>
                            <p class="muted">Pour préserver la confidentialité du propriétaire, l’adresse exacte est communiquée lors de la prise de rendez-vous.</p>
                            <a href="<?php echo esc_url(nova_maps_link($p['commune'] . ', ' . $p['wilaya'] . ', Algérie')); ?>" target="_blank" rel="noopener noreferrer" class="link-arrow">Voir le secteur sur Google Maps <?php echo nova_icon('arrow', 16); ?></a>
                        </div>
                    </div>
                </section>

                <section class="detail__section detail__form" id="formulaire" aria-labelledby="form-title">
                    <h2 id="form-title">Ce bien vous intéresse ?</h2>
                    <p class="muted">Laissez-nous vos coordonnées : un conseiller vous recontacte rapidement pour répondre à vos questions ou organiser une visite.</p>
                    <?php get_template_part('template-parts/contact-form', null, [
                        'subject' => $default_subject,
                        'property_id' => $p['id'],
                        'message' => sprintf('Bonjour, je suis intéressé(e) par le bien réf. %s (%s à %s). ', $p['reference'], $p['typology'] ?: nova_type_labels()[$p['type']], $p['commune']),
                        'submit_label' => 'Envoyer ma demande',
                    ]); ?>
                </section>
            </div>

            <aside class="detail__aside" aria-label="Contacter l’agence">
                <div class="agent-card">
                    <p class="agent-card__price"><?php echo esc_html(nova_format_price($p['price'])); ?><?php if (nova_price_suffix($p)) : ?><small><?php echo esc_html(nova_price_suffix($p)); ?></small><?php endif; ?></p>
                    <p class="agent-card__status agent-card__status--<?php echo esc_attr($p['status']); ?>"><span class="status-dot"></span> <?php echo esc_html($status_label); ?></p>
                    <div class="agent-card__agent">
                        <span class="agent-card__avatar" aria-hidden="true">NI</span>
                        <div><strong>Votre conseiller NOVA</strong><span>Réponse sous 24 h ouvrées</span></div>
                    </div>
                    <div class="agent-card__actions">
                        <button type="button" class="btn btn--primary btn--block" data-request-visit><?php echo nova_icon('calendar', 18); ?> Demander une visite</button>
                        <a href="<?php echo esc_url($wa); ?>" target="_blank" rel="noopener noreferrer" class="btn btn--whatsapp btn--block"><?php echo nova_icon('whatsapp', 18); ?> Contacter sur WhatsApp</a>
                        <?php if ($phone) : ?><a href="<?php echo esc_attr(nova_tel_href($phone)); ?>" class="btn btn--outline btn--block"><?php echo nova_icon('phone', 18); ?> <?php echo esc_html($phone); ?></a><?php endif; ?>
                    </div>
                    <p class="agent-card__note">Prix indicatif. Honoraires d’agence communiqués sur demande.</p>
                </div>
            </aside>
        </div>
    </div>

    <?php $similar = nova_similar_properties($p); if ($similar) : ?>
        <section class="section section--white detail__similar" aria-labelledby="similar-title">
            <div class="container">
                <div class="section-heading">
                    <div><span class="eyebrow">À découvrir aussi</span><h2 id="similar-title" class="section-heading__title">Biens similaires</h2></div>
                    <a href="<?php echo esc_url(add_query_arg('bien', $p['type'], $archive)); ?>" class="link-arrow">Tous les biens de ce type <?php echo nova_icon('arrow', 16); ?></a>
                </div>
                <div class="property-grid">
                    <?php foreach ($similar as $s) get_template_part('template-parts/property-card', null, ['p' => $s]); ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <div class="detail__mobile-bar">
        <?php if ($phone) : ?><a href="<?php echo esc_attr(nova_tel_href($phone)); ?>" class="btn btn--outline" aria-label="Appeler l’agence"><?php echo nova_icon('phone', 18); ?></a><?php endif; ?>
        <a href="<?php echo esc_url($wa); ?>" target="_blank" rel="noopener noreferrer" class="btn btn--whatsapp"><?php echo nova_icon('whatsapp', 18); ?> WhatsApp</a>
        <button type="button" class="btn btn--primary" data-request-visit>Visite</button>
    </div>
</article>
<?php get_footer();
