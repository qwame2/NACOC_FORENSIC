import json

# Comprehensive Official Districts of Ghana for each of the 16 regions
# Positioned with accurate SVG coordinates inside each region's bounding box
districts_data = {
    'Greater Accra': [
        {
            'name': 'Accra Metropolitan Assembly (AMA)',
            'short_name': 'Accra Metro',
            'code': 'AMA',
            'capital': 'Accra Central',
            'x': 522.0,
            'y': 788.0,
            'keywords': ['accra', 'osu', 'central', 'ministries', 'agbogbloshie', 'airport', 'kotoka']
        },
        {
            'name': 'Ayawaso West Municipal Assembly',
            'short_name': 'Ayawaso West (Legon)',
            'code': 'AWMA',
            'capital': 'Dzorwulu / Legon',
            'x': 536.0,
            'y': 770.0,
            'keywords': ['legon', 'ayawaso', 'dzorwulu', 'roman ridge', 'airport residential', 'east legon']
        },
        {
            'name': 'Tema Metropolitan Assembly (TMA)',
            'short_name': 'Tema Metro',
            'code': 'TMA',
            'capital': 'Tema Community 1',
            'x': 565.0,
            'y': 784.0,
            'keywords': ['tema', 'harbour', 'port', 'community']
        },
        {
            'name': 'Ga East Municipal Assembly',
            'short_name': 'Ga East',
            'code': 'GEMA',
            'capital': 'Abokobi',
            'x': 532.0,
            'y': 752.0,
            'keywords': ['abokobi', 'madina', 'haatso', 'dome']
        },
        {
            'name': 'Ga Central Municipal Assembly',
            'short_name': 'Ga Central',
            'code': 'GCMA',
            'capital': 'Sowutuom',
            'x': 505.0,
            'y': 776.0,
            'keywords': ['sowutuom', 'anyaa', 'chorkor', 'odorkor']
        },
        {
            'name': 'Ga South Municipal Assembly',
            'short_name': 'Ga South',
            'code': 'GSMA',
            'capital': 'Ngleshie Amanfro',
            'x': 485.0,
            'y': 782.0,
            'keywords': ['weija', 'amanfro', 'ngleshie']
        },
        {
            'name': 'Ledzokuku Municipal Assembly',
            'short_name': 'Ledzokuku',
            'code': 'LEKMA',
            'capital': 'Teshie',
            'x': 546.0,
            'y': 786.0,
            'keywords': ['teshie', 'nungua', 'ledzokuku']
        },
        {
            'name': 'Kpone-Katamanso Municipal',
            'short_name': 'Kpone-Katamanso',
            'code': 'KKMA',
            'capital': 'Kpone',
            'x': 578.0,
            'y': 766.0,
            'keywords': ['kpone', 'katamanso', 'michel camp']
        },
        {
            'name': 'Ningo-Prampram District Assembly',
            'short_name': 'Ningo-Prampram',
            'code': 'NIPDA',
            'capital': 'Prampram',
            'x': 598.0,
            'y': 774.0,
            'keywords': ['prampram', 'ningo']
        },
        {
            'name': 'Ada East District Assembly',
            'short_name': 'Ada East',
            'code': 'AEDA',
            'capital': 'Ada Foah',
            'x': 622.0,
            'y': 760.0,
            'keywords': ['ada', 'foah', 'estuary']
        }
    ],
    'Ashanti': [
        {
            'name': 'Kumasi Metropolitan Assembly (KMA)',
            'short_name': 'Kumasi Metro',
            'code': 'KMA',
            'capital': 'Adum / Kumasi Central',
            'x': 354.0,
            'y': 668.0,
            'keywords': ['kumasi', 'adum', 'bantama', 'central', 'tafo', 'suame']
        },
        {
            'name': 'Obuasi Municipal Assembly',
            'short_name': 'Obuasi Municipal',
            'code': 'OMA',
            'capital': 'Obuasi',
            'x': 308.0,
            'y': 732.0,
            'keywords': ['obuasi', 'anglogold']
        },
        {
            'name': 'Oforikrom Municipal Assembly',
            'short_name': 'Oforikrom',
            'code': 'OFMA',
            'capital': 'Oforikrom / KNUST',
            'x': 372.0,
            'y': 672.0,
            'keywords': ['oforikrom', 'knust', 'anloga']
        },
        {
            'name': 'Ejisu Municipal Assembly',
            'short_name': 'Ejisu Municipal',
            'code': 'EMA',
            'capital': 'Ejisu',
            'x': 390.0,
            'y': 658.0,
            'keywords': ['ejisu', 'kwaso']
        },
        {
            'name': 'Asante Akim Central Municipal',
            'short_name': 'Asante Akim Central',
            'code': 'AACMA',
            'capital': 'Konongo',
            'x': 420.0,
            'y': 664.0,
            'keywords': ['konongo', 'odumase']
        },
        {
            'name': 'Mampong Municipal Assembly',
            'short_name': 'Mampong Municipal',
            'code': 'MMA',
            'capital': 'Mampong',
            'x': 372.0,
            'y': 590.0,
            'keywords': ['mampong', 'ashanti mampong']
        },
        {
            'name': 'Bekwai Municipal Assembly',
            'short_name': 'Bekwai Municipal',
            'code': 'BMA',
            'capital': 'Bekwai',
            'x': 335.0,
            'y': 706.0,
            'keywords': ['bekwai']
        },
        {
            'name': 'Atwima Nwabiagya North District',
            'short_name': 'Atwima Nwabiagya',
            'code': 'ANNDA',
            'capital': 'Barekese',
            'x': 328.0,
            'y': 646.0,
            'keywords': ['barekese', 'atwima']
        }
    ],
    'Western': [
        {
            'name': 'Sekondi-Takoradi Metropolitan (STMA)',
            'short_name': 'Sekondi-Takoradi',
            'code': 'STMA',
            'capital': 'Sekondi-Takoradi',
            'x': 275.0,
            'y': 906.0,
            'keywords': ['sekondi', 'takoradi', 'harbour', 'market circle']
        },
        {
            'name': 'Tarkwa-Nsuaem Municipal Assembly',
            'short_name': 'Tarkwa-Nsuaem',
            'code': 'TNMA',
            'capital': 'Tarkwa',
            'x': 225.0,
            'y': 842.0,
            'keywords': ['tarkwa', 'nsuaem', 'mining']
        },
        {
            'name': 'Jomoro Municipal Assembly',
            'short_name': 'Jomoro (Elubo)',
            'code': 'JMA',
            'capital': 'Half Assini / Elubo',
            'x': 135.0,
            'y': 868.0,
            'keywords': ['elubo', 'jomoro', 'half assini']
        },
        {
            'name': 'Ahanta West Municipal Assembly',
            'short_name': 'Ahanta West',
            'code': 'AWMA',
            'capital': 'Agona Nkwanta',
            'x': 250.0,
            'y': 895.0,
            'keywords': ['agona nkwanta', 'busua', 'dixcove']
        },
        {
            'name': 'Prestea-Huni Valley Municipal',
            'short_name': 'Prestea-Huni Valley',
            'code': 'PHVMA',
            'capital': 'Bogoso / Prestea',
            'x': 210.0,
            'y': 800.0,
            'keywords': ['bogoso', 'prestea', 'huni valley']
        }
    ],
    'Central': [
        {
            'name': 'Cape Coast Metropolitan Assembly',
            'short_name': 'Cape Coast Metro',
            'code': 'CCMA',
            'capital': 'Cape Coast',
            'x': 355.0,
            'y': 870.0,
            'keywords': ['cape coast', 'ucc', 'central']
        },
        {
            'name': 'Awutu Senya East Municipal (Kasoa)',
            'short_name': 'Kasoa Municipal',
            'code': 'ASEMA',
            'capital': 'Kasoa',
            'x': 475.0,
            'y': 825.0,
            'keywords': ['kasoa', 'awutu', 'ofankor']
        },
        {
            'name': 'Effutu Municipal Assembly (Winneba)',
            'short_name': 'Effutu (Winneba)',
            'code': 'EMA',
            'capital': 'Winneba',
            'x': 435.0,
            'y': 848.0,
            'keywords': ['winneba', 'effutu']
        },
        {
            'name': 'Komenda-Edina-Eguafo-Abirem (KEEA)',
            'short_name': 'KEEA (Elmina)',
            'code': 'KEEA',
            'capital': 'Elmina',
            'x': 336.0,
            'y': 878.0,
            'keywords': ['elmina', 'komenda']
        },
        {
            'name': 'Mfantseman Municipal Assembly',
            'short_name': 'Mfantseman',
            'code': 'MMA',
            'capital': 'Saltpond',
            'x': 385.0,
            'y': 862.0,
            'keywords': ['saltpond', 'mankessim', 'mfantseman']
        }
    ],
    'Eastern': [
        {
            'name': 'New Juaben South Municipal (Koforidua)',
            'short_name': 'Koforidua Municipal',
            'code': 'NJSMA',
            'capital': 'Koforidua',
            'x': 453.0,
            'y': 715.0,
            'keywords': ['koforidua', 'new juaben']
        },
        {
            'name': 'Kwahu West Municipal Assembly',
            'short_name': 'Kwahu West (Nkawkaw)',
            'code': 'KWMA',
            'capital': 'Nkawkaw',
            'x': 415.0,
            'y': 660.0,
            'keywords': ['nkawkaw', 'kwahu']
        },
        {
            'name': 'Suhum Municipal Assembly',
            'short_name': 'Suhum Municipal',
            'code': 'SMA',
            'capital': 'Suhum',
            'x': 445.0,
            'y': 745.0,
            'keywords': ['suhum']
        },
        {
            'name': 'Asuogyaman District Assembly',
            'short_name': 'Asuogyaman (Akosombo)',
            'code': 'ADA',
            'capital': 'Atimpoku / Akosombo',
            'x': 545.0,
            'y': 715.0,
            'keywords': ['akosombo', 'atimpoku', 'dam']
        },
        {
            'name': 'Nsawam Adoagyiri Municipal Assembly',
            'short_name': 'Nsawam Adoagyiri',
            'code': 'NAMA',
            'capital': 'Nsawam',
            'x': 470.0,
            'y': 775.0,
            'keywords': ['nsawam', 'adoagyiri']
        }
    ],
    'Volta': [
        {
            'name': 'Ho Municipal Assembly',
            'short_name': 'Ho Municipal',
            'code': 'HMA',
            'capital': 'Ho Central',
            'x': 620.0,
            'y': 650.0,
            'keywords': ['ho', 'central']
        },
        {
            'name': 'Ketu South Municipal Assembly (Aflao)',
            'short_name': 'Ketu South (Aflao)',
            'code': 'KSMA',
            'capital': 'Denu / Aflao Border',
            'x': 695.0,
            'y': 735.0,
            'keywords': ['aflao', 'togo', 'border', 'ketu south']
        },
        {
            'name': 'Hohoe Municipal Assembly',
            'short_name': 'Hohoe Municipal',
            'code': 'HHMA',
            'capital': 'Hohoe',
            'x': 615.0,
            'y': 600.0,
            'keywords': ['hohoe']
        },
        {
            'name': 'Kpando Municipal Assembly',
            'short_name': 'Kpando Municipal',
            'code': 'KPMA',
            'capital': 'Kpando',
            'x': 570.0,
            'y': 635.0,
            'keywords': ['kpando', 'lake']
        },
        {
            'name': 'South Tongu District Assembly',
            'short_name': 'South Tongu (Sogakope)',
            'code': 'STDA',
            'capital': 'Sogakope',
            'x': 625.0,
            'y': 765.0,
            'keywords': ['sogakope', 'tongu']
        }
    ],
    'Northern': [
        {
            'name': 'Tamale Metropolitan Assembly',
            'short_name': 'Tamale Metro',
            'code': 'TMA',
            'capital': 'Tamale Central',
            'x': 465.0,
            'y': 285.0,
            'keywords': ['tamale', 'central']
        },
        {
            'name': 'Savelugu Municipal Assembly',
            'short_name': 'Savelugu Municipal',
            'code': 'SMA',
            'capital': 'Savelugu',
            'x': 450.0,
            'y': 245.0,
            'keywords': ['savelugu']
        },
        {
            'name': 'Yendi Municipal Assembly',
            'short_name': 'Yendi Municipal',
            'code': 'YMA',
            'capital': 'Yendi',
            'x': 550.0,
            'y': 280.0,
            'keywords': ['yendi']
        },
        {
            'name': 'Nanumba North Municipal Assembly',
            'short_name': 'Bimbilla Municipal',
            'code': 'NNMA',
            'capital': 'Bimbilla',
            'x': 590.0,
            'y': 345.0,
            'keywords': ['bimbilla', 'nanumba']
        }
    ],
    'Upper East': [
        {
            'name': 'Bolgatanga Municipal Assembly',
            'short_name': 'Bolgatanga Municipal',
            'code': 'BMA',
            'capital': 'Bolgatanga',
            'x': 445.0,
            'y': 95.0,
            'keywords': ['bolgatanga', 'bolga']
        },
        {
            'name': 'Kassena-Nankana Municipal (Navrongo/Paga)',
            'short_name': 'Navrongo / Paga',
            'code': 'KNMA',
            'capital': 'Navrongo / Paga Border',
            'x': 400.0,
            'y': 75.0,
            'keywords': ['navrongo', 'paga', 'burkina']
        },
        {
            'name': 'Bawku Municipal Assembly',
            'short_name': 'Bawku Municipal',
            'code': 'BKMA',
            'capital': 'Bawku',
            'x': 525.0,
            'y': 75.0,
            'keywords': ['bawku', 'tri-border']
        }
    ],
    'Upper West': [
        {
            'name': 'Wa Municipal Assembly',
            'short_name': 'Wa Municipal',
            'code': 'WMA',
            'capital': 'Wa Central',
            'x': 215.0,
            'y': 175.0,
            'keywords': ['wa', 'central']
        },
        {
            'name': 'Lambussie District (Hamile Border)',
            'short_name': 'Hamile Border Post',
            'code': 'LDA',
            'capital': 'Hamile',
            'x': 180.0,
            'y': 70.0,
            'keywords': ['hamile', 'lambussie']
        },
        {
            'name': 'Lawra Municipal Assembly',
            'short_name': 'Lawra Municipal',
            'code': 'LMA',
            'capital': 'Lawra',
            'x': 155.0,
            'y': 145.0,
            'keywords': ['lawra', 'black volta']
        },
        {
            'name': 'Sissala East Municipal (Tumu)',
            'short_name': 'Tumu Municipal',
            'code': 'SEMA',
            'capital': 'Tumu',
            'x': 285.0,
            'y': 68.0,
            'keywords': ['tumu', 'sissala']
        }
    ],
    'Bono': [
        {
            'name': 'Sunyani Municipal Assembly',
            'short_name': 'Sunyani Municipal',
            'code': 'SMA',
            'capital': 'Sunyani',
            'x': 210.0,
            'y': 480.0,
            'keywords': ['sunyani']
        },
        {
            'name': 'Berekum East Municipal Assembly',
            'short_name': 'Berekum Municipal',
            'code': 'BEMA',
            'capital': 'Berekum',
            'x': 175.0,
            'y': 485.0,
            'keywords': ['berekum']
        },
        {
            'name': 'Dormaa Central Municipal',
            'short_name': 'Dormaa Central',
            'code': 'DCMA',
            'capital': 'Dormaa Ahenkro',
            'x': 145.0,
            'y': 525.0,
            'keywords': ['dormaa', 'ahenkro']
        },
        {
            'name': 'Wenchi Municipal Assembly',
            'short_name': 'Wenchi Municipal',
            'code': 'WMA',
            'capital': 'Wenchi',
            'x': 245.0,
            'y': 445.0,
            'keywords': ['wenchi']
        }
    ],
    'Bono East': [
        {
            'name': 'Techiman Municipal Assembly',
            'short_name': 'Techiman Municipal',
            'code': 'TMA',
            'capital': 'Techiman',
            'x': 325.0,
            'y': 485.0,
            'keywords': ['techiman']
        },
        {
            'name': 'Kintampo North Municipal Assembly',
            'short_name': 'Kintampo North',
            'code': 'KNMA',
            'capital': 'Kintampo',
            'x': 345.0,
            'y': 415.0,
            'keywords': ['kintampo']
        },
        {
            'name': 'Atebubu-Amantin Municipal',
            'short_name': 'Atebubu-Amantin',
            'code': 'AAMA',
            'capital': 'Atebubu',
            'x': 450.0,
            'y': 485.0,
            'keywords': ['atebubu', 'amantin']
        }
    ],
    'Ahafo': [
        {
            'name': 'Asunafo North Municipal Assembly',
            'short_name': 'Asunafo North (Goaso)',
            'code': 'ANMA',
            'capital': 'Goaso',
            'x': 185.0,
            'y': 625.0,
            'keywords': ['goaso', 'asunafo']
        },
        {
            'name': 'Asutifi North District (Kenyasi)',
            'short_name': 'Asutifi North',
            'code': 'ANDA',
            'capital': 'Kenyasi',
            'x': 230.0,
            'y': 595.0,
            'keywords': ['kenyasi', 'asutifi']
        },
        {
            'name': 'Tano South Municipal Assembly',
            'short_name': 'Tano South',
            'code': 'TSMA',
            'capital': 'Bechem',
            'x': 265.0,
            'y': 610.0,
            'keywords': ['bechem', 'tano']
        }
    ],
    'Western North': [
        {
            'name': 'Sefwi Wiawso Municipal Assembly',
            'short_name': 'Sefwi Wiawso',
            'code': 'SWMA',
            'capital': 'Sefwi Wiawso',
            'x': 175.0,
            'y': 710.0,
            'keywords': ['sefwi wiawso', 'wiawso']
        },
        {
            'name': 'Bibiani-Anhwiaso-Bekwai Municipal',
            'short_name': 'Bibiani Municipal',
            'code': 'BABMA',
            'capital': 'Bibiani',
            'x': 230.0,
            'y': 665.0,
            'keywords': ['bibiani', 'anhwiaso']
        },
        {
            'name': 'Bia West District Assembly',
            'short_name': 'Bia West',
            'code': 'BWDA',
            'capital': 'Essam',
            'x': 120.0,
            'y': 675.0,
            'keywords': ['essam', 'bia']
        }
    ],
    'Oti': [
        {
            'name': 'Krachi East Municipal Assembly',
            'short_name': 'Krachi East (Dambai)',
            'code': 'KEMA',
            'capital': 'Dambai',
            'x': 565.0,
            'y': 460.0,
            'keywords': ['dambai', 'krachi']
        },
        {
            'name': 'Nkwanta South Municipal Assembly',
            'short_name': 'Nkwanta South',
            'code': 'NSMA',
            'capital': 'Nkwanta',
            'x': 605.0,
            'y': 430.0,
            'keywords': ['nkwanta']
        },
        {
            'name': 'Jasikan District Assembly',
            'short_name': 'Jasikan District',
            'code': 'JDA',
            'capital': 'Jasikan',
            'x': 585.0,
            'y': 535.0,
            'keywords': ['jasikan', 'buem']
        }
    ],
    'Savannah': [
        {
            'name': 'West Gonja Municipal Assembly',
            'short_name': 'West Gonja (Damongo)',
            'code': 'WGMA',
            'capital': 'Damongo',
            'x': 325.0,
            'y': 320.0,
            'keywords': ['damongo', 'mole', 'gonja']
        },
        {
            'name': 'Central Gonja District Assembly',
            'short_name': 'Central Gonja (Buipe)',
            'code': 'CGDA',
            'capital': 'Buipe',
            'x': 400.0,
            'y': 365.0,
            'keywords': ['buipe']
        },
        {
            'name': 'Bole District Assembly',
            'short_name': 'Bole District',
            'code': 'BDA',
            'capital': 'Bole',
            'x': 210.0,
            'y': 335.0,
            'keywords': ['bole', 'bamboi']
        }
    ],
    'North East': [
        {
            'name': 'East Mamprusi Municipal Assembly',
            'short_name': 'East Mamprusi (Nalerigu)',
            'code': 'EMMA',
            'capital': 'Nalerigu / Gambaga',
            'x': 540.0,
            'y': 145.0,
            'keywords': ['nalerigu', 'gambaga', 'east mamprusi']
        },
        {
            'name': 'West Mamprusi Municipal Assembly',
            'short_name': 'West Mamprusi (Walewale)',
            'code': 'WMMA',
            'capital': 'Walewale',
            'x': 430.0,
            'y': 155.0,
            'keywords': ['walewale', 'west mamprusi']
        },
        {
            'name': 'Bunkpurugu-Nakpanduri District',
            'short_name': 'Bunkpurugu District',
            'code': 'BNDA',
            'capital': 'Bunkpurugu',
            'x': 585.0,
            'y': 140.0,
            'keywords': ['bunkpurugu', 'nakpanduri']
        }
    ]
}

if __name__ == '__main__':
    total_districts = sum(len(d) for d in districts_data.values())
    print(f"Total districts defined: {total_districts} across 16 regions")
    for r, dists in districts_data.items():
        print(f"  {r}: {len(dists)} districts")
