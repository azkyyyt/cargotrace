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
            // Route 1: Santos, Brazil -> Tanjung Priok, Jakarta (South Atlantic -> Cape of Good Hope -> Indian Ocean -> Sunda Strait)
            'BRSSZ_IDTPP' => [
                [-23.9678, -46.2956, 'Port of Santos (Brazil) - Loading Silos'],
                [-25.5000, -42.0000, 'South Atlantic Brazilian Coastal Fairway'],
                [-30.0000, -25.0000, 'South Atlantic Trans-oceanic Corridor Alpha'],
                [-34.5000, 0.0000, 'Mid-Atlantic Southern Shipping Lane'],
                [-35.5000, 18.5000, 'Cape of Good Hope Oceanic Channel'],
                [-32.0000, 45.0000, 'South Indian Ocean Transit Waypoint 1'],
                [-20.0000, 75.0000, 'Central Indian Ocean Bulk Fairway'],
                [-10.0000, 95.0000, 'Cocos / Keeling Oceanic Approach'],
                [-6.5000, 104.5000, 'Sunda Strait Western Sea Approach'],
                [-5.9200, 105.8500, 'Sunda Strait Corridor'],
                [-6.1038, 106.8837, 'Tanjung Priok Port (Jakarta, Indonesia)'],
            ],

            // Route 2: Fremantle / Dampier, Australia -> Tanjung Priok, Jakarta (Wheat Route)
            'AUFRE_IDTPP' => [
                [-32.0569, 115.7439, 'Port of Fremantle (Perth, Australia)'],
                [-28.0000, 112.5000, 'Western Australian Coastal Fairway'],
                [-20.0000, 110.0000, 'Eastern Indian Ocean Northwest Track'],
                [-12.0000, 108.5000, 'Christmas Island Approach'],
                [-6.5000, 106.0000, 'Java Sea Southwestern Entrance'],
                [-6.1038, 106.8837, 'Tanjung Priok Port (Jakarta, Indonesia)'],
            ],

            // Route 3: New Orleans, USA -> Qingdao, China (Soybeans Route via Atlantic, Mediterranean, Suez & Malacca)
            'USMSY_CNTAO' => [
                [29.9511, -90.0715, 'Port of New Orleans / Mississippi Gulf (USA)'],
                [24.5000, -82.0000, 'Florida Straits Eastbound Channel'],
                [28.0000, -70.0000, 'North Atlantic Deepwater Track'],
                [32.0000, -45.0000, 'Mid-Atlantic Grain Shipping Corridor'],
                [35.0000, -20.0000, 'Azores Offshore Fairway'],
                [36.0000, -6.0000, 'Strait of Gibraltar Gateway'],
                [36.5000, 12.0000, 'Mediterranean Central Deep Basin'],
                [32.0000, 31.5000, 'Suez Canal Approach (Port Said)'],
                [27.5000, 34.0000, 'Gulf of Suez & Red Sea Corridor'],
                [13.0000, 43.5000, 'Bab el-Mandeb Strait Gateway'],
                [12.0000, 52.0000, 'Gulf of Aden / Socotra Fairway'],
                [7.0000, 78.0000, 'South Sri Lanka Maritime Highway'],
                [5.5000, 95.0000, 'Malacca Strait Western Approach'],
                [2.5000, 101.5000, 'Malacca Strait Main Corridor'],
                [1.3000, 104.2000, 'Singapore Strait Transit'],
                [12.0000, 112.5000, 'South China Sea Oceanic Fairway'],
                [22.0000, 120.0000, 'Taiwan Strait Eastbound Corridor'],
                [31.0000, 122.8000, 'East China Sea Shipping Lane'],
                [36.0671, 120.3826, 'Port of Qingdao (China)'],
            ],

            // Route 4: Bangkok / Laem Chabang, Thailand -> Tanjung Perak, Surabaya, Indonesia (Rice Route)
            'THBKK_IDSUB' => [
                [13.0827, 100.8833, 'Port of Laem Chabang / Bangkok (Thailand)'],
                [9.0000, 102.5000, 'Gulf of Thailand Central Fairway'],
                [4.5000, 104.5000, 'South China Sea / Natuna Sea Passage'],
                [1.5000, 105.0000, 'Singapore Strait Eastern Approaches'],
                [-1.5000, 106.5000, 'Karimata Strait Corridor'],
                [-4.5000, 110.0000, 'Java Sea Central Fairway'],
                [-7.1982, 112.7335, 'Tanjung Perak Port (Surabaya, Indonesia)'],
            ],

            // Route 2: Shanghai (Yangshan) -> Los Angeles (Transpacific Great Circle - Eastbound)
            'CNSHG_USLAX' => [
                [30.6274, 122.0642, 'Shanghai Yangshan Port'],
                [31.5000, 126.0000, 'East China Sea Exit'],
                [30.5000, 130.5000, 'Osumi Strait / South Japan'],
                [34.0000, 142.0000, 'Off Honshu East Pacific Track'],
                [38.5000, 155.0000, 'North Pacific Great Circle Waypoint 1'],
                [42.0000, 170.0000, 'North Pacific Central Track'],
                [43.0000, 185.0000, 'International Date Line Corridor Eastbound'],
                [41.5000, 200.0000, 'Gulf of Alaska Southern Waypoint'],
                [37.0000, 225.0000, 'California Current Approach'],
                [34.2000, 238.5000, 'Point Conception Approaches'],
                [33.7432, 241.7327, 'Port of Los Angeles'],
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

    /**
     * Interpolate a position along a corridor path based on progress (0.0 to 1.0).
     * Returns latitude, longitude, bearing heading, and waypoint location name.
     */
    public static function interpolatePositionAlongCorridor(array $corridor, float $progress): array
    {
        if (empty($corridor)) {
            return ['lat' => 0.0, 'lng' => 0.0, 'heading' => 90.0, 'location_name' => 'Unknown Sector', 'progress' => 0.0];
        }

        $n = count($corridor);
        if ($n === 1 || $progress <= 0.0) {
            return [
                'lat' => (float) $corridor[0][0],
                'lng' => (float) $corridor[0][1],
                'heading' => 90.0,
                'location_name' => $corridor[0][2] ?? 'Origin Port Terminal',
                'progress' => 0.0,
            ];
        }

        if ($progress >= 1.0) {
            $last = $corridor[$n - 1];
            return [
                'lat' => (float) $last[0],
                'lng' => (float) $last[1],
                'heading' => 90.0,
                'location_name' => $last[2] ?? 'Destination Discharge Berth',
                'progress' => 100.0,
            ];
        }

        // Calculate cumulative segment distances
        $distances = [];
        $totalDist = 0.0;
        for ($i = 0; $i < $n - 1; $i++) {
            $p1 = $corridor[$i];
            $p2 = $corridor[$i + 1];
            $d = hypot($p2[0] - $p1[0], $p2[1] - $p1[1]);
            $distances[] = $d;
            $totalDist += $d;
        }

        if ($totalDist <= 0) {
            return [
                'lat' => (float) $corridor[0][0],
                'lng' => (float) $corridor[0][1],
                'heading' => 90.0,
                'location_name' => $corridor[0][2] ?? 'Origin',
                'progress' => 0.0,
            ];
        }

        $targetDist = $progress * $totalDist;
        $accDist = 0.0;

        for ($i = 0; $i < count($distances); $i++) {
            $segDist = $distances[$i];
            if ($accDist + $segDist >= $targetDist || $i === count($distances) - 1) {
                $rem = $targetDist - $accDist;
                $fraction = ($segDist > 0) ? min(1.0, max(0.0, $rem / $segDist)) : 0.0;

                $p1 = $corridor[$i];
                $p2 = $corridor[$i + 1];

                $lat = $p1[0] + $fraction * ($p2[0] - $p1[0]);
                $lng = $p1[1] + $fraction * ($p2[1] - $p1[1]);
                $heading = self::calculateBearing($p1[0], $p1[1], $p2[0], $p2[1]);
                $locName = $p1[2] ?? ($p2[2] ?? 'Oceanic Transit Fairway');

                return [
                    'lat' => round($lat, 6),
                    'lng' => round($lng, 6),
                    'heading' => round($heading, 1),
                    'location_name' => $locName,
                    'progress' => round($progress * 100, 1),
                ];
            }
            $accDist += $segDist;
        }

        $last = $corridor[$n - 1];
        return [
            'lat' => (float) $last[0],
            'lng' => (float) $last[1],
            'heading' => 90.0,
            'location_name' => $last[2] ?? 'Destination',
            'progress' => 100.0,
        ];
    }
}
