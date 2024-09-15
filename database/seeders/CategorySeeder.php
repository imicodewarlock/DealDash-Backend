<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use GuzzleHttp\Client;
use App\Models\Category;
use Illuminate\Support\Facades\Log;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $apiKey = config('google_maps_api_key');
        $client = new Client();

        try {
            $response = $client->get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
                'query' => [
                    'location' => '40.712776,-74.005974', // New York City coordinates
                    'radius' => 50000,
                    'key' => $apiKey
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch categories from Google Places API: ' . $e->getMessage());
            return;
        }

        $places = json_decode($response->getBody(), true);

        if (isset($places['results'])) {
            foreach ($places['results'] as $place) {
                // Validate the name and image URL (if applicable)
                $categoryName = isset($place['types'][0]) ? $place['types'][0] : null;
                $imageUrl = isset($place['photos'][0]['photo_reference']) ? "https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=" . $place['photos'][0]['photo_reference'] . "&key=" . $apiKey : null;

                // Perform validation on category data
                if ($this->validateCategoryData($categoryName, $imageUrl)) {
                    try {
                        // Create or update the category
                        Category::firstOrCreate([
                            'name' => $categoryName,
                        ], [
                            'image' => $imageUrl
                        ]);
                    } catch (\Exception $e) {
                        Log::error("Failed to create category for {$categoryName}: " . $e->getMessage());
                    }
                } else {
                    Log::warning("Invalid data for category. Name: {$categoryName}, Image: {$imageUrl}");
                }
            }
        } else {
            Log::error('No results found in Google Places API response.');
        }
    }

    /**
     * Validate the category data
     *
     * @param string|null $name
     * @param string|null $imageUrl
     * @return bool
     */
    private function validateCategoryData(?string $name, ?string $imageUrl): bool
    {
        $isValid = true;

        // Validate name: should not be null or empty
        if (is_null($name) || trim($name) === '') {
            Log::error('Category name is missing or invalid.');
            $isValid = false;
        }

        // Validate image URL: should be a valid URL format, if present
        if (!is_null($imageUrl) && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            Log::error('Invalid image URL: ' . $imageUrl);
            $isValid = false;
        }

        return $isValid;
    }
}
