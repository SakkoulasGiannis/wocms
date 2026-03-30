<?php

namespace Modules\RentalProperties\Livewire;

use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\RentalProperties\Models\RentalProperty;

class RentalPropertyForm extends Component
{
    use WithFileUploads;

    public ?int $propertyId = null;

    public string $title = '';

    public string $slug = '';

    public string $description = '';

    public string $property_type = 'apartment';

    public string $status = 'for_rent';

    public $price = 0;

    public string $currency = 'EUR';

    public $area = '';

    public $land_size = '';

    public $bedrooms = '';

    public $bathrooms = '';

    public $rooms = '';

    public $garages = 0;

    public $floor = '';

    public $year_built = '';

    public string $address = '';

    public string $city = '';

    public string $state = '';

    public string $country = 'Greece';

    public string $postal_code = '';

    public $latitude = '';

    public $longitude = '';

    public $featuredImageUpload = null;

    public $galleryUploads = [];

    public string $video_url = '';

    public string $virtual_tour_url = '';

    public string $currentFeaturedImage = '';

    public array $currentGallery = [];

    public string $features = '';

    public string $nearby_amenities = '';

    public string $meta_title = '';

    public string $meta_description = '';

    public bool $active = false;

    public bool $featured = false;

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:rental_properties,slug,'.$this->propertyId,
            'price' => 'required|numeric|min:0',
            'featuredImageUpload' => 'nullable|image|max:10240',
            'galleryUploads.*' => 'nullable|image|max:10240',
        ];
    }

    public function mount(?int $propertyId = null): void
    {
        $this->propertyId = $propertyId;
        if ($this->propertyId) {
            $this->loadProperty();
        }
    }

    public function loadProperty(): void
    {
        $p = RentalProperty::findOrFail($this->propertyId);
        $this->title = $p->title;
        $this->slug = $p->slug;
        $this->description = $p->description ?? '';
        $this->property_type = $p->property_type;
        $this->status = $p->status;
        $this->price = $p->price;
        $this->currency = $p->currency ?? 'EUR';
        $this->area = $p->area ?? '';
        $this->land_size = $p->land_size ?? '';
        $this->bedrooms = $p->bedrooms ?? '';
        $this->bathrooms = $p->bathrooms ?? '';
        $this->rooms = $p->rooms ?? '';
        $this->garages = $p->garages ?? 0;
        $this->floor = $p->floor ?? '';
        $this->year_built = $p->year_built ?? '';
        $this->address = $p->address ?? '';
        $this->city = $p->city ?? '';
        $this->state = $p->state ?? '';
        $this->country = $p->country ?? 'Greece';
        $this->postal_code = $p->postal_code ?? '';
        $this->latitude = $p->latitude ?? '';
        $this->longitude = $p->longitude ?? '';
        $this->video_url = $p->video_url ?? '';
        $this->virtual_tour_url = $p->virtual_tour_url ?? '';
        $this->features = is_array($p->features) ? implode(', ', $p->features) : '';
        $this->nearby_amenities = is_array($p->nearby_amenities) ? implode(', ', $p->nearby_amenities) : '';
        $this->meta_title = $p->meta_title ?? '';
        $this->meta_description = $p->meta_description ?? '';
        $this->active = $p->active;
        $this->featured = $p->featured;
        $this->currentFeaturedImage = $p->getFirstMediaUrl('featured_image', 'thumb') ?: $p->getFirstMediaUrl('featured_image');
        $this->currentGallery = $p->getMedia('gallery')->map(fn ($m) => ['id' => $m->id, 'url' => $m->getUrl('thumb') ?: $m->getUrl()])->toArray();
    }

    public function updatedTitle(): void
    {
        if (! $this->propertyId) {
            $this->slug = Str::slug($this->title);
        }
    }

    public function removeGalleryImage(int $mediaId): void
    {
        if ($this->propertyId) {
            $p = RentalProperty::findOrFail($this->propertyId);
            $p->getMedia('gallery')->where('id', $mediaId)->first()?->delete();
            $this->currentGallery = array_filter($this->currentGallery, fn ($img) => $img['id'] !== $mediaId);
        }
    }

    public function save(): void
    {
        $this->validate();
        $data = [
            'title' => $this->title, 'slug' => $this->slug,
            'description' => $this->description ?: null, 'property_type' => $this->property_type,
            'status' => $this->status, 'price' => $this->price, 'currency' => $this->currency,
            'area' => $this->area ?: null, 'land_size' => $this->land_size ?: null,
            'bedrooms' => $this->bedrooms ?: null, 'bathrooms' => $this->bathrooms ?: null,
            'rooms' => $this->rooms ?: null, 'garages' => $this->garages ?: 0,
            'floor' => $this->floor ?: null, 'year_built' => $this->year_built ?: null,
            'address' => $this->address ?: null, 'city' => $this->city ?: null,
            'state' => $this->state ?: null, 'country' => $this->country,
            'postal_code' => $this->postal_code ?: null,
            'latitude' => $this->latitude ?: null, 'longitude' => $this->longitude ?: null,
            'video_url' => $this->video_url ?: null, 'virtual_tour_url' => $this->virtual_tour_url ?: null,
            'features' => $this->features ? array_map('trim', explode(',', $this->features)) : null,
            'nearby_amenities' => $this->nearby_amenities ? array_map('trim', explode(',', $this->nearby_amenities)) : null,
            'meta_title' => $this->meta_title ?: null, 'meta_description' => $this->meta_description ?: null,
            'active' => $this->active, 'featured' => $this->featured,
        ];
        $p = RentalProperty::updateOrCreate(['id' => $this->propertyId], $data);
        $this->propertyId = $p->id;

        if ($this->featuredImageUpload) {
            $p->clearMediaCollection('featured_image');
            $p->addMedia($this->featuredImageUpload->getRealPath())->usingFileName($this->featuredImageUpload->getClientOriginalName())->toMediaCollection('featured_image');
            $this->featuredImageUpload = null;
        }
        if (! empty($this->galleryUploads)) {
            foreach ($this->galleryUploads as $upload) {
                $p->addMedia($upload->getRealPath())->usingFileName($upload->getClientOriginalName())->toMediaCollection('gallery');
            }
            $this->galleryUploads = [];
        }
        $this->loadProperty();
        session()->flash('success', 'Rental property saved.');
    }

    public function render()
    {
        return view('rentalproperties::livewire.rental-property-form', [
            'propertyTypes' => RentalProperty::getPropertyTypes(),
            'statuses' => RentalProperty::getStatuses(),
        ])->layout('layouts.admin-clean')->title($this->propertyId ? 'Edit Rental Property' : 'Create Rental Property');
    }
}
