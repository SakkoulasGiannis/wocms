<?php

namespace App\Livewire\Admin\Media;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Models\MediaModel;
use Illuminate\Support\Facades\Storage;

class MediaLibrary extends Component
{
    use WithFileUploads, WithPagination;

    public $uploads = [];
    public $search = '';
    public $viewMode = 'grid'; // grid or list
    public $filterType = 'all'; // all, image, video, document
    public $selectedMedia = [];
    public $showDetails = false;
    public $detailsMediaId = null; // Store ID instead of object
    public $bulkAction = '';

    // For modal picker mode
    public $pickerMode = false;
    public $allowMultiple = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterType' => ['except' => 'all'],
        'viewMode' => ['except' => 'grid'],
        'detailsMediaId' => ['except' => null, 'as' => 'details']
    ];

    public function mount($pickerMode = false, $allowMultiple = false)
    {
        $this->pickerMode = $pickerMode;
        $this->allowMultiple = $allowMultiple;

        // Auto-open details panel if detailsMediaId is in URL
        if ($this->detailsMediaId) {
            $this->showDetails = true;
        }
    }

    public function updatedUploads()
    {
        $this->validate([
            'uploads.*' => 'file|max:10240', // 10MB max
        ]);

        foreach ($this->uploads as $upload) {
            // Create or get a MediaModel instance
            $mediaModel = MediaModel::firstOrCreate(['name' => 'uploads']);

            // Use Spatie Media Library to handle upload and conversions
            $mediaModel->addMedia($upload)
                ->withCustomProperties([
                    'uploaded_by' => auth()->user()->name ?? 'Unknown',
                ])
                ->toMediaCollection('default', 'public');
        }

        $this->uploads = [];
        session()->flash('success', 'Files uploaded successfully! Image sizes generated automatically.');
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterType()
    {
        $this->resetPage();
    }

    public function toggleSelection($mediaId)
    {
        if (in_array($mediaId, $this->selectedMedia)) {
            $this->selectedMedia = array_diff($this->selectedMedia, [$mediaId]);
        } else {
            if (!$this->allowMultiple && $this->pickerMode) {
                $this->selectedMedia = [$mediaId];
            } else {
                $this->selectedMedia[] = $mediaId;
            }
        }
    }

    public function selectAll()
    {
        $mediaIds = $this->getMediaQuery()->pluck('id')->toArray();
        $this->selectedMedia = $mediaIds;
    }

    public function deselectAll()
    {
        $this->selectedMedia = [];
    }

    public function showDetailsPanel($mediaId)
    {
        \Log::info('showDetailsPanel called', ['mediaId' => $mediaId]);
        $this->detailsMediaId = $mediaId;
        $this->showDetails = true;
    }

    public function closeDetails()
    {
        $this->showDetails = false;
        $this->detailsMediaId = null;
    }

    public function getDetailsMediaProperty()
    {
        if ($this->detailsMediaId) {
            return Media::find($this->detailsMediaId);
        }
        return null;
    }

    public function deleteSelected()
    {
        if (empty($this->selectedMedia)) {
            return;
        }

        foreach ($this->selectedMedia as $mediaId) {
            $media = Media::find($mediaId);
            if ($media) {
                Storage::disk($media->disk)->delete($media->file_name);
                $media->delete();
            }
        }

        $this->selectedMedia = [];
        session()->flash('success', count($this->selectedMedia) . ' file(s) deleted successfully!');
        $this->resetPage();
    }

    public function deleteMedia($mediaId)
    {
        $media = Media::find($mediaId);
        if ($media) {
            Storage::disk($media->disk)->delete($media->file_name);
            $media->delete();
            session()->flash('success', 'File deleted successfully!');
        }

        $this->closeDetails();
        $this->resetPage();
    }

    public function executeBulkAction()
    {
        if (empty($this->selectedMedia) || empty($this->bulkAction)) {
            return;
        }

        switch ($this->bulkAction) {
            case 'delete':
                $this->deleteSelected();
                break;
        }

        $this->bulkAction = '';
    }

    public function selectMediaForPicker()
    {
        if ($this->pickerMode && !empty($this->selectedMedia)) {
            $selectedMediaItems = Media::whereIn('id', $this->selectedMedia)->get();
            $this->dispatch('mediaSelected', $selectedMediaItems->toArray());
        }
    }

    protected function getMediaQuery()
    {
        $query = Media::query()->latest();

        // Search
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('file_name', 'like', '%' . $this->search . '%');
            });
        }

        // Filter by type
        if ($this->filterType !== 'all') {
            switch ($this->filterType) {
                case 'image':
                    $query->where('mime_type', 'like', 'image/%');
                    break;
                case 'video':
                    $query->where('mime_type', 'like', 'video/%');
                    break;
                case 'document':
                    $query->whereIn('mime_type', [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    ]);
                    break;
            }
        }

        return $query;
    }

    public function render()
    {
        $media = $this->getMediaQuery()->paginate(24);

        return view('livewire.admin.media.media-library', [
            'media' => $media,
            'totalMedia' => Media::count(),
            'totalSize' => Media::sum('size'),
            'detailsMedia' => $this->detailsMedia, // Pass computed property to view
        ])->layout($this->pickerMode ? null : 'layouts.admin-clean');
    }
}
