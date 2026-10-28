<?php

return [
    /*
    |--------------------------------------------------------------------------
    | World nationalities for Select2 dropdowns
    |--------------------------------------------------------------------------
    | WHY: Prior code inlined ~200 nationalities as a JS array literal on
    | every page load (~6 KB of raw HTML). Moved to config and passed via
    | @json() to keep the Blade view lean and enable future localization.
    */
    'list' => [
        "Ghanaian", "Afghan", "Albanian", "Algerian", "American", "Andorran", "Angolan", "Antiguan and Barbudan", "Argentine", "Armenian",
        "Australian", "Austrian", "Azerbaijani", "Bahamian", "Bahraini", "Bangladeshi", "Barbadian", "Belarusian", "Belgian", "Belizean",
        "Beninese", "Bhutanese", "Bolivian", "Bosnian", "Motswana (Botswana)", "Brazilian", "British", "Bruneian", "Bulgarian", "Burkinabe (Burkina Faso)",
        "Burundian", "Cabo Verdean", "Cambodian", "Cameroonian", "Canadian", "Central African", "Chadian", "Chilean", "Chinese", "Colombian",
        "Comorian", "Congolese (Brazzaville)", "Congolese (Kinshasa)", "Costa Rican", "Ivorian (Côte d'Ivoire)", "Croatian", "Cuban", "Cypriot", "Czech",
        "Dane (Danish)", "Djiboutian", "Dominican (Dominica)", "Dominican (Dominican Republic)", "Dutch (Netherlands)", "East Timorese", "Ecuadorean", "Egyptian", "Salvadoran",
        "Equatorial Guinean", "Eritrean", "Estonian", "Swazi (Eswatini)", "Ethiopian", "Fijian", "Finn (Finnish)", "French", "Gabonese", "Gambian",
        "Georgian", "German", "Greek", "Grenadian", "Guatemalan", "Guinean", "Bissau-Guinean", "Guyanese", "Haitian", "Honduran",
        "Hungarian", "Icelander", "Indian", "Indonesian", "Iranian", "Iraqi", "Irish", "Israeli", "Italian", "Jamaican",
        "Japanese", "Jordanian", "Kazakhstani", "Kenyan", "I-Kiribati", "North Korean", "South Korean", "Kuwaiti", "Kyrgyz", "Laotian",
        "Latvian", "Lebanese", "Basotho (Lesotho)", "Liberian", "Libyan", "Liechtenstein citizen", "Lithuanian", "Luxembourgish", "Malagasy", "Malawian",
        "Malaysian", "Maldivian", "Malian", "Maltese", "Marshallese", "Mauritanian", "Mauritian", "Mexican", "Micronesian", "Moldovan",
        "Monegasque", "Mongolian", "Montenegrin", "Moroccan", "Mozambican", "Burmese (Myanmar)", "Namibian", "Nauruan", "Nepali", "New Zealander",
        "Nicaraguan", "Nigerien", "Nigerian", "North Macedonian", "Norwegian", "Omani", "Pakistani", "Palauan", "Palestinian", "Panamanian",
        "Papua New Guinean", "Paraguayan", "Peruvian", "Filipino", "Polish", "Portuguese", "Qatari", "Romanian", "Russian", "Rwandan",
        "Kittitian or Nevisian", "Saint Lucian", "Vincentian", "Samoan", "Sammarinese", "Sao Tomean", "Saudi", "Senegalese", "Serbian",
        "Seychellois", "Sierra Leonean", "Singaporean", "Slovak", "Slovenian", "Solomon Islander", "Somali", "South African", "South Sudanese", "Spanish",
        "Sri Lankan", "Sudanese", "Surinamese", "Swedish", "Swiss", "Syrian", "Taiwanese", "Tajik", "Tanzanian", "Thai",
        "Togolese", "Tongan", "Trinidadian or Tobagonian", "Tunisian", "Turkish", "Turkmen", "Tuvaluan", "Ugandan", "Ukrainian", "Emirati",
        "Uruguayan", "Uzbek", "Ni-Vanuatu", "Vatican citizen", "Venezuelan", "Vietnamese", "Yemeni", "Zambian", "Zimbabwean"
    ],
];
