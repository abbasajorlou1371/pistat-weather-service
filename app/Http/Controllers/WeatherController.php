<?php

namespace App\Http\Controllers;

use App\Services\FarmService;
use App\Services\WeatherApiService;
use Illuminate\Http\Request;

class WeatherController extends Controller
{
    public function __construct(
        private readonly FarmService $farmService,
        private readonly WeatherApiService $weatherApiService
    ) {}

    /**
     * Get all farms from JSON file (for form/dropdown usage)
     */
    public function getFarms()
    {
        $result = $this->farmService->getFarms();

        if ($result['success']) {
            return response()->json($result['data']);
        }

        $status = $result['status'] ?? 500;
        $payload = ['error' => $result['error']];

        if (isset($result['path'])) {
            $payload['path'] = $result['path'];
        }
        if (isset($result['message'])) {
            $payload['message'] = $result['message'];
        }
        if (isset($result['json_error'])) {
            $payload['json_error'] = $result['json_error'];
        }
        if (isset($result['trace'])) {
            $payload['trace'] = $result['trace'];
        }

        return response()->json($payload, $status);
    }

    /**
     * Get weather data for a specific farm/location
     */
    public function getWeather(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'date' => 'nullable|date|before_or_equal:today|after_or_equal:' . now()->subDays(365)->format('Y-m-d'),
        ]);

        $lat = (float) $request->input('lat');
        $lng = (float) $request->input('lng');
        $date = $request->input('date');

        // If date is provided, get historical weather, otherwise get current weather
        if ($date) {
            $result = $this->weatherApiService->getHistoricalWeather($lat, $lng, $date);
        } else {
            $result = $this->weatherApiService->getCurrentWeather($lat, $lng);
        }

        if ($result['success']) {
            return response()->json($result['data']);
        }

        $status = $result['status'] ?? 500;
        $payload = ['error' => $result['error']];

        if (isset($result['message'])) {
            $payload['message'] = $result['message'];
        }

        return response()->json($payload, $status);
    }

    /**
     * Get filtered weather data for a date range and temperature range
     */
    public function getFilteredWeather(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'start_date' => 'required|date|before_or_equal:today|after_or_equal:' . now()->subDays(365)->format('Y-m-d'),
            'end_date' => 'required|date|before_or_equal:today|after_or_equal:' . now()->subDays(365)->format('Y-m-d'),
            'min_temp' => 'nullable|numeric',
            'max_temp' => 'nullable|numeric',
        ]);

        $lat = (float) $request->input('lat');
        $lng = (float) $request->input('lng');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $minTemp = $request->has('min_temp') ? (float) $request->input('min_temp') : null;
        $maxTemp = $request->has('max_temp') ? (float) $request->input('max_temp') : null;

        // Validate date range
        if (strtotime($startDate) > strtotime($endDate)) {
            return response()->json([
                'error' => 'Start date must be before or equal to end date'
            ], 400);
        }

        // Validate date range is within 90 days
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $now = new \DateTime();
        $ninetyDaysAgo = (clone $now)->modify('-365 days');

        if ($start < $ninetyDaysAgo || $end > $now) {
            return response()->json([
                'error' => 'Date range must be within the last 365 days'
            ], 400);
        }

        $result = $this->weatherApiService->getHistoricalWeatherRange(
            $lat,
            $lng,
            $startDate,
            $endDate,
            $minTemp,
            $maxTemp
        );

        if ($result['success']) {
            return response()->json([
                'data' => $result['data'],
                'temp_range' => $result['temp_range'] ?? [
                    'min' => $minTemp ?? 0,
                    'max' => $maxTemp ?? 7,
                ],
            ]);
        }

        $status = $result['status'] ?? 500;
        $payload = ['error' => $result['error']];

        if (isset($result['message'])) {
            $payload['message'] = $result['message'];
        }

        return response()->json($payload, $status);
    }
}
