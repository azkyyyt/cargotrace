<?php

namespace App\Services;

class SeaRouteService
{
    /**
     * Standard predefined realistic maritime corridors connecting major global ports
     * avoiding landmasses and following standard international navigational shipping lanes.
     */
    public static function getRoute(string $originCode, string $destinationCode): array
    {
        $key = strtoupper($originCode) . '_' . strtoupper($destinationCode);

        // Predefined realistic corridors
        $corridors = [
            // Route 1: Jakarta (Tanjung Priok) -> Rotterdam
            'IDTPP_NLRTM' => [
                [-6.1038, 106.8837, 'Tanjung Priok, Jakarta'],
                [-5.9200, 105.8500, 'Sunda Strait Corridor'],
                [-1.0000, 104.5000, 'Bangka Strait Northbound'],
                [1.2750, 103.7800, 'Singapore Strait / Port of Singapore'],
                [2.8500, 101.2000, 'Malacca Strait Central Channel'],
                [5.9500, 95.2000, 'Aceh / Andaman Sea Corridor'],
                [5.8000, 80.5000, 'South Sri Lanka Shipping Lane'],
                [11.5000, 60.0000, 'Arabian Sea Crossing'],
                [12.5000, 50.8000, 'Gulf of Aden Transit Corridor'],
                [12.6000, 43.5000, 'Bab-el-Mandeb Strait'],
                [20.0000, 38.5000, 'Red Sea Shipping Channel'],
                [27.8000, 34.2000, 'Gulf of Suez Approaches'],
                [29.9500, 32.5500, 'Suez Canal / Port Said'],
                [35.0000, 24.5000, 'Mediterranean Sea South of Crete'],
                [37.2000, 11.5000, 'Strait of Sicily'],
                [36.1000, -5.3500, 'Strait of Gibraltar'],
                [43.5000, -9.5000, 'Cape Finisterre Atlantic Channel'],
                [48.5000, -5.0000, 'English Channel Western Approaches'],
                [50.5000, 0.0000, 'Strait of Dover Channel'],
                [51.9054, 4.3160, 'Port of Rotterdam'],
            ],

            // Route 2: Shanghai (Yangshan) -> Los Angeles (Transpacific Great Circle)
            'CNSHG_USLAX' => [
                [30.6274, 122.0642, 'Shanghai Yangshan Port'],
                [31.5000, 126.0000, 'East China Sea Exit'],
                [30.5000, 130.5000, 'Osumi Strait / South Japan'],
                [34.0000, 142.0000, 'Off Honshu East Pacific Track'],
                [38.5000, 155.0000, 'North Pacific Great Circle Waypoint 1'],
                [42.0000, 170.0000, 'North Pacific Central Track'],
                [43.0000, -175.0000, 'International Date Line Corridor'],
                [41.5000, -160.0000, 'Gulf of Alaska Southern Waypoint'],
                [37.0000, -135.0000, 'California Current Approach'],
                [34.2000, -121.5000, 'Point Conception Approaches'],
                [33.7432, -118.2673, 'Port of Los Angeles'],
            ],

            // Route 3: Jakarta -> Sydney (Via Java Sea, Bali Strait, Timor Sea, Tasman Sea)
            'IDTPP_AUSYD' => [
                [-6.1038, 106.8837, 'Tanjung Priok, Jakarta'],
                [-6.8000, 112.7000, 'Java Sea / Madura Approaches'],
                [-8.7500, 115.3000, 'Badung / Lombok Strait'],
                [-10.5000, 119.5000, 'Sumba Strait Southbound'],
                [-12.0000, 124.0000, 'Timor Sea Shipping Corridor'],
                [-14.5000, 130.0000, 'Joseph Bonaparte Gulf Offshore'],
                [-10.8000, 142.5000, 'Torres Strait Great Barrier Track'],
                [-16.0000, 146.5000, 'Coral Sea Maritime Lane'],
                [-23.5000, 153.0000, 'Capricorn Channel Off Gladstone'],
                [-28.0000, 154.0000, 'Gold Coast Maritime Highway'],
                [-33.8688, 151.2093, 'Port Botany / Sydney Harbour'],
            ],

            // Route 4: Jakarta -> Hamburg (Air Freight corridor / direct Great Circle)
            'IDTPP_DEHAM' => [
                [-6.1038, 106.8837, 'Jakarta Soekarno-Hatta / Priok'],
                [1.3644, 103.9915, 'Singapore Changi Air Transit'],
                [13.6900, 100.7501, 'Bangkok Suvarnabhumi Air Corridor'],
                [25.2532, 55.3657, 'Dubai Cargo Hub'],
                [36.9000, 30.7000, 'Eastern Mediterranean Air Route'],
                [47.5000, 19.0000, 'Central European Airway'],
                [53.5511, 9.9937, 'Hamburg Logistics Hub'],
            ],
        ];

        return $corridors[$key] ?? [];
    }

    /**
     * Calculate initial bearing (degrees 0-360) from point A to point B
     */
    public static function calculateBearing(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $dLngRad = deg2rad($lng2 - $lng1);

        $y = sin($dLngRad) * cos($lat2Rad);
        $x = cos($lat1Rad) * sin($lat2Rad) - sin($lat1Rad) * cos($lat2Rad) * cos($dLngRad);

        $bearing = rad2deg(atan2($y, $x));
        return fmod($bearing + 360, 360);
    }
}
