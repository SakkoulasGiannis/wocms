<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EditorJsController extends Controller
{
    /**
     * List media library items as JSON for the in-editor media picker.
     */
    public function mediaList(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));
        $type = $request->input('type', 'image');
        $perPage = min(60, max(12, (int) $request->input('per_page', 24)));

        $query = Media::query()->orderByDesc('created_at');

        if ($type === 'image') {
            $query->where('mime_type', 'like', 'image/%');
        }
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%");
            });
        }

        $items = $query->paginate($perPage);

        return response()->json([
            'items' => $items->getCollection()->map(function (Media $m) {
                $url = $m->getFullUrl();
                $thumb = null;
                try {
                    if ($m->hasGeneratedConversion('thumb')) {
                        $thumb = $m->getFullUrl('thumb');
                    }
                } catch (\Throwable $e) {
                    $thumb = null;
                }
                return [
                    'id' => $m->id,
                    'name' => $m->name,
                    'file_name' => $m->file_name,
                    'mime_type' => $m->mime_type,
                    'size' => $m->size,
                    'url' => $url,
                    'thumb' => $thumb ?: $url,
                ];
            })->values(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    /**
     * Handle image upload from EditorJS image tool.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:10240',
        ]);

        $path = $request->file('image')->store('editorjs', 'public');

        return response()->json([
            'success' => 1,
            'file' => [
                'url' => asset('storage/'.$path),
            ],
        ]);
    }

    /**
     * Handle image fetch by URL for EditorJS image tool.
     */
    public function fetchImageByUrl(Request $request): JsonResponse
    {
        $url = $request->input('url');

        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['success' => 0, 'message' => 'Invalid URL']);
        }

        return response()->json([
            'success' => 1,
            'file' => [
                'url' => $url,
            ],
        ]);
    }

    /**
     * Handle file attachment upload from EditorJS attaches tool.
     */
    public function uploadFile(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:20480',
        ]);

        $file = $request->file('file');
        $path = $file->store('editorjs/files', 'public');

        return response()->json([
            'success' => 1,
            'file' => [
                'url' => asset('storage/'.$path),
                'size' => $file->getSize(),
                'name' => $file->getClientOriginalName(),
                'extension' => $file->getClientOriginalExtension(),
            ],
        ]);
    }
}
