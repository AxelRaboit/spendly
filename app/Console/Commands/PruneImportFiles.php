<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('spendly:prune-import-files {--hours=24 : Delete files older than this many hours}')]
#[Description('Delete orphaned temporary import files from storage/app/xlsx-imports')]
class PruneImportFiles extends Command
{
    public function handle(): void
    {
        $hours = (int) $this->option('hours');
        $threshold = now()->subHours($hours)->getTimestamp();
        $pruned = 0;

        foreach (Storage::files('xlsx-imports') as $path) {
            if (Storage::lastModified($path) < $threshold) {
                Storage::delete($path);
                $pruned++;
            }
        }

        $this->info(sprintf('Pruned %d orphaned import file(s) older than %dh.', $pruned, $hours));
    }
}
