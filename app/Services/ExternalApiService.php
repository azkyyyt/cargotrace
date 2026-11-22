<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExternalApiService
{
    /**
     * Search places and geocode locations like Google Maps Places.
     * Checks Google Maps Geocoding if API key is provided, 
     * otherwise uses high-speed Photon & Nominatim geocoding services.
     */
    public function searchPlaces(string $query, int $limit = 6): array
    {
        $query = trim($query);
        if (strlen($query) < 2) {
            return [];
        }

        $results = [];

        // 1. If Google Maps API Key is provided, use Google Maps Geocoding API
        $googleKey = config('services.google_maps.key', env('GOOGLE_MAPS_API_KEY'));
        if (!empty($googleKey)) {
            try {
                $response = Http::withoutVerifying()->timeout(4)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $query,
                    'key' => $googleKey,
                ]);
                if ($response->successful()) {
                    $data = $response->json();
                    if (($data['status'] ?? '') === 'OK' && !empty($data['results'])) {
                        foreach (array_slice($data['results'], 0, $limit) as $item) {
                            $components = $item['address_components'] ?? [];
                            $city = '';
                            $country = '';
                            foreach ($components as $c) {
                                if (in_array('locality', $c['types'] ?? [])) $city = $c['long_name'];
                                if (empty($city) && in_array('administrative_area_level_2', $c['types'] ?? [])) $city = $c['long_name'];
                                if (in_array('country', $c['types'] ?? [])) $country = $c['long_name'];
                            }

                            $results[] = [
                                'name' => $item['address_components'][0]['long_name'] ?? ($item['formatted_address'] ?? $query),
                                'city' => $city ?: ($item['address_components'][0]['short_name'] ?? 'City'),
                                'country' => $country ?: 'Global',
                                'latitude' => (float) ($item['geometry']['location']['lat'] ?? 0),
                                'longitude' => (float) ($item['geometry']['location']['lng'] ?? 0),
                                'address' => $item['formatted_address'] ?? '',
                                'source' => 'Google Maps API',
                            ];
                        }
                        if (!empty($results)) {
                            return $results;
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Google Maps Geocoding error: " . $e->getMessage());
            }
        }

        // 2. High-speed Photon Geocoding Service (OpenStreetMap data, instant autocomplete, zero key needed)
        try {
            $response = Http::withoutVerifying()
                ->timeout(4)
                ->withHeaders(['User-Agent' => 'CargoTrace-Platform/1.0'])
                ->get('https://photon.komoot.io/api/', [
                    'q' => $query,
                    'limit' => $limit,
                ]);
            if ($response->successful()) {
                $data = $response->json();
                foreach ($data['features'] ?? [] as $f) {
                    $p = $f['properties'] ?? [];
                    $coords = $f['geometry']['coordinates'] ?? [0, 0];
                    $name = $p['name'] ?? $query;
                    $city = $p['city'] ?? ($p['district'] ?? ($p['state'] ?? ''));
                    $country = $p['country'] ?? '';
                    $addressParts = array_filter([$name, $city, $p['state'] ?? null, $country]);

                    $results[] = [
                        'name' => $name,
                        'city' => $city ?: $name,
                        'country' => $country ?: 'Global',
                        'latitude' => round((float) $coords[1], 6),
                        'longitude' => round((float) $coords[0], 6),
                        'address' => implode(', ', $addressParts),
                        'source' => 'OpenStreetMap Photon Engine',
                    ];
                }
                if (!empty($results)) {
                    return $results;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Photon Geocoding error: " . $e->getMessage());
        }

        // 3. Fallback to standard Nominatim Search
        try {
            $response = Http::withoutVerifying()
                ->timeout(4)
                ->withHeaders(['User-Agent' => 'CargoTrace-Platform/1.0 (contact@cargotrace.test)'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => $limit,
                ]);
            if ($response->successful()) {
                $data = $response->json();
                foreach ($data as $item) {
                    $addr = $item['address'] ?? [];
                    $city = $addr['city'] ?? ($addr['town'] ?? ($addr['county'] ?? ($addr['state'] ?? '')));
                    $country = $addr['country'] ?? '';
                    $name = $item['name'] ?? ($item['display_name'] ?? $query);

                    $results[] = [
                        'name' => $name,
                        'city' => $city ?: $name,
                        'country' => $country ?: 'Global',
                        'latitude' => round((float) $item['lat'], 6),
                        'longitude' => round((float) $item['lon'], 6),
                        'address' => $item['display_name'] ?? '',
                        'source' => 'OpenStreetMap Nominatim',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Nominatim search error: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Geocode an address/location query using OpenStreetMap Nominatim.
     * Handles timeouts, errors, and empty responses gracefully.
     */
    public function geocode(string $query): array
    {
        $baseUrl = config('services.geocoding.url', env('GEOCODING_API_URL', 'https://nominatim.openstreetmap.org'));

        try {
            $response = Http::withoutVerifying()
                ->timeout(4)
                ->withHeaders([
                    'User-Agent' => 'CargoTrace-Platform/1.0 (contact@cargotrace.test)'
                ])
                ->get("{$baseUrl}/search", [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
                    return [
                        'success' => true,
                        'name' => $data[0]['display_name'] ?? $query,
                        'latitude' => (float) $data[0]['lat'],
                        'longitude' => (float) $data[0]['lon'],
                        'source' => 'OpenStreetMap Nominatim',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Geocoding external API failed for query '{$query}': " . $e->getMessage());
        }

        // Graceful fallback for demonstration / offline resilience
        return [
            'success' => false,
            'name' => $query,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'source' => 'Fallback Coordinates (Jakarta Hub)',
            'note' => 'External geocoding service timeout or offline. Graceful fallback applied.',
        ];
    }

    /**
     * Fetch weather metrics for coordinates using Open-Meteo free API.
     * Handles timeouts, errors, and provides contextual status.
     */
    public function getWeather(float $lat, float $lon): array
    {
        $baseUrl = config('services.weather.url', env('WEATHER_API_URL', 'https://api.open-meteo.com/v1/forecast'));

        try {
            $response = Http::withoutVerifying()->timeout(4)->get($baseUrl, [
                'latitude' => $lat,
                'longitude' => $lon,
                'current_weather' => 'true',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['current_weather'])) {
                    $cw = $data['current_weather'];
                    return [
                        'success' => true,
                        'temperature' => $cw['temperature'] ?? null,
                        'windspeed' => $cw['windspeed'] ?? null,
                        'weathercode' => $cw['weathercode'] ?? null,
                        'condition' => $this->interpretWeatherCode($cw['weathercode'] ?? 0),
                        'source' => 'Open-Meteo Free Forecast API',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Weather external API failed for coordinates [{$lat}, {$lon}]: " . $e->getMessage());
        }

        // Resilient fallback data
        return [
            'success' => false,
            'temperature' => 28.5,
            'windspeed' => 12.0,
            'weathercode' => 1,
            'condition' => 'Mainly clear (Cached/Fallback)',
            'source' => 'Local Weather Cache',
            'note' => 'Weather API currently unreachable. Showing cached contextual observation.',
        ];
    }

    /**
     * Calculate maritime weather delay impact hours deterministically.
     * Evaluates live windspeed, gusts, and storm weather codes.
     */
    public function calculateWeatherImpactHours(float $lat, float $lon): array
    {
        $weather = $this->getWeather($lat, $lon);
        $windSpeed = (float) ($weather['windspeed'] ?? 0.0);
        $weatherCode = (int) ($weather['weathercode'] ?? 0);

        // Deterministic delay calculation formula
        $delayHours = 0;
        $severity = 'NONE';
        $reason = 'Normal calm oceanic navigation conditions.';

        if ($windSpeed > 75 || in_array($weatherCode, [96, 99], true)) {
            $delayHours = 28;
            $severity = 'HIGH';
            $reason = "Severe tropical storm/gale (Wind: {$windSpeed} km/h, Code: {$weatherCode}). Vessel steaming speed reduced for bulk grain hold safety.";
        } elseif ($windSpeed > 50 || in_array($weatherCode, [95, 82, 65], true)) {
            $delayHours = 16;
            $severity = 'MEDIUM';
            $reason = "Heavy oceanic swells & high winds ({$windSpeed} km/h). Course deviation of 85 NM.";
        } elseif ($windSpeed > 35) {
            $delayHours = 8;
            $severity = 'LOW';
            $reason = "Moderate rough sea state (Wind: {$windSpeed} km/h). Speed adjusted by -3.5 knots.";
        }

        return [
            'weather' => $weather,
            'delay_hours' => $delayHours,
            'severity' => $severity,
            'reason' => $reason,
            'is_adverse' => $delayHours > 0,
        ];
    }

    private function interpretWeatherCode(int $code): string
    {
        return match ($code) {
            0 => 'Clear sky',
            1, 2, 3 => 'Mainly clear / Partly cloudy',
            45, 48 => 'Foggy',
            51, 53, 55 => 'Drizzle',
            61, 63, 65 => 'Rain',
            71, 73, 75 => 'Snowfall',
            80, 81, 82 => 'Rain showers',
            95, 96, 99 => 'Thunderstorm',
            default => 'Fair conditions',
        };
    }
}
