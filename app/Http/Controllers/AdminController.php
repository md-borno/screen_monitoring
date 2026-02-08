<?php

namespace App\Http\Controllers;

use App\Models\Screenshot;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function dashboard()
    {
        $screenshots = Screenshot::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $employees = User::where('role', 'employee')->get();

        // Add full URLs to each screenshot for debugging
        $screenshots->getCollection()->transform(function ($screenshot) {
            $screenshot->full_url = route('admin.screenshot.view', ['path' => $screenshot->image_path]);
            $screenshot->storage_path = storage_path("app/public/{$screenshot->image_path}");
            $screenshot->file_exists = file_exists($screenshot->storage_path);
            return $screenshot;
        });

        return view('admin.dashboard', compact('screenshots', 'employees'));
    }

    public function filter(Request $request)
    {
        $query = Screenshot::with('user');

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }

        $screenshots = $query->orderBy('created_at', 'desc')->paginate(20);
        $employees = User::where('role', 'employee')->get();

        return view('admin.dashboard', compact('screenshots', 'employees'));
    }

    public function deleteOldScreenshots()
    {
        $twoDaysAgo = now()->subDays(2);

        $oldScreenshots = Screenshot::where('created_at', '<', $twoDaysAgo)->get();

        $deletedCount = 0;

        foreach ($oldScreenshots as $screenshot) {
            // Delete file from storage
            if (Storage::exists('public/' . $screenshot->image_path)) {
                Storage::delete('public/' . $screenshot->image_path);
                $deletedCount++;
            }
            // Delete database record
            $screenshot->delete();
        }

        return back()->with('success', "Deleted {$deletedCount} old screenshots.");
    }
}
