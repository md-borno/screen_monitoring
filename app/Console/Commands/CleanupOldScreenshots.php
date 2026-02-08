<?php

namespace App\Console\Commands;

use App\Models\Screenshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOldScreenshots extends Command
{
    protected $signature = 'screenshots:cleanup';
    protected $description = 'Delete screenshots older than 2 days';

    public function handle()
    {
        $twoDaysAgo = now()->subDays(2);

        $oldScreenshots = Screenshot::where('created_at', '<', $twoDaysAgo)->get();

        $deletedCount = 0;

        foreach ($oldScreenshots as $screenshot) {
            // Delete file
            if (Storage::exists('public/' . $screenshot->image_path)) {
                Storage::delete('public/' . $screenshot->image_path);
            }
            // Delete database record
            $screenshot->delete();
            $deletedCount++;
        }

        $this->info("Deleted {$deletedCount} old screenshots.");

        return Command::SUCCESS;
    }
}
