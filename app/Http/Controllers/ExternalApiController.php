<?php

namespace App\Http\Controllers;

use App\Services\ExternalApiService;
use Illuminate\Http\Request;

class ExternalApiController extends Controller
{
    protected ExternalApiService $externalApiService;

    public function __construct(ExternalApiService $externalApiService)
    {
        $this->externalApiService = $externalApiService;
    }

    public function index(Request $request)
    {
        $geoQuery = $request->input('location', 'Port of Singapore');
        $geoResult = null;
        $weatherResult = null;

        if ($request->has('location')) {
            $geoResult = $this->externalApiService->geocode($geoQuery);
            if ($geoResult && isset($geoResult['latitude'])) {
                $weatherResult = $this->externalApiService->getWeather($geoResult['latitude'], $geoResult['longitude']);
            }
        }

        return view('integrations.index', compact('geoQuery', 'geoResult', 'weatherResult'));
    }
}
