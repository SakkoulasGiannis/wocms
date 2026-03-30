<?php

namespace Modules\Properties\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Modules\Properties\Models\Property;
use Modules\Properties\Services\CrmApiClient;
use Modules\RentalProperties\Models\RentalProperty;

class SyncPropertiesCommand extends Command
{
    protected $signature = 'properties:sync {--type=all : Type to sync: rentals, sales, or all}';

    protected $description = 'Sync properties from CRM API (crm.kretaeiendom.com)';

    protected int $created = 0;

    protected int $updated = 0;

    protected int $failed = 0;

    public function handle(): int
    {
        $client = new CrmApiClient;

        if (! $client->isConfigured()) {
            $this->error('CRM API is not configured. Set crm_api_url and crm_api_token in Settings.');

            return self::FAILURE;
        }

        $type = $this->option('type');

        if (in_array($type, ['sales', 'all'])) {
            $this->syncSales($client);
        }

        if (in_array($type, ['rentals', 'all'])) {
            $this->syncRentals($client);
        }

        $this->newLine();
        $this->info("Sync complete: {$this->created} created, {$this->updated} updated, {$this->failed} failed.");

        return self::SUCCESS;
    }

    protected function syncSales(CrmApiClient $client): void
    {
        $this->info('Fetching sales from CRM...');
        $result = $client->getAllSales();

        if (! $result['success']) {
            $this->error('Failed to fetch sales: '.($result['error'] ?? 'Unknown'));

            return;
        }

        $this->info('Found '.count($result['data']).' sale listings.');

        foreach ($result['data'] as $item) {
            $this->syncProperty($item, 'sale', $client);
        }
    }

    protected function syncRentals(CrmApiClient $client): void
    {
        $this->info('Fetching rentals from CRM...');
        $result = $client->getAllRentals();

        if (! $result['success']) {
            $this->error('Failed to fetch rentals: '.($result['error'] ?? 'Unknown'));

            return;
        }

        $this->info('Found '.count($result['data']).' rental listings.');

        foreach ($result['data'] as $item) {
            $this->syncRentalProperty($item, $client);
        }
    }

    protected function syncProperty(array $item, string $type, CrmApiClient $client): void
    {
        $externalId = (string) ($item['id'] ?? null);
        if (! $externalId) {
            $this->failed++;

            return;
        }

        try {
            $data = $this->mapPropertyData($item, 'for_sale');
            $property = Property::updateOrCreate(
                ['external_id' => $externalId],
                $data
            );

            if ($property->wasRecentlyCreated) {
                $this->created++;
                $this->line("  Created: {$data['title']}");
            } else {
                $this->updated++;
                $this->line("  Updated: {$data['title']}");
            }

            $this->syncMedia($property, $item, $client);
        } catch (\Exception $e) {
            $this->failed++;
            $this->warn("  Failed [{$externalId}]: {$e->getMessage()}");
        }
    }

    protected function syncRentalProperty(array $item, CrmApiClient $client): void
    {
        $externalId = (string) ($item['id'] ?? null);
        if (! $externalId) {
            $this->failed++;

            return;
        }

        try {
            $data = $this->mapPropertyData($item, 'for_rent');
            $property = RentalProperty::updateOrCreate(
                ['external_id' => $externalId],
                $data
            );

            if ($property->wasRecentlyCreated) {
                $this->created++;
                $this->line("  Created: {$data['title']}");
            } else {
                $this->updated++;
                $this->line("  Updated: {$data['title']}");
            }

            $this->syncMedia($property, $item, $client);
        } catch (\Exception $e) {
            $this->failed++;
            $this->warn("  Failed [{$externalId}]: {$e->getMessage()}");
        }
    }

    /**
     * Map CRM API fields to local model fields.
     * CRM response has nested structure: location.city, details.bedrooms, pricing.price, etc.
     */
    protected function mapPropertyData(array $item, string $defaultStatus): array
    {
        $location = $item['location'] ?? [];
        $details = $item['details'] ?? [];
        $pricing = $item['pricing'] ?? [];
        $rules = $item['rules'] ?? [];

        $title = $item['name'] ?? $item['title'] ?? 'Untitled';

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.($item['id'] ?? rand(1000, 9999)),
            'description' => $item['description'] ?? '',
            'property_type' => $this->mapPropertyType($details['property_type'] ?? $details['room_type'] ?? ''),
            'status' => $defaultStatus,
            'price' => (float) ($pricing['price'] ?? $item['price'] ?? 0),
            'currency' => $pricing['currency'] ?? 'EUR',
            'area' => ! empty($details['square_meters']) ? (float) $details['square_meters'] : null,
            'bedrooms' => $details['bedrooms'] ?? null,
            'bathrooms' => $details['bathrooms'] ?? null,
            'rooms' => $details['beds'] ?? $details['rooms'] ?? null,
            'address' => $location['address'] ?? '',
            'city' => $location['city'] ?? '',
            'state' => $location['state'] ?? $location['region'] ?? '',
            'country' => $location['country'] ?? 'Greece',
            'postal_code' => $location['postal_code'] ?? $location['zip_code'] ?? '',
            'latitude' => $location['latitude'] ?? $location['lat'] ?? null,
            'longitude' => $location['longitude'] ?? $location['lng'] ?? null,
            'features' => $item['amenities'] ?? [],
            'active' => true,
        ];
    }

    protected function mapPropertyType(string $type): string
    {
        $type = strtolower(trim($type));
        $map = [
            'apartment' => 'apartment', 'flat' => 'apartment', 'διαμέρισμα' => 'apartment',
            'house' => 'house', 'σπίτι' => 'house', 'detached' => 'house',
            'villa' => 'villa', 'βίλα' => 'villa',
            'studio' => 'studio', 'στούντιο' => 'studio',
            'office' => 'office', 'γραφείο' => 'office',
            'commercial' => 'commercial', 'επαγγελματικό' => 'commercial', 'shop' => 'commercial',
            'land' => 'land', 'plot' => 'land', 'οικόπεδο' => 'land',
        ];

        return $map[$type] ?? 'other';
    }

    protected function extractFeatures(array $item): array
    {
        if (isset($item['features']) && is_array($item['features'])) {
            return $item['features'];
        }

        if (isset($item['amenities']) && is_array($item['amenities'])) {
            return $item['amenities'];
        }

        // Try to collect boolean feature flags
        $features = [];
        $featureKeys = [
            'has_pool' => 'Pool', 'pool' => 'Pool',
            'has_garden' => 'Garden', 'garden' => 'Garden',
            'has_garage' => 'Garage', 'garage' => 'Garage',
            'has_balcony' => 'Balcony', 'balcony' => 'Balcony',
            'has_elevator' => 'Elevator', 'elevator' => 'Elevator',
            'has_parking' => 'Parking', 'parking' => 'Parking',
            'has_storage' => 'Storage', 'storage_room' => 'Storage',
            'air_conditioning' => 'Air Conditioning', 'has_ac' => 'Air Conditioning',
            'furnished' => 'Furnished', 'is_furnished' => 'Furnished',
            'heating' => 'Heating', 'has_heating' => 'Heating',
            'sea_view' => 'Sea View', 'has_sea_view' => 'Sea View',
        ];

        foreach ($featureKeys as $key => $label) {
            if (! empty($item[$key]) && $item[$key] !== false && $item[$key] !== 0) {
                $features[] = $label;
            }
        }

        return array_unique($features);
    }

    protected function syncMedia($property, array $item, CrmApiClient $client): void
    {
        $photos = $item['photos'] ?? $item['images'] ?? $item['gallery'] ?? [];

        if (empty($photos)) {
            return;
        }

        // Extract URLs from photos array (could be strings or objects with url key)
        $urls = [];
        foreach ($photos as $photo) {
            if (is_string($photo)) {
                $urls[] = $photo;
            } elseif (is_array($photo)) {
                $urls[] = $photo['url'] ?? $photo['original'] ?? $photo['path'] ?? '';
            }
        }
        $urls = array_filter($urls);

        if (empty($urls)) {
            return;
        }

        // Only re-download if media count differs (avoid re-downloading on every sync)
        $existingCount = $property->getMedia('featured_image')->count() + $property->getMedia('gallery')->count();
        if ($existingCount >= count($urls)) {
            return;
        }

        // Featured image = first photo
        if ($property->getMedia('featured_image')->isEmpty()) {
            try {
                $property->addMediaFromUrl($urls[0])->toMediaCollection('featured_image');
            } catch (\Exception $e) {
                // Skip silently
            }
        }

        // Gallery = remaining photos
        $existingGallery = $property->getMedia('gallery')->count();
        $galleryUrls = array_slice($urls, 1);

        if ($existingGallery < count($galleryUrls)) {
            foreach (array_slice($galleryUrls, $existingGallery) as $url) {
                try {
                    $property->addMediaFromUrl($url)->toMediaCollection('gallery');
                } catch (\Exception $e) {
                    // Skip silently
                }
            }
        }
    }
}
