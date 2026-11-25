<?php
/**
 * Elementor widgets of the theme (loaded only when Elementor registers widgets).
 * Each widget reuses the theme's own template parts, so it looks and behaves like the rest of the site.
 */

defined('ABSPATH') || exit;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

abstract class BahiaTheme_Base_Widget extends Widget_Base
{
    public function get_categories(): array
    {
        return ['bahia'];
    }

    public function get_keywords(): array
    {
        return ['immobilier', 'bien', 'agence'];
    }

    /** Optional eyebrow + title above the widget, as in the theme's sections. */
    protected function heading_controls(string $default_title = '', string $default_eyebrow = ''): void
    {
        $this->add_control('eyebrow', ['label' => 'Surtitre', 'type' => Controls_Manager::TEXT, 'default' => $default_eyebrow]);
        $this->add_control('title', ['label' => 'Titre', 'type' => Controls_Manager::TEXT, 'default' => $default_title, 'label_block' => true]);
    }

    protected function render_heading(array $s): void
    {
        if (empty($s['title']) && empty($s['eyebrow'])) {
            return;
        }
        echo '<div class="section-heading"><div>';
        if (!empty($s['eyebrow'])) {
            echo '<span class="eyebrow">' . esc_html(bahia_t($s['eyebrow'])) . '</span>';
        }
        if (!empty($s['title'])) {
            echo '<h2 class="section-heading__title">' . esc_html(bahia_t($s['title'])) . '</h2>';
        }
        echo '</div></div>';
    }
}

/** Featured or filtered properties, as cards with favorites. */
class BahiaTheme_Featured_Widget extends BahiaTheme_Base_Widget
{
    public function get_name(): string { return 'bahia-featured'; }
    public function get_title(): string { return 'Biens à la une'; }
    public function get_icon(): string { return 'eicon-posts-grid'; }

    protected function register_controls(): void
    {
        $this->start_controls_section('content', ['label' => 'Contenu']);
        $this->heading_controls('Biens à la une', 'Sélection du moment');
        $this->add_control('count', ['label' => 'Nombre de biens', 'type' => Controls_Manager::NUMBER, 'min' => 1, 'max' => 12, 'default' => 6]);
        $this->add_control('source', ['label' => 'Biens affichés', 'type' => Controls_Manager::SELECT, 'default' => 'featured', 'options' => [
            'featured' => 'Biens à la une (puis les plus récents)',
            'latest' => 'Les plus récents',
        ]]);
        $this->add_control('transaction', ['label' => 'Transaction', 'type' => Controls_Manager::SELECT, 'default' => '', 'options' => ['' => 'Toutes'] + bahia_transaction_labels()]);
        $this->add_control('type', ['label' => 'Type de bien', 'type' => Controls_Manager::SELECT, 'default' => '', 'options' => ['' => 'Tous'] + bahia_type_labels()]);
        $this->add_control('show_link', ['label' => 'Lien « Voir tous les biens »', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->end_controls_section();
    }

    protected function render(): void
    {
        $s = $this->get_settings_for_display();
        $count = max(1, min(12, (int) ($s['count'] ?? 6)));
        $filtered = !empty($s['transaction']) || !empty($s['type']);
        if (($s['source'] ?? 'featured') === 'featured' && !$filtered) {
            $items = bahia_featured_properties($count);
        } else {
            $mq = [];
            if (!empty($s['transaction'])) $mq[] = ['key' => '_bahia_transaction', 'value' => $s['transaction']];
            if (!empty($s['type'])) $mq[] = ['key' => '_bahia_type', 'value' => $s['type']];
            $q = new WP_Query(['post_type' => 'property', 'posts_per_page' => $count, 'meta_query' => $mq, 'no_found_rows' => true]);
            $items = array_map('bahia_get_property', $q->posts);
        }
        echo '<div class="bahia-widget">';
        $this->render_heading($s);
        if ($items) {
            echo '<div class="property-grid">';
            foreach ($items as $p) {
                get_template_part('template-parts/property-card', null, ['p' => $p]);
            }
            echo '</div>';
        } else {
            echo '<p class="empty-state">' . esc_html(bahia_t('Aucun bien publié pour le moment.')) . '</p>';
        }
        if (($s['show_link'] ?? 'yes') === 'yes') {
            $url = get_post_type_archive_link('property');
            $url = add_query_arg(array_filter(['transaction' => $s['transaction'] ?? '', 'bien' => $s['type'] ?? '']), $url);
            echo '<p class="bahia-widget__more"><a class="link-arrow" href="' . esc_url($url) . '">' . esc_html(bahia_t('Voir tous les biens')) . ' ' . bahia_icon('arrow', 16, 'rtl-flip') . '</a></p>';
        }
        echo '</div>';
    }
}

/** Property search bar (sale / rent, type, wilaya, budget). */
class BahiaTheme_Search_Widget extends BahiaTheme_Base_Widget
{
    public function get_name(): string { return 'bahia-search'; }
    public function get_title(): string { return 'Recherche de biens'; }
    public function get_icon(): string { return 'eicon-search'; }

    protected function register_controls(): void
    {
        $this->start_controls_section('content', ['label' => 'Contenu']);
        $this->add_control('note', ['type' => Controls_Manager::RAW_HTML, 'raw' => 'La recherche envoie le visiteur vers la liste des biens filtrée.']);
        $this->end_controls_section();
    }

    protected function render(): void
    {
        echo '<div class="bahia-widget bahia-widget--search">';
        get_template_part('template-parts/search-bar');
        echo '</div>';
    }
}

/** Contact form: requests arrive in "Demandes" and by email, like the theme's contact page. */
class BahiaTheme_Contact_Widget extends BahiaTheme_Base_Widget
{
    public function get_name(): string { return 'bahia-contact'; }
    public function get_title(): string { return 'Formulaire de contact'; }
    public function get_icon(): string { return 'eicon-form-horizontal'; }

    protected function register_controls(): void
    {
        $this->start_controls_section('content', ['label' => 'Contenu']);
        $this->heading_controls('Parlez-nous de votre projet', 'Écrivez-nous');
        $this->add_control('subject', ['label' => 'Sujet proposé', 'type' => Controls_Manager::SELECT, 'default' => '', 'options' => ['' => 'Aucun (le visiteur choisit)'] + array_combine(bahia_subjects(), bahia_subjects())]);
        $this->end_controls_section();
    }

    protected function render(): void
    {
        $s = $this->get_settings_for_display();
        echo '<div class="bahia-widget bahia-widget--form" id="formulaire">';
        $this->render_heading($s);
        get_template_part('template-parts/contact-form', null, ['subject' => $s['subject'] ?? '']);
        echo '</div>';
    }
}

/** The three key figures of the agency settings. */
class BahiaTheme_Stats_Widget extends BahiaTheme_Base_Widget
{
    public function get_name(): string { return 'bahia-stats'; }
    public function get_title(): string { return 'Chiffres clés'; }
    public function get_icon(): string { return 'eicon-counter'; }

    protected function register_controls(): void
    {
        $this->start_controls_section('content', ['label' => 'Contenu']);
        $this->add_control('note', ['type' => Controls_Manager::RAW_HTML, 'raw' => 'Les chiffres se modifient dans Réglages agence › Page d’accueil.']);
        $this->end_controls_section();
    }

    protected function render(): void
    {
        echo '<div class="bahia-widget">';
        get_template_part('template-parts/stats');
        echo '</div>';
    }
}

/** "Vous avez un projet immobilier ?" banner with contact and WhatsApp buttons. */
class BahiaTheme_Cta_Widget extends BahiaTheme_Base_Widget
{
    public function get_name(): string { return 'bahia-cta'; }
    public function get_title(): string { return 'Bandeau contact'; }
    public function get_icon(): string { return 'eicon-call-to-action'; }

    protected function register_controls(): void
    {
        $this->start_controls_section('content', ['label' => 'Contenu']);
        $this->add_control('note', ['type' => Controls_Manager::RAW_HTML, 'raw' => 'Le numéro WhatsApp vient de Réglages agence.']);
        $this->end_controls_section();
    }

    protected function render(): void
    {
        echo '<div class="bahia-widget bahia-widget--cta">';
        get_template_part('template-parts/cta-banner');
        echo '</div>';
    }
}
