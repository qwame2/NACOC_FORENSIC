<?php

namespace App\Http\Controllers;

use App\Services\GeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeocodingController extends Controller
{
    public function __construct(
        protected GeocodingService $geocodingService
    ) {}

    /**
     * Search coordinates and place suggestions for a query.
     */
    public function search(Request $request): JsonResponse
    {
        $query = (string) $request->query('q', '');
        if (trim($query) === '') {
            return response()->json([
                'status' => 'success',
                'data'   => [],
            ]);
        }

        $results = $this->geocodingService->search($query);

        return response()->json([
            'status' => 'success',
            'count'  => count($results),
            'data'   => $results,
        ]);
    }

    /**
     * Reverse geocode a latitude and longitude into human-readable place description.
     */
    public function reverse(Request $request): JsonResponse
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        if (!is_numeric($lat) || !is_numeric($lng)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Valid numeric latitude and longitude parameters are required.',
            ], 422);
        }

        $result = $this->geocodingService->reverse((float) $lat, (float) $lng);

        return response()->json([
            'status' => 'success',
            'data'   => $result,
        ]);
    }
}

