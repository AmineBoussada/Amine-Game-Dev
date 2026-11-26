/* French / Arabic strings. Static markup uses data-i18n="key" (text), data-i18n-ph (placeholder),
   data-i18n-aria (aria-label). Dynamic code uses TI.t(key, vars). Missing keys are recorded in TI.missing. */
window.TI = window.TI || {};
(function (TI) {
  var fr = {
    skip: 'Aller au contenu',
    logo_a: 'Tassili', logo_b: 'Immobilier', brand: 'Tassili Immobilier',
    nav_label: 'Navigation principale',
    nav_home: 'Accueil', nav_buy: 'Acheter', nav_rent: 'Louer', nav_new: 'Neuf', nav_about: 'L’agence', nav_contact: 'Contact',
    cta_list: 'Déposer mon bien',
    menu_open: 'Ouvrir le menu', menu_close: 'Fermer le menu', menu: 'Menu',
    theme_dark: 'Passer en mode sombre', theme_light: 'Passer en mode clair',
    lang_other: 'العربية', lang_aria: 'Afficher le site en arabe',
    favorites: 'Mes favoris', favorites_n: 'Mes favoris ({n})',
    wa_write: 'Écrire sur WhatsApp', wa_fab: 'Nous contacter sur WhatsApp', call: 'Appeler', call_agency: 'Appeler l’agence',
    footer_about: 'Agence immobilière à Alger depuis 2009. Vente, location et programmes neufs dans 7 wilayas, avec des papiers vérifiés avant chaque publication.',
    footer_links: 'Liens utiles', footer_wilayas: 'Nos wilayas', footer_contact: 'Nous contacter',
    footer_legal: 'Mentions légales', footer_privacy: 'Confidentialité',
    footer_rights: '© 2026 Tassili Immobilier · Agence fictive, données de démonstration.',
    social_fb: 'Facebook', social_ig: 'Instagram',

    cur: 'DA', per_month: '/ mois',
    milliard: '{n} milliard', milliards: '{n} milliards', millions: '{n} millions',
    tx_vente: 'Vente', tx_location: 'Location', badge_new: 'Neuf', badge_excl: 'Exclusivité',
    photos_n: '{n} photos', rooms_n: '{n} pièces', beds_n: '{n} ch.', baths_n: '{n} sdb', papers_ok: 'Acte notarié',
    delivery_d: 'Livraison {d}',
    see_property: 'Voir le bien', fav_add: 'Ajouter aux favoris', fav_remove: 'Retirer des favoris',
    fav_added: 'Ajouté à vos favoris', fav_removed: 'Retiré de vos favoris',
    wa_card: 'WhatsApp à propos du bien {ref}',
    ref: 'Réf.',

    wa_prop: 'Bonjour Tassili Immobilier, je suis intéressé(e) par le bien Réf. {ref} ({title}, {place}). Est-il toujours disponible ?',
    wa_general: 'Bonjour Tassili Immobilier, je souhaite avoir des informations.',
    wa_alert: 'Bonjour Tassili Immobilier, je cherche : {q}. Pouvez-vous me prévenir quand un bien correspond ?',
    wa_sell: 'Bonjour Tassili Immobilier, je souhaite faire estimer mon bien.',
    wa_visit: 'Bonjour, je souhaite visiter le bien Réf. {ref} ({title}).',

    s_label: 'Rechercher un bien', s_buy: 'Acheter', s_rent: 'Louer', s_new: 'Neuf',
    s_wilaya: 'Wilaya', s_all_wilayas: 'Toutes les wilayas', s_commune: 'Commune', s_all_communes: 'Toutes les communes',
    s_type: 'Type de bien', s_all_types: 'Tous les types', s_typo: 'Typologie', s_any: 'Indifférent',
    s_budget: 'Budget max (DA)', s_budget_ph: 'ex. 25 000 000', s_go: 'Rechercher',
    s_go_n: 'Voir {n} biens', s_go_1: 'Voir 1 bien', s_go_0: 'Aucun bien', s_more: '+ Plus de critères', s_less: '− Moins de critères',

    a_title_all: 'Tous nos biens', a_title_vente: 'Biens à vendre', a_title_location: 'Biens à louer', a_title_neuf: 'Programmes neufs', a_title_fav: 'Mes favoris',
    a_in: '{t} à {w}', a_count_n: '{n} biens', a_count_1: '1 bien', a_count_0: 'Aucun bien',
    bc_label: 'Fil d’Ariane', bc_home: 'Accueil', bc_props: 'Biens',
    f_title: 'Filtres', f_open: 'Filtres', f_open_n: 'Filtres ({n})', f_close: 'Fermer les filtres',
    f_tx: 'Transaction', f_all: 'Tout', f_price: 'Prix (DA)', f_min: 'Min', f_max: 'Max',
    f_surface: 'Surface minimum (m²)', f_papers: 'Papiers', f_amen: 'Équipements', f_reset: 'Réinitialiser',
    f_apply_n: 'Voir {n} biens', f_apply_1: 'Voir 1 bien', f_apply_0: 'Aucun bien',
    sort_label: 'Trier par', sort_recent: 'Plus récents', sort_price_asc: 'Prix croissant', sort_price_desc: 'Prix décroissant', sort_surface: 'Plus grande surface',
    view_label: 'Affichage', view_grid: 'Grille', view_list: 'Liste',
    chip_remove: 'Retirer le filtre : {f}', clear_all: 'Tout effacer',
    chip_budget_max: 'Jusqu’à {n} DA', chip_budget_min: 'À partir de {n} DA', chip_surface: '{n} m² et plus', chip_new: 'Neuf', chip_fav: 'Favoris',
    empty_title: 'Aucun bien ne correspond à votre recherche',
    empty_text: 'Élargissez vos critères, ou laissez-nous vous prévenir dès qu’un bien correspond.',
    empty_fav_title: 'Pas encore de favoris',
    empty_fav: 'Touchez le cœur sur un bien pour le retrouver ici, sur cet appareil.',
    alert_title: 'Recevez les nouveaux biens sur WhatsApp',
    alert_text: 'Nous vous envoyons les biens qui correspondent à cette recherche, rien d’autre.',
    alert_btn: 'Créer une alerte',
    page_label: 'Pagination', page_prev: 'Page précédente', page_next: 'Page suivante', page_n: 'Page {n}',
    all_props: 'Tous les biens',

    p_notfound_title: 'Ce bien n’est plus disponible', p_notfound_text: 'Il a peut-être été vendu ou loué. Découvrez nos autres biens.', p_back: 'Voir tous les biens',
    g_label: 'Photos du bien', g_prev: 'Photo précédente', g_next: 'Photo suivante', g_open: 'Agrandir la photo', g_close: 'Fermer', g_thumb: 'Photo {i} : {c}', g_more: '+{n}',
    p_facts: 'Caractéristiques', fact_surface: 'Surface', fact_land: 'Terrain', fact_typo: 'Typologie', fact_rooms: 'Pièces', fact_beds: 'Chambres',
    fact_baths: 'Salles d’eau', fact_floor: 'Étage', fact_floors: 'Niveaux', fact_year: 'Construction', fact_delivery: 'Livraison',
    floor_rdc: 'Rez-de-chaussée', floor_n: '{n}e étage', floor_1: '1er étage',
    p_desc: 'Description', read_more: 'Lire la suite', read_less: 'Réduire',
    p_papers: 'Papiers et situation juridique', papers_note: 'Documents vus par notre service juridique avant la publication.',
    paper_yes: 'Disponible', paper_no: 'Non fourni',
    p_amen: 'Équipements', p_location: 'Localisation',
    p_location_text: '{commune}, wilaya de {wilaya}. L’adresse exacte est communiquée lors de la visite.',
    p_map: 'Voir le quartier sur Google Maps',
    p_agent: 'Votre conseiller', agent_reply: 'Répond en général en moins de 15 min',
    p_visit: 'Planifier une visite', p_share: 'Partager', share_copied: 'Lien copié', share_fail: 'Copiez le lien depuis la barre d’adresse',
    p_similar: 'Biens similaires', nav_visit_short: 'Visite', p_print_ref: 'Référence {ref}',

    v_title: 'Planifier une visite', v_intro: 'Choisissez un jour et un créneau. Votre conseiller vous rappelle pour confirmer.',
    v_date: 'Date souhaitée', v_slot: 'Créneau', slot_am: 'Matin (9h – 12h)', slot_pm: 'Après-midi (13h – 17h)',
    v_send: 'Envoyer la demande', v_success_title: 'Demande envoyée',
    v_success: 'Merci {name}. {agent} vous rappelle au {phone} pour confirmer votre visite du {date}.',
    v_close: 'Fermer',

    f_name: 'Nom complet', f_phone: 'Téléphone', f_phone_ph: '0555 12 34 56', f_email: 'E-mail (facultatif)', f_email_ph: 'nom@exemple.dz',
    f_subject: 'Objet', subj_buy: 'Acheter un bien', subj_rent: 'Louer un bien', subj_sell: 'Vendre ou louer mon bien', subj_estimate: 'Faire estimer mon bien', subj_other: 'Autre question',
    f_ref: 'Référence du bien (facultatif)', f_ref_ph: 'ex. TS-1024',
    f_message: 'Message', f_message_ph: 'Décrivez votre projet : wilaya, budget, délai…',
    f_consent: 'J’accepte d’être recontacté(e) au sujet de ma demande.',
    f_send: 'Envoyer le message', sending: 'Envoi…',
    err_required: 'Ce champ est obligatoire.', err_name: 'Entrez au moins 2 lettres.',
    err_phone: 'Entrez un numéro algérien, par exemple 0555 12 34 56.', err_email: 'Entrez une adresse e-mail valide, par exemple nom@exemple.dz.',
    err_consent: 'Cochez cette case pour que nous puissions vous répondre.', err_date: 'Choisissez une date à partir de demain.',
    err_friday: 'L’agence est fermée le vendredi. Choisissez un autre jour.', err_message: 'Écrivez au moins 10 caractères.',
    err_summary: 'Vérifiez les champs signalés en rouge.',
    form_success_title: 'Message envoyé', form_success: 'Merci {name}. Nous vous répondons sous 24 h ouvrées au {phone}.', form_again: 'Envoyer un autre message',
    demo_note: 'Démonstration : aucune donnée n’est envoyée.',

    meta_home: 'Tassili Immobilier · Vente et location en Algérie',
    meta_about: 'L’agence · Tassili Immobilier', meta_contact: 'Contact · Tassili Immobilier', meta_suffix: ' · Tassili Immobilier',

    h_eyebrow: 'Agence immobilière · 7 wilayas',
    h_title: 'Trouvez le bien qui vous ressemble, papiers en règle',
    h_lede: 'Appartements, villas, terrains et programmes neufs, vérifiés par notre équipe, d’Alger à Oran.',
    trust1: 'Papiers vérifiés avant publication', trust2: '1 240 biens vendus depuis 2009', trust3: 'Réponse WhatsApp en 15 min',
    sec_wilayas: 'Parcourir par wilaya', sec_wilayas_sub: 'Nos conseillers connaissent chaque quartier.',
    sec_featured: 'Biens à la une', sec_featured_sub: 'Sélectionnés cette semaine par nos conseillers.', see_all: 'Voir tous les biens',
    sec_types: 'Que cherchez-vous ?',
    sec_why: 'Pourquoi Tassili Immobilier',
    why1_t: 'Papiers vérifiés', why1: 'Acte, livret foncier et permis contrôlés avant chaque publication.',
    why2_t: 'Visites organisées', why2: 'Un conseiller vous accompagne, aux horaires qui vous arrangent.',
    why3_t: 'Jusqu’au notaire', why3: 'Nous préparons le dossier et vous accompagnons jusqu’à la signature.',
    why4_t: 'Réponse rapide', why4: 'Sur WhatsApp ou par téléphone, en arabe ou en français.',
    sec_new: 'Programmes neufs', sec_new_sub: 'Achetez sur plan avec un paiement échelonné.',
    sec_testi: 'Ils nous ont fait confiance',
    fig1: 'biens vendus', fig2: 'ans d’expérience', fig3: 'wilayas couvertes', fig4: 'de clients satisfaits',
    t1: 'Nous avons trouvé notre F4 à Bab Ezzouar en trois semaines. Les papiers étaient déjà vérifiés, la signature chez le notaire s’est faite sans surprise.',
    t1_who: 'Amel et Riad', t1_where: 'Acheteurs, Alger',
    t2: 'J’ai loué mon local d’El Biar en un mois. Yasmine m’envoyait le compte rendu de chaque visite sur WhatsApp.',
    t2_who: 'Mourad', t2_where: 'Propriétaire, El Biar',
    t3: 'Installés à l’étranger, nous avons acheté à Tichy sans multiplier les allers-retours. Visite vidéo, dossier complet : tout était clair.',
    t3_who: 'Sofiane et Lila', t3_where: 'Acheteurs, Lyon',
    sell_title: 'Vous vendez ou louez un bien ?',
    sell_text: 'Estimation gratuite sous 48 h, photos professionnelles et diffusion auprès d’acheteurs déjà qualifiés.',
    sell_cta: 'Estimer mon bien', wil_count_n: '{n} biens', wil_count_1: '1 bien', type_count_n: '{n} annonces', type_count_1: '1 annonce',

    ab_eyebrow: 'L’agence', ab_title: 'Une agence algérienne, des conseillers de terrain',
    ab_lede: 'Fondée en 2009 à El Biar, Tassili Immobilier accompagne acheteurs, vendeurs et locataires dans sept wilayas. Notre règle n’a pas changé : ne publier que des biens dont nous avons vu les papiers.',
    ab_method: 'Notre méthode', ab_method_sub: 'Les mêmes étapes pour chaque bien, de l’estimation à la remise des clés.',
    m1_t: 'Estimation', m1: 'Visite du bien et prix fondé sur les ventes récentes du quartier.',
    m2_t: 'Vérification des papiers', m2: 'Acte, livret foncier, permis et situation de copropriété.',
    m3_t: 'Visites', m3: 'Visites organisées, compte rendu envoyé sur WhatsApp.',
    m4_t: 'Notaire', m4: 'Préparation du dossier et rendez-vous chez le notaire.',
    m5_t: 'Remise des clés', m5: 'État des lieux et transfert des compteurs.',
    ab_team: 'L’équipe', ab_team_sub: 'Un interlocuteur unique du premier appel à la signature.',
    ab_legal: 'Informations légales', ab_reg: 'Registre du commerce', ab_nif: 'Identifiant fiscal', ab_office: 'Siège',
    ab_privacy_t: 'Confidentialité', ab_privacy: 'Les informations envoyées par nos formulaires servent uniquement à répondre à votre demande. Les favoris restent enregistrés sur votre appareil.',
    ab_cta_title: 'Rencontrez-nous à l’agence', ab_cta_text: 'Sans rendez-vous du dimanche au jeudi, ou sur rendez-vous le samedi matin.', ab_cta_btn: 'Nous contacter',
    langs_spoken: 'Langues : {l}', member_wa: 'WhatsApp',

    ct_eyebrow: 'Contact', ct_title: 'Parlons de votre projet',
    ct_lede: 'Le plus rapide, c’est WhatsApp. Vous pouvez aussi nous appeler ou remplir le formulaire.',
    ch_wa_t: 'WhatsApp', ch_wa: 'Réponse en moins de 15 min aux heures d’ouverture', ch_call_t: 'Téléphone', ch_mail_t: 'E-mail',
    ct_form: 'Envoyer un message', ct_form_sub: 'Réponse sous 24 h ouvrées.',
    ct_office: 'Notre agence', ct_address: 'Adresse', ct_hours: 'Horaires', ct_map: 'Ouvrir dans Google Maps',
    faq_title: 'Questions fréquentes',
    faq1_q: 'Quels sont vos frais d’agence ?', faq1_a: 'Pour une vente, 1 % du prix à la charge de chaque partie. Pour une location, l’équivalent d’un mois de loyer.',
    faq2_q: 'Les papiers sont-ils vérifiés ?', faq2_a: 'Oui. Nous demandons l’acte notarié et, quand il existe, le livret foncier avant de publier un bien.',
    faq3_q: 'Puis-je visiter le week-end ?', faq3_a: 'Les visites ont lieu du dimanche au jeudi et le samedi matin. L’agence est fermée le vendredi.',
    faq4_q: 'J’habite à l’étranger, comment faire ?', faq4_a: 'Nous organisons des visites vidéo et préparons une procuration avec le notaire si besoin.',

    shot_facade: 'Façade', shot_residence: 'Résidence', shot_salon: 'Séjour', shot_cuisine: 'Cuisine', shot_chambre: 'Chambre', shot_sdb: 'Salle de bain',
    shot_terrasse: 'Terrasse', shot_jardin: 'Jardin', shot_vue: 'Vue', shot_terrain: 'Terrain', shot_acces: 'Accès', shot_interieur: 'Intérieur', shot_vitrine: 'Vitrine'
  };

  var ar = {
    skip: 'انتقل إلى المحتوى',
    logo_a: 'تاسيلي', logo_b: 'للعقار', brand: 'تاسيلي للعقار',
    nav_label: 'التنقل الرئيسي',
    nav_home: 'الرئيسية', nav_buy: 'شراء', nav_rent: 'كراء', nav_new: 'جديد', nav_about: 'الوكالة', nav_contact: 'اتصل بنا',
    cta_list: 'اعرض عقارك',
    menu_open: 'فتح القائمة', menu_close: 'إغلاق القائمة', menu: 'القائمة',
    theme_dark: 'التبديل إلى الوضع الداكن', theme_light: 'التبديل إلى الوضع الفاتح',
    lang_other: 'Français', lang_aria: 'عرض الموقع بالفرنسية',
    favorites: 'مفضلتي', favorites_n: 'مفضلتي ({n})',
    wa_write: 'راسلنا على واتساب', wa_fab: 'تواصل معنا عبر واتساب', call: 'اتصال', call_agency: 'اتصل بالوكالة',
    footer_about: 'وكالة عقارية في الجزائر العاصمة منذ 2009. بيع وكراء ومشاريع جديدة في 7 ولايات، بوثائق نتحقق منها قبل كل نشر.',
    footer_links: 'روابط مفيدة', footer_wilayas: 'ولاياتنا', footer_contact: 'تواصل معنا',
    footer_legal: 'الإشعارات القانونية', footer_privacy: 'الخصوصية',
    footer_rights: '© 2026 تاسيلي للعقار · وكالة وهمية وبيانات تجريبية.',
    social_fb: 'فيسبوك', social_ig: 'إنستغرام',

    cur: 'دج', per_month: '/ شهريًا',
    milliard: '{n} مليار سنتيم', milliards: '{n} مليار سنتيم', millions: '{n} مليون سنتيم',
    tx_vente: 'بيع', tx_location: 'كراء', badge_new: 'جديد', badge_excl: 'حصري',
    photos_n: '{n} صور', rooms_n: '{n} غرف', beds_n: '{n} غرف نوم', baths_n: '{n} حمام', papers_ok: 'عقد موثق',
    delivery_d: 'التسليم {d}',
    see_property: 'عرض العقار', fav_add: 'أضف إلى المفضلة', fav_remove: 'إزالة من المفضلة',
    fav_added: 'أضيف إلى مفضلتك', fav_removed: 'أزيل من مفضلتك',
    wa_card: 'واتساب بخصوص العقار {ref}',
    ref: 'المرجع',

    wa_prop: 'السلام عليكم، أنا مهتم بالعقار المرجع {ref} ({title}، {place}). هل ما زال متاحًا؟',
    wa_general: 'السلام عليكم، أريد الحصول على بعض المعلومات.',
    wa_alert: 'السلام عليكم، أبحث عن: {q}. هل يمكنكم إعلامي عند توفر عقار مناسب؟',
    wa_sell: 'السلام عليكم، أريد تقييم عقاري.',
    wa_visit: 'السلام عليكم، أريد زيارة العقار المرجع {ref} ({title}).',

    s_label: 'البحث عن عقار', s_buy: 'شراء', s_rent: 'كراء', s_new: 'جديد',
    s_wilaya: 'الولاية', s_all_wilayas: 'كل الولايات', s_commune: 'البلدية', s_all_communes: 'كل البلديات',
    s_type: 'نوع العقار', s_all_types: 'كل الأنواع', s_typo: 'النمط', s_any: 'الكل',
    s_budget: 'الميزانية القصوى (دج)', s_budget_ph: 'مثال: 25.000.000', s_go: 'بحث',
    s_go_n: 'عرض {n} عقارات', s_go_1: 'عرض عقار واحد', s_go_0: 'لا يوجد عقار', s_more: '+ معايير إضافية', s_less: '− معايير أقل',

    a_title_all: 'كل عقاراتنا', a_title_vente: 'عقارات للبيع', a_title_location: 'عقارات للكراء', a_title_neuf: 'مشاريع جديدة', a_title_fav: 'مفضلتي',
    a_in: '{t} في {w}', a_count_n: '{n} عقارات', a_count_1: 'عقار واحد', a_count_0: 'لا يوجد عقار',
    bc_label: 'مسار التنقل', bc_home: 'الرئيسية', bc_props: 'العقارات',
    f_title: 'الفلاتر', f_open: 'الفلاتر', f_open_n: 'الفلاتر ({n})', f_close: 'إغلاق الفلاتر',
    f_tx: 'نوع المعاملة', f_all: 'الكل', f_price: 'السعر (دج)', f_min: 'الأدنى', f_max: 'الأقصى',
    f_surface: 'المساحة الدنيا (م²)', f_papers: 'الوثائق', f_amen: 'التجهيزات', f_reset: 'إعادة الضبط',
    f_apply_n: 'عرض {n} عقارات', f_apply_1: 'عرض عقار واحد', f_apply_0: 'لا يوجد عقار',
    sort_label: 'ترتيب حسب', sort_recent: 'الأحدث', sort_price_asc: 'السعر: من الأقل', sort_price_desc: 'السعر: من الأعلى', sort_surface: 'المساحة الأكبر',
    view_label: 'طريقة العرض', view_grid: 'شبكة', view_list: 'قائمة',
    chip_remove: 'إزالة الفلتر: {f}', clear_all: 'مسح الكل',
    chip_budget_max: 'حتى {n} دج', chip_budget_min: 'ابتداءً من {n} دج', chip_surface: '{n} م² فأكثر', chip_new: 'جديد', chip_fav: 'المفضلة',
    empty_title: 'لا يوجد عقار يطابق بحثك',
    empty_text: 'وسّع معايير البحث، أو دعنا نعلمك فور توفر عقار مناسب.',
    empty_fav_title: 'لا توجد مفضلات بعد',
    empty_fav: 'اضغط على القلب في أي عقار لتجده هنا على هذا الجهاز.',
    alert_title: 'استقبل العقارات الجديدة على واتساب',
    alert_text: 'نرسل لك العقارات المطابقة لهذا البحث فقط.',
    alert_btn: 'إنشاء تنبيه',
    page_label: 'ترقيم الصفحات', page_prev: 'الصفحة السابقة', page_next: 'الصفحة التالية', page_n: 'الصفحة {n}',
    all_props: 'كل العقارات',

    p_notfound_title: 'هذا العقار لم يعد متاحًا', p_notfound_text: 'ربما تم بيعه أو كراؤه. اكتشف عقاراتنا الأخرى.', p_back: 'عرض كل العقارات',
    g_label: 'صور العقار', g_prev: 'الصورة السابقة', g_next: 'الصورة التالية', g_open: 'تكبير الصورة', g_close: 'إغلاق', g_thumb: 'الصورة {i}: {c}', g_more: '+{n}',
    p_facts: 'المواصفات', fact_surface: 'المساحة', fact_land: 'الأرض', fact_typo: 'النمط', fact_rooms: 'الغرف', fact_beds: 'غرف النوم',
    fact_baths: 'الحمامات', fact_floor: 'الطابق', fact_floors: 'الطوابق', fact_year: 'سنة البناء', fact_delivery: 'التسليم',
    floor_rdc: 'الطابق الأرضي', floor_n: 'الطابق {n}', floor_1: 'الطابق 1',
    p_desc: 'الوصف', read_more: 'اقرأ المزيد', read_less: 'عرض أقل',
    p_papers: 'الوثائق والوضعية القانونية', papers_note: 'وثائق اطلع عليها قسمنا القانوني قبل النشر.',
    paper_yes: 'متوفر', paper_no: 'غير مقدم',
    p_amen: 'التجهيزات', p_location: 'الموقع',
    p_location_text: '{commune}، ولاية {wilaya}. يُعطى العنوان الدقيق أثناء الزيارة.',
    p_map: 'عرض الحي على خرائط Google',
    p_agent: 'مستشارك', agent_reply: 'يرد عادة في أقل من 15 دقيقة',
    p_visit: 'حجز موعد زيارة', p_share: 'مشاركة', share_copied: 'تم نسخ الرابط', share_fail: 'انسخ الرابط من شريط العنوان',
    p_similar: 'عقارات مشابهة', nav_visit_short: 'زيارة', p_print_ref: 'المرجع {ref}',

    v_title: 'حجز موعد زيارة', v_intro: 'اختر يومًا وفترة، وسيتصل بك مستشارك للتأكيد.',
    v_date: 'التاريخ المفضل', v_slot: 'الفترة', slot_am: 'صباحًا (9:00 – 12:00)', slot_pm: 'مساءً (13:00 – 17:00)',
    v_send: 'إرسال الطلب', v_success_title: 'تم إرسال الطلب',
    v_success: 'شكرًا {name}. سيتصل بك {agent} على الرقم {phone} لتأكيد زيارتك يوم {date}.',
    v_close: 'إغلاق',

    f_name: 'الاسم الكامل', f_phone: 'الهاتف', f_phone_ph: '0555 12 34 56', f_email: 'البريد الإلكتروني (اختياري)', f_email_ph: 'name@example.dz',
    f_subject: 'الموضوع', subj_buy: 'شراء عقار', subj_rent: 'كراء عقار', subj_sell: 'بيع أو كراء عقاري', subj_estimate: 'تقييم عقاري', subj_other: 'سؤال آخر',
    f_ref: 'مرجع العقار (اختياري)', f_ref_ph: 'مثال: TS-1024',
    f_message: 'الرسالة', f_message_ph: 'صف مشروعك: الولاية، الميزانية، المدة…',
    f_consent: 'أوافق على أن يتم الاتصال بي بخصوص طلبي.',
    f_send: 'إرسال الرسالة', sending: 'جارٍ الإرسال…',
    err_required: 'هذا الحقل إجباري.', err_name: 'أدخل حرفين على الأقل.',
    err_phone: 'أدخل رقمًا جزائريًا، مثل 0555 12 34 56.', err_email: 'أدخل بريدًا إلكترونيًا صحيحًا، مثل name@example.dz.',
    err_consent: 'ضع علامة في هذا المربع حتى نتمكن من الرد عليك.', err_date: 'اختر تاريخًا ابتداءً من الغد.',
    err_friday: 'الوكالة مغلقة يوم الجمعة. اختر يومًا آخر.', err_message: 'اكتب 10 أحرف على الأقل.',
    err_summary: 'تحقق من الحقول المحددة باللون الأحمر.',
    form_success_title: 'تم إرسال رسالتك', form_success: 'شكرًا {name}. سنرد عليك خلال 24 ساعة عمل على الرقم {phone}.', form_again: 'إرسال رسالة أخرى',
    demo_note: 'نسخة تجريبية: لا يتم إرسال أي بيانات.',

    meta_home: 'تاسيلي للعقار · بيع وكراء في الجزائر',
    meta_about: 'الوكالة · تاسيلي للعقار', meta_contact: 'اتصل بنا · تاسيلي للعقار', meta_suffix: ' · تاسيلي للعقار',

    h_eyebrow: 'وكالة عقارية · 7 ولايات',
    h_title: 'اعثر على العقار الذي يناسبك، بوثائق سليمة',
    h_lede: 'شقق، فيلات، أراضٍ ومشاريع جديدة، تحقق منها فريقنا، من الجزائر العاصمة إلى وهران.',
    trust1: 'وثائق مُتحقق منها قبل النشر', trust2: '1240 عقارًا مبيعًا منذ 2009', trust3: 'رد عبر واتساب خلال 15 دقيقة',
    sec_wilayas: 'تصفح حسب الولاية', sec_wilayas_sub: 'مستشارونا يعرفون كل حي.',
    sec_featured: 'عقارات مميزة', sec_featured_sub: 'اختارها مستشارونا هذا الأسبوع.', see_all: 'عرض كل العقارات',
    sec_types: 'عن ماذا تبحث؟',
    sec_why: 'لماذا تاسيلي للعقار',
    why1_t: 'وثائق مُتحقق منها', why1: 'نتحقق من العقد والدفتر العقاري والرخصة قبل كل نشر.',
    why2_t: 'زيارات منظمة', why2: 'يرافقك مستشار في الأوقات التي تناسبك.',
    why3_t: 'حتى الموثق', why3: 'نحضّر الملف ونرافقك حتى التوقيع.',
    why4_t: 'رد سريع', why4: 'عبر واتساب أو الهاتف، بالعربية أو الفرنسية.',
    sec_new: 'مشاريع جديدة', sec_new_sub: 'اشترِ على المخطط مع الدفع بالتقسيط.',
    sec_testi: 'وثقوا بنا',
    fig1: 'عقار مبيع', fig2: 'سنة من الخبرة', fig3: 'ولايات مغطاة', fig4: 'من العملاء راضون',
    t1: 'وجدنا شقتنا F4 في باب الزوار خلال ثلاثة أسابيع. كانت الوثائق مُتحققًا منها مسبقًا، وتم التوقيع عند الموثق دون مفاجآت.',
    t1_who: 'أمال ورياض', t1_where: 'مشتريان، الجزائر',
    t2: 'أجّرت محلي في الأبيار خلال شهر. كانت ياسمين ترسل لي تقرير كل زيارة على واتساب.',
    t2_who: 'مراد', t2_where: 'مالك، الأبيار',
    t3: 'ونحن مقيمون في الخارج، اشترينا في تيشي دون كثرة التنقل. زيارة بالفيديو وملف كامل، كان كل شيء واضحًا.',
    t3_who: 'سفيان وليلى', t3_where: 'مشتريان، ليون',
    sell_title: 'تبيع أو تؤجر عقارًا؟',
    sell_text: 'تقييم مجاني خلال 48 ساعة، صور احترافية ونشر لدى مشترين جادين.',
    sell_cta: 'قيّم عقارك', wil_count_n: '{n} عقارات', wil_count_1: 'عقار واحد', type_count_n: '{n} إعلانات', type_count_1: 'إعلان واحد',

    ab_eyebrow: 'الوكالة', ab_title: 'وكالة جزائرية بمستشارين ميدانيين',
    ab_lede: 'تأسست تاسيلي للعقار سنة 2009 في الأبيار، وترافق المشترين والبائعين والمستأجرين في سبع ولايات. قاعدتنا لم تتغير: لا ننشر إلا العقارات التي اطلعنا على وثائقها.',
    ab_method: 'طريقتنا', ab_method_sub: 'نفس المراحل لكل عقار، من التقييم إلى تسليم المفاتيح.',
    m1_t: 'التقييم', m1: 'زيارة العقار وتحديد سعر مبني على المبيعات الأخيرة في الحي.',
    m2_t: 'التحقق من الوثائق', m2: 'العقد، الدفتر العقاري، الرخصة ووضعية الملكية المشتركة.',
    m3_t: 'الزيارات', m3: 'زيارات منظمة وتقرير يرسل عبر واتساب.',
    m4_t: 'الموثق', m4: 'تحضير الملف والموعد عند الموثق.',
    m5_t: 'تسليم المفاتيح', m5: 'معاينة حالة العقار ونقل العدادات.',
    ab_team: 'الفريق', ab_team_sub: 'محاور واحد من أول اتصال حتى التوقيع.',
    ab_legal: 'معلومات قانونية', ab_reg: 'السجل التجاري', ab_nif: 'رقم التعريف الجبائي', ab_office: 'المقر',
    ab_privacy_t: 'الخصوصية', ab_privacy: 'تُستعمل المعلومات المرسلة عبر استماراتنا فقط للرد على طلبك. وتبقى المفضلة محفوظة على جهازك.',
    ab_cta_title: 'زورونا في الوكالة', ab_cta_text: 'بدون موعد من الأحد إلى الخميس، أو بموعد صباح السبت.', ab_cta_btn: 'اتصل بنا',
    langs_spoken: 'اللغات: {l}', member_wa: 'واتساب',

    ct_eyebrow: 'اتصل بنا', ct_title: 'لنتحدث عن مشروعك',
    ct_lede: 'الأسرع هو واتساب. يمكنك أيضًا الاتصال بنا أو ملء الاستمارة.',
    ch_wa_t: 'واتساب', ch_wa: 'رد في أقل من 15 دقيقة خلال ساعات العمل', ch_call_t: 'الهاتف', ch_mail_t: 'البريد الإلكتروني',
    ct_form: 'أرسل رسالة', ct_form_sub: 'نرد خلال 24 ساعة عمل.',
    ct_office: 'وكالتنا', ct_address: 'العنوان', ct_hours: 'ساعات العمل', ct_map: 'فتح في خرائط Google',
    faq_title: 'أسئلة شائعة',
    faq1_q: 'ما هي أتعاب الوكالة؟', faq1_a: 'في البيع، 1% من السعر على كل طرف. في الكراء، ما يعادل شهر كراء.',
    faq2_q: 'هل يتم التحقق من الوثائق؟', faq2_a: 'نعم. نطلب العقد الموثق والدفتر العقاري إن وجد قبل نشر أي عقار.',
    faq3_q: 'هل يمكنني الزيارة في عطلة نهاية الأسبوع؟', faq3_a: 'تتم الزيارات من الأحد إلى الخميس وصباح السبت. الوكالة مغلقة يوم الجمعة.',
    faq4_q: 'أقيم في الخارج، كيف أتصرف؟', faq4_a: 'ننظم زيارات بالفيديو ونحضّر وكالة لدى الموثق عند الحاجة.',

    shot_facade: 'الواجهة', shot_residence: 'الإقامة', shot_salon: 'الصالون', shot_cuisine: 'المطبخ', shot_chambre: 'غرفة النوم', shot_sdb: 'الحمام',
    shot_terrasse: 'التراس', shot_jardin: 'الحديقة', shot_vue: 'الإطلالة', shot_terrain: 'الأرض', shot_acces: 'المدخل', shot_interieur: 'الداخل', shot_vitrine: 'الواجهة الزجاجية'
  };

  TI.dict = { fr: fr, ar: ar };
  TI.missing = [];
  TI.lang = function () { return document.documentElement.lang === 'ar' ? 'ar' : 'fr'; };
  TI.t = function (key, vars) {
    var l = TI.lang(), s = TI.dict[l][key];
    if (s == null) {
      if (TI.missing.indexOf(l + ':' + key) < 0) TI.missing.push(l + ':' + key);
      s = TI.dict.fr[key] != null ? TI.dict.fr[key] : key;
    }
    if (vars) s = s.replace(/\{(\w+)\}/g, function (m, v) { return vars[v] != null ? vars[v] : m; });
    return s;
  };
  /* Localised field of a data object: {fr, ar} */
  TI.L = function (o) { return o ? (o[TI.lang()] != null ? o[TI.lang()] : o.fr) : ''; };
  TI.plural = function (base, n) { return TI.t(base + (n === 0 && TI.dict.fr[base + '0'] ? '0' : n === 1 ? '1' : 'n'), { n: TI.num(n) }); };

  TI.applyI18n = function (root) {
    root = root || document;
    TI.$$('[data-i18n]', root).forEach(function (el) { el.textContent = TI.t(el.getAttribute('data-i18n')); });
    TI.$$('[data-i18n-ph]', root).forEach(function (el) { el.setAttribute('placeholder', TI.t(el.getAttribute('data-i18n-ph'))); });
    TI.$$('[data-i18n-aria]', root).forEach(function (el) { el.setAttribute('aria-label', TI.t(el.getAttribute('data-i18n-aria'))); });
    TI.$$('[data-icon]', root).forEach(function (el) { if (!el.firstChild) el.innerHTML = TI.icon(el.getAttribute('data-icon'), el.getAttribute('data-icon-class') || ''); });
  };

  TI.setLang = function (l) {
    var d = document.documentElement;
    d.lang = l; d.dir = l === 'ar' ? 'rtl' : 'ltr';
    try { localStorage.setItem('ti-lang', l); } catch (e) {}
    var p = TI.params();
    if (p.has('lang')) { p.delete('lang'); var q = p.toString(); history.replaceState(null, '', location.pathname + (q ? '?' + q : '') + location.hash); }
    TI.applyI18n();
    document.dispatchEvent(new CustomEvent('ti:lang', { detail: { lang: l } }));
  };
})(window.TI);
