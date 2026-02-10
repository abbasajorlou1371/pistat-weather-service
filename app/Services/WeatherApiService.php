<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WeatherApiService
{
    private const BASE_URL = 'http://api.weatherapi.com/v1';

    private readonly ?string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.weatherapi.key');
    }

    /**
     * Get current weather for coordinates.
     *
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @param string $lang Language code (default: 'en')
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    public function getCurrentWeather(float $lat, float $lng, string $lang = 'en'): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => 'Weather API key not configured',
                'status' => 500,
            ];
        }

        try {
            $response = Http::get(self::BASE_URL . '/current.json', [
                'key' => $this->apiKey,
                'q' => "{$lat},{$lng}",
                'lang' => $lang,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Weather API error',
                'message' => $response->body(),
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch weather data',
                'message' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    /**
     * Get historical weather for coordinates and date.
     *
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @param string $date Date in YYYY-MM-DD format
     * @param string $lang Language code (default: 'en')
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    public function getHistoricalWeather(float $lat, float $lng, string $date, string $lang = 'en'): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => 'Weather API key not configured',
                'status' => 500,
            ];
        }

        try {
            $response = Http::get(self::BASE_URL . '/history.json', [
                'key' => $this->apiKey,
                'q' => "{$lat},{$lng}",
                'dt' => $date,
                'lang' => $lang,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Weather API error',
                'message' => $response->body(),
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch weather data',
                'message' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    /**
     * Get historical weather for coordinates and date range.
     * Fetches data for each day in the range and filters by temperature.
     *
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @param string $startDate Start date in YYYY-MM-DD format
     * @param string $endDate End date in YYYY-MM-DD format
     * @param float|null $minTemp Minimum temperature filter (optional)
     * @param float|null $maxTemp Maximum temperature filter (optional)
     * @param string $lang Language code (default: 'en')
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    public function getHistoricalWeatherRange(
        float $lat,
        float $lng,
        string $startDate,
        string $endDate,
        ?float $minTemp = null,
        ?float $maxTemp = null,
        string $lang = 'en'
    ): array {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => 'Weather API key not configured',
                'status' => 500,
            ];
        }

        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $end->setTime(23, 59, 59); // Include the end date

            if ($start > $end) {
                return [
                    'success' => false,
                    'error' => 'Start date must be before or equal to end date',
                    'status' => 400,
                ];
            }

            $dailyData = [];
            $current = clone $start;

            // Fetch data for each day in the range
            while ($current <= $end) {
                $dateStr = $current->format('Y-m-d');
                $result = $this->getHistoricalWeather($lat, $lng, $dateStr, $lang);

                if ($result['success'] && isset($result['data']['forecast']['forecastday'][0])) {
                    $dayData = $result['data']['forecast']['forecastday'][0];

                    // Process hourly data and aggregate by day
                    if (isset($dayData['hour']) && is_array($dayData['hour'])) {
                        $temperatures = [];
                        $hoursInRange = 0;

                        foreach ($dayData['hour'] as $hour) {
                            $temp = $hour['temp_c'] ?? null;

                            if ($temp === null) {
                                continue;
                            }

                            // Count hours with temperature within the specified range
                            // If user provided both min and max, use them; otherwise default to 0-7
                            $countMin = ($minTemp !== null && $maxTemp !== null) ? $minTemp : 0;
                            $countMax = ($minTemp !== null && $maxTemp !== null) ? $maxTemp : 7;

                            if ($temp >= $countMin && $temp <= $countMax) {
                                $hoursInRange++;
                            }

                            // Apply temperature filter if provided (for average calculation)
                            if ($minTemp !== null && $temp < $minTemp) {
                                continue;
                            }
                            if ($maxTemp !== null && $temp > $maxTemp) {
                                continue;
                            }

                            $temperatures[] = $temp;
                        }

                        // Calculate daily average temperature (only show days with filtered temperatures)
                        if (count($temperatures) > 0) {
                            $avgTemp = array_sum($temperatures) / count($temperatures);

                            $dailyData[] = [
                                'date' => $dayData['date'],
                                'temp_c' => round($avgTemp, 1),
                                'temp_hours_count' => $hoursInRange,
                            ];
                        }
                    }
                }

                $current->modify('+1 day');
            }

            // Sort by date
            usort($dailyData, function ($a, $b) {
                return strcmp($a['date'], $b['date']);
            });

            // Determine the temperature range used for counting
            // If user provided both min and max, use them; otherwise default to 0-7
            $countMin = ($minTemp !== null && $maxTemp !== null) ? $minTemp : 0;
            $countMax = ($minTemp !== null && $maxTemp !== null) ? $maxTemp : 7;

            return [
                'success' => true,
                'data' => $dailyData,
                'temp_range' => [
                    'min' => $countMin,
                    'max' => $countMax,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch weather data',
                'message' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }
}
