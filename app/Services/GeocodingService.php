<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    /**
     * Real Ghana administrative regions with verified centroids.
     *
     * @var array<string, array{0: float, 1: float}>
     */
    public const REGION_CENTROIDS = [
        'Greater Accra' => [5.6037, -0.1870],
        'Ashanti'       => [6.6885, -1.6244],
        'Western'       => [4.8967, -1.7558],
        'Western North' => [6.2059, -2.4854],
        'Eastern'       => [6.0941, -0.2591],
        'Volta'         => [6.6008, 0.4713],
        'Oti'           => [8.0689, 0.1792],
        'Northern'      => [9.4008, -0.8393],
        'Savannah'      => [9.0830, -1.8180],
        'North East'    => [10.5269, -0.3694],
        'Central'       => [5.1053, -1.2466],
        'Bono'          => [7.3349, -2.3123],
        'Bono East'     => [7.5862, -1.9344],
        'Ahafo'         => [6.8036, -2.5172],
        'Upper East'    => [10.7856, -0.8514],
        'Upper West'    => [10.0601, -2.5099],
    ];

    /**
     * Comprehensive real gazetteer of Ghana cities, major towns, border posts,
     * seaports, airports, and urban districts.
     *
     * @var array<string, array{lat: float, lng: float, region: string, type: string}>
     */
    public const GHANA_GAZETTEER = [
        // Greater Accra
        'accra'                     => ['lat' => 5.6037, 'lng' => -0.1870, 'region' => 'Greater Accra', 'type' => 'National Capital'],
        'kotoka international airport' => ['lat' => 5.6052, 'lng' => -0.1668, 'region' => 'Greater Accra', 'type' => 'International Airport'],
        'kia'                       => ['lat' => 5.6052, 'lng' => -0.1668, 'region' => 'Greater Accra', 'type' => 'International Airport'],
        'east legon'                => ['lat' => 5.6358, 'lng' => -0.1558, 'region' => 'Greater Accra', 'type' => 'Sub-Metro District'],
        'tema'                      => ['lat' => 5.6698, 'lng' => -0.0166, 'region' => 'Greater Accra', 'type' => 'Metropolis & Port City'],
        'tema harbour'              => ['lat' => 5.6372, 'lng' => 0.0076,  'region' => 'Greater Accra', 'type' => 'Seaport / Cargo Terminal'],
        'tema port'                 => ['lat' => 5.6372, 'lng' => 0.0076,  'region' => 'Greater Accra', 'type' => 'Seaport / Cargo Terminal'],
        'spintex'                   => ['lat' => 5.6350, 'lng' => -0.1050, 'region' => 'Greater Accra', 'type' => 'Commercial Corridor'],
        'madina'                    => ['lat' => 5.6786, 'lng' => -0.1669, 'region' => 'Greater Accra', 'type' => 'Municipality'],
        'cantonments'               => ['lat' => 5.5806, 'lng' => -0.1731, 'region' => 'Greater Accra', 'type' => 'Diplomatic / Residential Zone'],
        'osu'                       => ['lat' => 5.5560, 'lng' => -0.1816, 'region' => 'Greater Accra', 'type' => 'Commercial District'],
        'dansoman'                  => ['lat' => 5.5515, 'lng' => -0.2647, 'region' => 'Greater Accra', 'type' => 'Sub-Metro District'],
        'kaneshie'                  => ['lat' => 5.5681, 'lng' => -0.2361, 'region' => 'Greater Accra', 'type' => 'Transport Hub & Market'],
        'lapaz'                     => ['lat' => 5.6089, 'lng' => -0.2498, 'region' => 'Greater Accra', 'type' => 'Commercial Junction'],
        'dome'                      => ['lat' => 5.6500, 'lng' => -0.2333, 'region' => 'Greater Accra', 'type' => 'Residential District'],
        'ashaiman'                  => ['lat' => 5.6950, 'lng' => -0.0328, 'region' => 'Greater Accra', 'type' => 'Municipality'],
        'dodowa'                    => ['lat' => 5.8819, 'lng' => -0.0981, 'region' => 'Greater Accra', 'type' => 'District Capital'],
        'ada'                       => ['lat' => 5.7833, 'lng' => 0.6333,  'region' => 'Greater Accra', 'type' => 'Coastal Town'],
        'ada foah'                  => ['lat' => 5.7900, 'lng' => 0.6300,  'region' => 'Greater Accra', 'type' => 'Estuary & Border Post'],
        'prampram'                  => ['lat' => 5.7167, 'lng' => 0.1167,  'region' => 'Greater Accra', 'type' => 'Coastal District'],
        'nungua'                    => ['lat' => 5.6019, 'lng' => -0.0753, 'region' => 'Greater Accra', 'type' => 'Coastal Town'],
        'teshie'                    => ['lat' => 5.5833, 'lng' => -0.1000, 'region' => 'Greater Accra', 'type' => 'Municipality'],
        'labadi'                    => ['lat' => 5.5636, 'lng' => -0.1525, 'region' => 'Greater Accra', 'type' => 'Coastal District'],
        'kasoa'                     => ['lat' => 5.5345, 'lng' => -0.4168, 'region' => 'Central',       'type' => 'Commercial Border City'],
        'weija'                     => ['lat' => 5.5667, 'lng' => -0.3333, 'region' => 'Greater Accra', 'type' => 'Municipality'],
        'amasaman'                  => ['lat' => 5.7042, 'lng' => -0.2994, 'region' => 'Greater Accra', 'type' => 'District Capital'],
        'achimota'                  => ['lat' => 5.6264, 'lng' => -0.2248, 'region' => 'Greater Accra', 'type' => 'Transit Hub'],
        'adenta'                    => ['lat' => 5.7153, 'lng' => -0.1633, 'region' => 'Greater Accra', 'type' => 'Municipality'],
        'haatso'                    => ['lat' => 5.6740, 'lng' => -0.1980, 'region' => 'Greater Accra', 'type' => 'Urban District'],
        'abeka'                     => ['lat' => 5.5960, 'lng' => -0.2410, 'region' => 'Greater Accra', 'type' => 'Commercial Zone'],
        'oyarifa'                   => ['lat' => 5.7714, 'lng' => -0.1806, 'region' => 'Greater Accra', 'type' => 'Urban Town'],
        'abokobi'                   => ['lat' => 5.7361, 'lng' => -0.1989, 'region' => 'Greater Accra', 'type' => 'District Capital'],
        'kwabenya'                  => ['lat' => 5.6914, 'lng' => -0.2411, 'region' => 'Greater Accra', 'type' => 'Urban District'],
        'pokuase'                   => ['lat' => 5.6881, 'lng' => -0.2892, 'region' => 'Greater Accra', 'type' => 'Interchange & Town'],
        'sakumono'                  => ['lat' => 5.6228, 'lng' => -0.0569, 'region' => 'Greater Accra', 'type' => 'Coastal Estate'],
        'lashibi'                   => ['lat' => 5.6472, 'lng' => -0.0686, 'region' => 'Greater Accra', 'type' => 'Residential District'],
        'dzorwulu'                  => ['lat' => 5.6119, 'lng' => -0.2031, 'region' => 'Greater Accra', 'type' => 'Sub-Metro District'],
        'airport residential'       => ['lat' => 5.6058, 'lng' => -0.1819, 'region' => 'Greater Accra', 'type' => 'Residential Zone'],
        'labone'                    => ['lat' => 5.5697, 'lng' => -0.1708, 'region' => 'Greater Accra', 'type' => 'Sub-Metro District'],
        'tesano'                    => ['lat' => 5.6056, 'lng' => -0.2308, 'region' => 'Greater Accra', 'type' => 'Urban District'],
        'odorkor'                   => ['lat' => 5.5794, 'lng' => -0.2708, 'region' => 'Greater Accra', 'type' => 'Commercial Corridor'],
        'mallam'                    => ['lat' => 5.5719, 'lng' => -0.2897, 'region' => 'Greater Accra', 'type' => 'Transit Junction'],
        'gbawe'                     => ['lat' => 5.5778, 'lng' => -0.3114, 'region' => 'Greater Accra', 'type' => 'Residential District'],
        'dawhenya'                  => ['lat' => 5.7689, 'lng' => 0.0539,  'region' => 'Greater Accra', 'type' => 'Highway Town'],
        'afienya'                   => ['lat' => 5.7989, 'lng' => 0.0028,  'region' => 'Greater Accra', 'type' => 'Transit Town'],
        'oyibi'                     => ['lat' => 5.8147, 'lng' => -0.1197, 'region' => 'Greater Accra', 'type' => 'Suburban Town'],

        // Ashanti
        'kumasi'                    => ['lat' => 6.6885, 'lng' => -1.6244, 'region' => 'Ashanti',       'type' => 'Metropolitan Capital'],
        'kumasi airport'            => ['lat' => 6.7146, 'lng' => -1.5908, 'region' => 'Ashanti',       'type' => 'Airport'],
        'kejetia'                   => ['lat' => 6.6975, 'lng' => -1.6225, 'region' => 'Ashanti',       'type' => 'Central Market Hub'],
        'adum'                      => ['lat' => 6.6900, 'lng' => -1.6210, 'region' => 'Ashanti',       'type' => 'Commercial Center'],
        'obuasi'                    => ['lat' => 6.2023, 'lng' => -1.6634, 'region' => 'Ashanti',       'type' => 'Mining Municipality'],
        'ejisu'                     => ['lat' => 6.7167, 'lng' => -1.4667, 'region' => 'Ashanti',       'type' => 'Municipality'],
        'konongo'                   => ['lat' => 6.6167, 'lng' => -1.2167, 'region' => 'Ashanti',       'type' => 'Municipality'],
        'mampong'                   => ['lat' => 7.0627, 'lng' => -1.4001, 'region' => 'Ashanti',       'type' => 'Municipality'],
        'bekwai'                    => ['lat' => 6.4532, 'lng' => -1.5786, 'region' => 'Ashanti',       'type' => 'Municipality'],
        'offinso'                   => ['lat' => 6.9500, 'lng' => -1.6500, 'region' => 'Ashanti',       'type' => 'Municipality'],
        'agogo'                     => ['lat' => 6.8000, 'lng' => -1.0833, 'region' => 'Ashanti',       'type' => 'Town'],
        'tafo'                      => ['lat' => 6.7333, 'lng' => -1.6167, 'region' => 'Ashanti',       'type' => 'Sub-Metro District'],
        'asokwa'                    => ['lat' => 6.6710, 'lng' => -1.6030, 'region' => 'Ashanti',       'type' => 'Industrial Zone'],

        // Western & Western North
        'takoradi'                  => ['lat' => 4.8967, 'lng' => -1.7558, 'region' => 'Western',       'type' => 'Regional Capital & Seaport'],
        'takoradi harbour'          => ['lat' => 4.8885, 'lng' => -1.7420, 'region' => 'Western',       'type' => 'Seaport / Offshore Logistics'],
        'sekondi'                   => ['lat' => 4.9340, 'lng' => -1.7137, 'region' => 'Western',       'type' => 'Naval Base & Twin City'],
        'tarkwa'                    => ['lat' => 5.3018, 'lng' => -1.9930, 'region' => 'Western',       'type' => 'Mining Municipality'],
        'elubo'                     => ['lat' => 5.2869, 'lng' => -2.7675, 'region' => 'Western',       'type' => 'International Border Post (Ivory Coast)'],
        'axim'                      => ['lat' => 4.8699, 'lng' => -2.2405, 'region' => 'Western',       'type' => 'Coastal Town'],
        'prestea'                   => ['lat' => 5.4339, 'lng' => -2.1428, 'region' => 'Western',       'type' => 'Town'],
        'bogoso'                    => ['lat' => 5.5667, 'lng' => -2.0167, 'region' => 'Western',       'type' => 'Town'],
        'sefwi wiawso'              => ['lat' => 6.2059, 'lng' => -2.4854, 'region' => 'Western North', 'type' => 'Regional Capital'],
        'wiawso'                    => ['lat' => 6.2059, 'lng' => -2.4854, 'region' => 'Western North', 'type' => 'Regional Capital'],
        'bibiani'                   => ['lat' => 6.4635, 'lng' => -2.3194, 'region' => 'Western North', 'type' => 'Municipality'],
        'enchi'                     => ['lat' => 5.8200, 'lng' => -2.8250, 'region' => 'Western North', 'type' => 'Border District'],

        // Eastern
        'koforidua'                 => ['lat' => 6.0941, 'lng' => -0.2591, 'region' => 'Eastern',       'type' => 'Regional Capital'],
        'nkawkaw'                   => ['lat' => 6.5512, 'lng' => -0.7662, 'region' => 'Eastern',       'type' => 'Municipality'],
        'nsawam'                    => ['lat' => 5.8089, 'lng' => -0.3503, 'region' => 'Eastern',       'type' => 'Municipality & Prison Command'],
        'suhum'                     => ['lat' => 6.0367, 'lng' => -0.4500, 'region' => 'Eastern',       'type' => 'Municipality'],
        'akim oda'                  => ['lat' => 5.9267, 'lng' => -0.9858, 'region' => 'Eastern',       'type' => 'Municipality'],
        'oda'                       => ['lat' => 5.9267, 'lng' => -0.9858, 'region' => 'Eastern',       'type' => 'Municipality'],
        'somanya'                   => ['lat' => 6.1000, 'lng' => -0.0167, 'region' => 'Eastern',       'type' => 'Municipality'],
        'akropong'                  => ['lat' => 5.9744, 'lng' => -0.0914, 'region' => 'Eastern',       'type' => 'Town'],
        'kibi'                      => ['lat' => 6.1667, 'lng' => -0.5500, 'region' => 'Eastern',       'type' => 'Town'],
        'begoro'                    => ['lat' => 6.3833, 'lng' => -0.3833, 'region' => 'Eastern',       'type' => 'Town'],
        'asutuare'                  => ['lat' => 6.0460, 'lng' => 0.1730,  'region' => 'Eastern',       'type' => 'Riverine / Security Zone'],

        // Volta & Oti
        'ho'                        => ['lat' => 6.6008, 'lng' => 0.4713,  'region' => 'Volta',         'type' => 'Regional Capital'],
        'aflao'                     => ['lat' => 6.1190, 'lng' => 1.1904,  'region' => 'Volta',         'type' => 'Major Border Control Station (Togo)'],
        'hohoe'                     => ['lat' => 7.1518, 'lng' => 0.4736,  'region' => 'Volta',         'type' => 'Municipality'],
        'kpando'                    => ['lat' => 6.9954, 'lng' => 0.2931,  'region' => 'Volta',         'type' => 'Municipality'],
        'keta'                      => ['lat' => 5.9179, 'lng' => 0.9879,  'region' => 'Volta',         'type' => 'Coastal Municipality'],
        'sogakope'                  => ['lat' => 6.0000, 'lng' => 0.6000,  'region' => 'Volta',         'type' => 'Highway Corridor'],
        'akatsi'                    => ['lat' => 6.1333, 'lng' => 0.8000,  'region' => 'Volta',         'type' => 'Town'],
        'dambai'                    => ['lat' => 8.0689, 'lng' => 0.1792,  'region' => 'Oti',           'type' => 'Regional Capital'],
        'nkwanta'                   => ['lat' => 8.2500, 'lng' => 0.5167,  'region' => 'Oti',           'type' => 'Municipality'],
        'jasikan'                   => ['lat' => 7.4000, 'lng' => 0.4667,  'region' => 'Oti',           'type' => 'District Capital'],

        // Northern, Savannah & North East
        'tamale'                    => ['lat' => 9.4008, 'lng' => -0.8393, 'region' => 'Northern',      'type' => 'Regional Capital'],
        'tamale airport'            => ['lat' => 9.5571, 'lng' => -0.8632, 'region' => 'Northern',      'type' => 'International Airport'],
        'yendi'                     => ['lat' => 9.4427, 'lng' => -0.0099, 'region' => 'Northern',      'type' => 'Municipality'],
        'savelugu'                  => ['lat' => 9.6244, 'lng' => -0.8253, 'region' => 'Northern',      'type' => 'Municipality'],
        'damongo'                   => ['lat' => 9.0830, 'lng' => -1.8180, 'region' => 'Savannah',      'type' => 'Regional Capital'],
        'bole'                      => ['lat' => 9.0333, 'lng' => -2.4833, 'region' => 'Savannah',      'type' => 'Border District'],
        'salaga'                    => ['lat' => 8.5508, 'lng' => -0.5188, 'region' => 'Savannah',      'type' => 'Town'],
        'buipe'                     => ['lat' => 8.7667, 'lng' => -1.4833, 'region' => 'Savannah',      'type' => 'Port / Transit Town'],
        'nalerigu'                  => ['lat' => 10.5269, 'lng' => -0.3694, 'region' => 'North East',   'type' => 'Regional Capital'],
        'walewale'                  => ['lat' => 10.3500, 'lng' => -0.8000, 'region' => 'North East',   'type' => 'Municipality'],

        // Central
        'cape coast'                => ['lat' => 5.1053, 'lng' => -1.2466, 'region' => 'Central',       'type' => 'Regional Capital'],
        'elmina'                    => ['lat' => 5.0847, 'lng' => -1.3509, 'region' => 'Central',       'type' => 'Coastal Municipality'],
        'winneba'                   => ['lat' => 5.3511, 'lng' => -0.6232, 'region' => 'Central',       'type' => 'Municipality'],
        'mankessim'                 => ['lat' => 5.2667, 'lng' => -1.0167, 'region' => 'Central',       'type' => 'Trading Hub'],
        'swedru'                    => ['lat' => 5.5371, 'lng' => -0.6998, 'region' => 'Central',       'type' => 'Municipality'],
        'saltpond'                  => ['lat' => 5.2091, 'lng' => -1.0606, 'region' => 'Central',       'type' => 'Coastal Town'],
        'assin fosu'                => ['lat' => 5.7012, 'lng' => -1.2866, 'region' => 'Central',       'type' => 'Municipality'],
        'dunkwa'                    => ['lat' => 5.9667, 'lng' => -1.7833, 'region' => 'Central',       'type' => 'Mining Town'],

        // Bono, Bono East & Ahafo
        'sunyani'                   => ['lat' => 7.3349, 'lng' => -2.3123, 'region' => 'Bono',          'type' => 'Regional Capital'],
        'berekum'                   => ['lat' => 7.4534, 'lng' => -2.5840, 'region' => 'Bono',          'type' => 'Municipality'],
        'dormaa ahenkro'            => ['lat' => 7.2833, 'lng' => -2.8833, 'region' => 'Bono',          'type' => 'Border Municipality (Ivory Coast)'],
        'dormaa'                    => ['lat' => 7.2833, 'lng' => -2.8833, 'region' => 'Bono',          'type' => 'Border Municipality (Ivory Coast)'],
        'wenchi'                    => ['lat' => 7.7392, 'lng' => -2.1046, 'region' => 'Bono',          'type' => 'Municipality'],
        'techiman'                  => ['lat' => 7.5862, 'lng' => -1.9344, 'region' => 'Bono East',     'type' => 'Regional Capital & Commercial Hub'],
        'kintampo'                  => ['lat' => 8.0563, 'lng' => -1.7306, 'region' => 'Bono East',     'type' => 'Municipality'],
        'atebubu'                   => ['lat' => 7.7500, 'lng' => -0.9833, 'region' => 'Bono East',     'type' => 'District Capital'],
        'yeji'                      => ['lat' => 8.2219, 'lng' => -0.6531, 'region' => 'Bono East',     'type' => 'Lake Volta Port'],
        'goaso'                     => ['lat' => 6.8036, 'lng' => -2.5172, 'region' => 'Ahafo',         'type' => 'Regional Capital'],
        'bechem'                    => ['lat' => 7.0903, 'lng' => -2.0250, 'region' => 'Ahafo',         'type' => 'Town'],

        // Upper East & Upper West
        'bolgatanga'                => ['lat' => 10.7856, 'lng' => -0.8514, 'region' => 'Upper East',   'type' => 'Regional Capital'],
        'paga'                      => ['lat' => 10.9969, 'lng' => -1.1136, 'region' => 'Upper East',   'type' => 'Strategic Northern Border Post (Burkina Faso)'],
        'bawku'                     => ['lat' => 11.0616, 'lng' => -0.2417, 'region' => 'Upper East',   'type' => 'Border Municipality'],
        'navrongo'                  => ['lat' => 10.8956, 'lng' => -1.0921, 'region' => 'Upper East',   'type' => 'Municipality'],
        'zebilla'                   => ['lat' => 10.9167, 'lng' => -0.5167, 'region' => 'Upper East',   'type' => 'District Capital'],
        'wa'                        => ['lat' => 10.0601, 'lng' => -2.5099, 'region' => 'Upper West',   'type' => 'Regional Capital'],
        'tumu'                      => ['lat' => 10.8833, 'lng' => -1.9833, 'region' => 'Upper West',   'type' => 'Border District'],
        'lawra'                     => ['lat' => 10.6500, 'lng' => -2.8833, 'region' => 'Upper West',   'type' => 'Border Town'],
        'hamile'                    => ['lat' => 11.0000, 'lng' => -2.7667, 'region' => 'Upper West',   'type' => 'International Border Post'],
    ];

    /**
     * Search coordinates for a place query.
     * Uses live OpenStreetMap Nominatim with local Ghana gazetteer fallback.
     *
     * @param string $query
     * @return array<int, array{name: string, display_name: string, latitude: float, longitude: float, region: string|null, type: string, source: string}>
     */
    public function search(string $query): array
    {
        $cleanQuery = trim($query);
        $len = function_exists('mb_strlen') ? mb_strlen($cleanQuery) : strlen($cleanQuery);
        if ($len < 2) {
            return [];
        }

        $normalized = function_exists('mb_strtolower') ? mb_strtolower($cleanQuery) : strtolower($cleanQuery);
        $results = [];
        $seenCoords = [];

        // 0. Direct raw coordinate input support (e.g. "5.6037, -0.1870")
        if (preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', $cleanQuery, $coordMatches)) {
            $lat = (float) $coordMatches[1];
            $lng = (float) $coordMatches[2];
            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                $rev = $this->findNearestGazetteerPoint($lat, $lng);
                return [[
                    'name'         => $rev['name'],
                    'display_name' => $rev['display_name'],
                    'latitude'     => $lat,
                    'longitude'    => $lng,
                    'region'       => $rev['region'],
                    'type'         => 'GPS Coordinates',
                    'source'       => 'Direct Coordinates',
                ]];
            }
        }

        // 1. Check local gazetteer for exact or high-confidence substring matches first
        $localMatches = $this->searchLocalGazetteer($normalized);
        foreach ($localMatches as $lm) {
            $key = round($lm['latitude'], 4) . ',' . round($lm['longitude'], 4);
            $seenCoords[$key] = true;
            $results[] = $lm;
        }

        // 2. Query OpenStreetMap Nominatim live geocoding API bounded to Ghana
        $liveResults = $this->queryNominatimLive($cleanQuery);
        foreach ($liveResults as $lr) {
            $key = round($lr['latitude'], 4) . ',' . round($lr['longitude'], 4);
            if (!isset($seenCoords[$key])) {
                $seenCoords[$key] = true;
                $results[] = $lr;
            }
        }

        // 3. Fallback to Region centroids if still empty
        if (empty($results)) {
            foreach (self::REGION_CENTROIDS as $regName => $coords) {
                $regLower = function_exists('mb_strtolower') ? mb_strtolower($regName) : strtolower($regName);
                if (str_contains($normalized, $regLower)) {
                    $results[] = [
                        'name'         => $regName . ' Region',
                        'display_name' => $regName . ' Region, Ghana',
                        'latitude'     => $coords[0],
                        'longitude'    => $coords[1],
                        'region'       => $regName,
                        'type'         => 'Administrative Region',
                        'source'       => 'Ghana Gazetteer',
                    ];
                    break;
                }
            }
        }

        return array_slice($results, 0, 8);
    }

    /**
     * Reverse geocode a latitude/longitude pair into a human-readable location description.
     *
     * @param float $lat
     * @param float $lng
     * @return array{name: string, display_name: string, latitude: float, longitude: float, region: string|null, source: string}
     */
    public function reverse(float $lat, float $lng): array
    {
        // 1. Try Nominatim reverse lookup (IPv4 + fast connect timeout + circuit breaker for firewalled hosts)
        $skipNominatim = false;
        try {
            $skipNominatim = (bool) \Illuminate\Support\Facades\Cache::get('nacoc_nominatim_unreachable', false);
        } catch (\Throwable $e) {}

        if (!$skipNominatim) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'NACOC-Forensics-Incident-Locator/1.0 (contact: operations@nacoc.gov.gh)',
                    'Accept'     => 'application/json',
                ])
                    ->connectTimeout(1.5)
                    ->timeout(2.5)
                    ->withOptions(['force_ip_resolve' => 'v4'])
                    ->get('https://nominatim.openstreetmap.org/reverse', [
                        'format'         => 'json',
                        'lat'            => $lat,
                        'lon'            => $lng,
                        'zoom'           => 18,
                        'addressdetails' => 1,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (!empty($data) && !empty($data['display_name'])) {
                        $addr = $data['address'] ?? [];
                        $shortParts = [];
                        foreach (['road', 'suburb', 'neighbourhood', 'town', 'city', 'county', 'state'] as $f) {
                            if (!empty($addr[$f]) && !in_array($addr[$f], $shortParts, true)) {
                                $shortParts[] = $addr[$f];
                            }
                        }
                        $shortName = !empty($shortParts) ? implode(', ', array_slice($shortParts, 0, 3)) : ($data['name'] ?? 'Pinpoint Location');

                        return [
                            'name'         => $shortName,
                            'display_name' => $data['display_name'],
                            'latitude'     => (float) $data['lat'],
                            'longitude'    => (float) $data['lon'],
                            'region'       => $addr['state'] ?? null,
                            'source'       => 'OpenStreetMap Nominatim',
                        ];
                    }
                }
            } catch (\Throwable $e) {
                try {
                    \Illuminate\Support\Facades\Cache::put('nacoc_nominatim_unreachable', true, now()->addMinutes(10));
                } catch (\Throwable $ce) {}
                Log::info('Geocoding reverse live lookup error: ' . $e->getMessage());
            }
        }

        // 2. Fallback to nearest gazetteer point using real Haversine formula
        return $this->findNearestGazetteerPoint($lat, $lng);
    }

    /**
     * Search local verified Ghana gazetteer.
     *
     * @param string $query
     * @return array<int, array{name: string, display_name: string, latitude: float, longitude: float, region: string, type: string, source: string}>
     */
    private function searchLocalGazetteer(string $query): array
    {
        $matches = [];
        foreach (self::GHANA_GAZETTEER as $name => $info) {
            $isDirect = str_contains($query, $name) || str_contains($name, $query);
            if ($isDirect) {
                $titleCase = ucwords($name);
                $matches[] = [
                    'name'         => $titleCase,
                    'display_name' => "{$titleCase}, {$info['region']} Region, Ghana",
                    'latitude'     => $info['lat'],
                    'longitude'    => $info['lng'],
                    'region'       => $info['region'],
                    'type'         => $info['type'],
                    'source'       => 'Ghana Official Gazetteer',
                ];
            }
        }

        // Sort by match closeness
        usort($matches, function ($a, $b) use ($query) {
            $aName = function_exists('mb_strtolower') ? mb_strtolower($a['name']) : strtolower($a['name']);
            $bName = function_exists('mb_strtolower') ? mb_strtolower($b['name']) : strtolower($b['name']);
            if ($aName === $query) return -1;
            if ($bName === $query) return 1;
            return strlen($aName) <=> strlen($bName);
        });

        return $matches;
    }

    /**
     * Query OpenStreetMap Nominatim live search API bounded to Ghana.
     *
     * @param string $query
     * @return array<int, array{name: string, display_name: string, latitude: float, longitude: float, region: string|null, type: string, source: string}>
     */
    private function queryNominatimLive(string $query): array
    {
        $cacheKey = 'nacoc_geocode_gh_' . md5(strtolower(trim($query)));
        try {
            if (\Illuminate\Support\Facades\Cache::get('nacoc_nominatim_unreachable', false)) {
                return [];
            }
            $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        } catch (\Throwable $e) {
            // Proceed without cache if cache store is unavailable
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'NACOC-Forensics-Incident-Locator/1.0 (contact: operations@nacoc.gov.gh)',
                'Accept'     => 'application/json',
            ])
                ->connectTimeout(1.5)
                ->timeout(2.5)
                ->withOptions(['force_ip_resolve' => 'v4'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'format'         => 'json',
                    'q'              => $query,
                    'countrycodes'   => 'gh',
                    'addressdetails' => 1,
                    'limit'          => 6,
                ]);

            if (!$response->successful()) {
                return [];
            }

            $rawList = $response->json();
            if (!is_array($rawList)) {
                return [];
            }

            $results = [];
            foreach ($rawList as $item) {
                if (!isset($item['lat'], $item['lon'])) {
                    continue;
                }

                $addr = $item['address'] ?? [];
                $region = $addr['state'] ?? ($addr['region'] ?? null);
                $name = $item['name'] ?? ($addr['road'] ?? ($addr['suburb'] ?? ($addr['town'] ?? ($addr['city'] ?? 'Pinpoint Location'))));

                $results[] = [
                    'name'         => $name,
                    'display_name' => $item['display_name'] ?? $name,
                    'latitude'     => (float) $item['lat'],
                    'longitude'    => (float) $item['lon'],
                    'region'       => $region,
                    'type'         => ucfirst(str_replace('_', ' ', $item['type'] ?? 'Location')),
                    'source'       => 'OpenStreetMap Nominatim',
                ];
            }

            if (!empty($results)) {
                try {
                    \Illuminate\Support\Facades\Cache::put($cacheKey, $results, now()->addHours(24));
                } catch (\Throwable $e) {}
            }

            return $results;
        } catch (\Throwable $e) {
            try {
                \Illuminate\Support\Facades\Cache::put('nacoc_nominatim_unreachable', true, now()->addMinutes(10));
            } catch (\Throwable $ce) {}
            Log::info('Geocoding live search error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Find the nearest point in the Ghana gazetteer using Haversine formula.
     *
     * @param float $lat
     * @param float $lng
     * @return array{name: string, display_name: string, latitude: float, longitude: float, region: string, source: string}
     */
    private function findNearestGazetteerPoint(float $lat, float $lng): array
    {
        $closestName = null;
        $closestInfo = null;
        $minDist = PHP_FLOAT_MAX;

        foreach (self::GHANA_GAZETTEER as $name => $info) {
            $dist = $this->haversineGreatCircleDistance($lat, $lng, $info['lat'], $info['lng']);
            if ($dist < $minDist) {
                $minDist = $dist;
                $closestName = $name;
                $closestInfo = $info;
            }
        }

        if ($closestInfo && $closestName) {
            $titleName = ucwords($closestName);
            $distKm = round($minDist, 1);
            $distanceNote = $distKm > 1.0 ? " (~{$distKm}km from {$titleName})" : '';

            return [
                'name'         => "Near {$titleName}",
                'display_name' => "Coordinates ({$lat}, {$lng}) near {$titleName}{$distanceNote}, {$closestInfo['region']} Region, Ghana",
                'latitude'     => $lat,
                'longitude'    => $lng,
                'region'       => $closestInfo['region'],
                'source'       => 'Ghana Gazetteer Vector Match',
            ];
        }

        return [
            'name'         => "Pinpoint Location ({$lat}, {$lng})",
            'display_name' => "Coordinates ({$lat}, {$lng}), Ghana",
            'latitude'     => $lat,
            'longitude'    => $lng,
            'region'       => 'Ghana',
            'source'       => 'GPS Coordinate Pin',
        ];
    }

    /**
     * Calculates the great-circle distance between two points in kilometers.
     */
    private function haversineGreatCircleDistance(float $latitudeFrom, float $longitudeFrom, float $latitudeTo, float $longitudeTo): float
    {
        $earthRadius = 6371.0;
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }
}

