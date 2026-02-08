<?php

namespace App\Http\Controllers;

use App\Models\Screenshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ScreenshotController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'screenshot' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $user = Auth::user();
        $file = $request->file('screenshot');

        Log::info('=== UPLOAD STARTED ===');

        // CORRECT DIRECTORY PATH
        $directory = "screenshots/{$user->id}/" . date('Y/m/d');
        $filename = Str::random(20) . '_' . time() . '.jpg';
        $path = "{$directory}/{$filename}";

        Log::info('User: ' . $user->id);
        Log::info('Target path: ' . $path);

        try {
            // METHOD 1: Use store() with correct disk
            $storedPath = $file->storeAs($directory, $filename, 'public');

            Log::info('Stored path: ' . $storedPath);
            Log::info('Full storage path: ' . storage_path('app/public/' . $storedPath));

            // Verify file was saved
            if (!Storage::disk('public')->exists($storedPath)) {
                throw new \Exception('File not saved to public disk');
            }

            $fileSize = Storage::disk('public')->size($storedPath);
            Log::info('File saved successfully: ' . $fileSize . ' bytes');

            // Save to database with correct path
            $screenshot = Screenshot::create([
                'user_id' => $user->id,
                'image_path' => $storedPath, // This should be the path from storeAs
            ]);

            Log::info('Database record created: ID ' . $screenshot->id);

            return response()->json([
                'success' => true,
                'message' => 'Screenshot saved',
                'debug' => [
                    'path' => $storedPath,
                    'full_path' => storage_path('app/public/' . $storedPath),
                    'file_size' => $fileSize,
                    'exists' => Storage::disk('public')->exists($storedPath),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Upload failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getScreenshot($path)
    {
        try {
            Log::info('Accessing screenshot: ' . $path);

            // Find screenshot
            $screenshot = Screenshot::where('image_path', $path)->firstOrFail();

            // Check admin
            $user = Auth::user();
            if (!$user || !$user->isAdmin()) {
                abort(403, 'Unauthorized');
            }

            // Get file from public disk
            $filePath = storage_path('app/public/' . $path);

            Log::info('Looking for file at: ' . $filePath);

            if (!file_exists($filePath)) {
                Log::error('File not found at: ' . $filePath);

                // Also check the wrong location (for existing files)
                $wrongPath = storage_path('app/private/public/' . $path);
                if (file_exists($wrongPath)) {
                    Log::info('Found file in wrong location, moving...');

                    // Move file to correct location
                    $correctDir = dirname($filePath);
                    if (!is_dir($correctDir)) {
                        mkdir($correctDir, 0755, true);
                    }

                    rename($wrongPath, $filePath);
                    Log::info('File moved to correct location');
                } else {
                    abort(404, 'Screenshot file not found');
                }
            }

            return response()->file($filePath, [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'public, max-age=3600',
            ]);

        } catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());
            abort(500, 'Error loading screenshot');
        }
    }
}
