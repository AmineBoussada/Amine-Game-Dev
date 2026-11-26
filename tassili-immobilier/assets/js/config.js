/* Agency settings and reference lists. Tassili Immobilier is fictional: replace the phone
   numbers, WhatsApp number, e-mail and address with the real agency's before going live. */
window.TI = window.TI || {};
(function (TI) {
  TI.config = {
    phone: '0555 00 00 00',
    phoneHref: '+213555000000',
    whatsapp: '213555000000',
    email: 'contact@tassili-immobilier.dz',
    address: { fr: 'Lot 12, Cité des Pins, El Biar, Alger', ar: 'التجزئة 12، حي الصنوبر، الأبيار، الجزائر' },
    mapQuery: 'El Biar, Alger, Algérie',
    hours: { fr: 'Dim – Jeu · 8h30 – 17h30', ar: 'الأحد – الخميس · 8:30 – 17:30' },
    saturday: { fr: 'Samedi · 9h – 13h (sur rendez-vous)', ar: 'السبت · 9:00 – 13:00 (بموعد)' },
    friday: { fr: 'Vendredi · fermé', ar: 'الجمعة · مغلق' },
    rc: 'RC 16/00-0000000 B 09',
    nif: 'NIF 0000 1600 0000 000',
    founded: 2009,
    facebook: 'https://www.facebook.com/',
    instagram: 'https://www.instagram.com/'
  };

  TI.agents = [
    { id: 'yasmine', name: 'Yasmine Benali', role: { fr: 'Conseillère senior, Alger', ar: 'مستشارة أولى، الجزائر' }, phone: '0555 00 00 01', whatsapp: '213555000001', langs: { fr: 'arabe, français, anglais', ar: 'العربية، الفرنسية، الإنجليزية' } },
    { id: 'karim', name: 'Karim Haddad', role: { fr: 'Conseiller, Oran', ar: 'مستشار، وهران' }, phone: '0555 00 00 02', whatsapp: '213555000002', langs: { fr: 'arabe, français', ar: 'العربية، الفرنسية' } },
    { id: 'nadia', name: 'Nadia Mansouri', role: { fr: 'Conseillère, Est algérien', ar: 'مستشارة، الشرق الجزائري' }, phone: '0555 00 00 03', whatsapp: '213555000003', langs: { fr: 'arabe, français, tamazight', ar: 'العربية، الفرنسية، الأمازيغية' } },
    { id: 'samir', name: 'Samir Belkacem', role: { fr: 'Directeur d’agence', ar: 'مدير الوكالة' }, phone: '0555 00 00 04', whatsapp: '213555000004', langs: { fr: 'arabe, français, anglais', ar: 'العربية، الفرنسية، الإنجليزية' } }
  ];

  function c(id, fr, ar) { return { id: id, fr: fr, ar: ar }; }
  TI.wilayas = [
    { code: 16, fr: 'Alger', ar: 'الجزائر', coast: true, communes: [c('hydra', 'Hydra', 'حيدرة'), c('el-biar', 'El Biar', 'الأبيار'), c('ben-aknoun', 'Ben Aknoun', 'بن عكنون'), c('cheraga', 'Chéraga', 'الشراقة'), c('dely-ibrahim', 'Dely Ibrahim', 'دالي إبراهيم'), c('bab-ezzouar', 'Bab Ezzouar', 'باب الزوار')] },
    { code: 31, fr: 'Oran', ar: 'وهران', coast: true, communes: [c('akid-lotfi', 'Akid Lotfi', 'العقيد لطفي'), c('canastel', 'Canastel', 'كناستال'), c('bir-el-djir', 'Bir El Djir', 'بئر الجير')] },
    { code: 25, fr: 'Constantine', ar: 'قسنطينة', coast: false, communes: [c('ali-mendjeli', 'Ali Mendjeli', 'علي منجلي'), c('sidi-mabrouk', 'Sidi Mabrouk', 'سيدي مبروك')] },
    { code: 42, fr: 'Tipaza', ar: 'تيبازة', coast: true, communes: [c('cherchell', 'Cherchell', 'شرشال'), c('bou-ismail', 'Bou Ismaïl', 'بو إسماعيل')] },
    { code: 23, fr: 'Annaba', ar: 'عنابة', coast: true, communes: [c('seraidi', 'Seraïdi', 'سرايدي')] },
    { code: 6, fr: 'Béjaïa', ar: 'بجاية', coast: true, communes: [c('tichy', 'Tichy', 'تيشي')] },
    { code: 13, fr: 'Tlemcen', ar: 'تلمسان', coast: false, communes: [c('mansourah', 'Mansourah', 'منصورة')] }
  ];

  TI.types = [
    { id: 'appartement', icon: 'building', fr: 'Appartement', ar: 'شقة', plural: { fr: 'Appartements', ar: 'شقق' } },
    { id: 'villa', icon: 'villa', fr: 'Villa', ar: 'فيلا', plural: { fr: 'Villas', ar: 'فيلات' } },
    { id: 'duplex', icon: 'duplex', fr: 'Duplex', ar: 'دوبلكس', plural: { fr: 'Duplex', ar: 'دوبلكسات' } },
    { id: 'terrain', icon: 'land', fr: 'Terrain', ar: 'قطعة أرض', plural: { fr: 'Terrains', ar: 'أراضٍ' } },
    { id: 'local', icon: 'shop', fr: 'Local commercial', ar: 'محل تجاري', plural: { fr: 'Locaux commerciaux', ar: 'محلات تجارية' } }
  ];

  TI.papers = [
    { id: 'acte', fr: 'Acte notarié', ar: 'عقد موثق' },
    { id: 'livret', fr: 'Livret foncier', ar: 'دفتر عقاري' },
    { id: 'permis', fr: 'Permis de construire', ar: 'رخصة البناء' },
    { id: 'conformite', fr: 'Certificat de conformité', ar: 'شهادة المطابقة' }
  ];

  TI.amenities = [
    { id: 'ascenseur', fr: 'Ascenseur', ar: 'مصعد', filter: true },
    { id: 'parking', fr: 'Parking', ar: 'موقف سيارات', filter: true },
    { id: 'vue_mer', fr: 'Vue mer', ar: 'إطلالة على البحر', filter: true },
    { id: 'piscine', fr: 'Piscine', ar: 'مسبح', filter: true },
    { id: 'jardin', fr: 'Jardin', ar: 'حديقة', filter: true },
    { id: 'meuble', fr: 'Meublé', ar: 'مفروش', filter: true },
    { id: 'terrasse', fr: 'Terrasse', ar: 'تراس', filter: true },
    { id: 'climatisation', fr: 'Climatisation', ar: 'تكييف', filter: true },
    { id: 'chauffage', fr: 'Chauffage central', ar: 'تدفئة مركزية' },
    { id: 'gaz', fr: 'Gaz de ville', ar: 'غاز المدينة' },
    { id: 'citerne', fr: 'Citerne d’eau', ar: 'خزان مياه' },
    { id: 'securite', fr: 'Gardiennage', ar: 'حراسة' },
    { id: 'garage', fr: 'Garage', ar: 'مرآب' },
    { id: 'cuisine', fr: 'Cuisine équipée', ar: 'مطبخ مجهز' },
    { id: 'viabilise', fr: 'Terrain viabilisé', ar: 'أرض مهيأة' },
    { id: 'vitrine', fr: 'Vitrine', ar: 'واجهة زجاجية' }
  ];
})(window.TI);
