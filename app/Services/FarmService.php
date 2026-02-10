<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class FarmService
{
    private readonly string $filePath;

    public function __construct(?string $filePath = null)
    {
        $this->filePath = $filePath ?? storage_path('app/private/farms.json');
    }

    /**
     * Get all farms with parsed coordinates for form/dropdown usage.
     *
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    public function getFarms(): array
    {
        try {
            if (!file_exists($this->filePath)) {
                return [
                    'success' => false,
                    'error' => 'Farms file not found',
                    'path' => $this->filePath,
                    'status' => 404,
                ];
            }

            $farmsJson = file_get_contents($this->filePath);

            if ($farmsJson === false) {
                return [
                    'success' => false,
                    'error' => 'Failed to read farms file',
                    'status' => 500,
                ];
            }

            $farms = $this->parseFarmsJson($farmsJson);

            if (empty($farms)) {
                return [
                    'success' => false,
                    'error' => 'No valid farms found',
                    'json_error' => json_last_error_msg(),
                    'status' => 500,
                ];
            }

            $farmsData = $this->extractFarmsData($farms);

            return [
                'success' => true,
                'data' => array_values($farmsData),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to load farms: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Failed to load farms',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'status' => 500,
            ];
        }
    }

    /**
     * Parse the JSON array format (one object per line).
     */
    private function parseFarmsJson(string $farmsJson): array
    {
        $farmsJson = trim($farmsJson);
        $farmsJson = trim($farmsJson, '[]');

        $lines = explode("\n", $farmsJson);
        $farms = [];

        foreach ($lines as $line) {
            $line = trim($line);
            $line = rtrim($line, ',');
            if (empty($line)) {
                continue;
            }

            $farm = json_decode($line, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($farm)) {
                $farms[] = $farm;
            } else {
                Log::warning('Failed to parse farm line: ' . substr($line, 0, 100) . ' - Error: ' . json_last_error_msg());
            }
        }

        return $farms;
    }

    /**
     * Extract center coordinates and normalize farm data.
     */
    private function extractFarmsData(array $farms): array
    {
        $farmsData = array_map(function ($farm) {
            $centerStr = $farm['center'] ?? '[]';
            $centerStr = trim($centerStr, '"');
            $center = json_decode($centerStr, true);

            if (!is_array($center) && is_string($centerStr)) {
                $parts = explode(',', $centerStr);
                if (count($parts) === 2) {
                    $center = [(float)trim($parts[0]), (float)trim($parts[1])];
                }
            }

            return [
                'id' => $farm['id'] ?? null,
                'name' => $farm['name'] ?? 'Unknown',
                'lat' => is_array($center) && count($center) >= 2 ? (float)$center[0] : null,
                'lng' => is_array($center) && count($center) >= 2 ? (float)$center[1] : null,
            ];
        }, $farms);

        return array_filter($farmsData, function ($farm) {
            return $farm['lat'] !== null && $farm['lng'] !== null && $farm['id'] !== null;
        });
    }
}
