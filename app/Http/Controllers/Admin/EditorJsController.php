<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EditorJsController extends Controller
{
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
