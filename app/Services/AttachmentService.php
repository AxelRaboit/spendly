<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AllowedMime;
use App\Models\Transaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AttachmentService
{
    private const string DISK = 'local';

    private const string DIRECTORY = 'uploads/receipts';

    private const int MAX_SIZE_KB = 2048;

    public function store(UploadedFile $file, Transaction $transaction): string
    {
        $path = $file->store(
            sprintf('%s/%d/%s', self::DIRECTORY, $transaction->user_id, now()->format('Y/m')),
            self::DISK
        );

        $transaction->update(['attachment_path' => $path]);

        return $path;
    }

    public function delete(Transaction $transaction): void
    {
        if (! $transaction->attachment_path) {
            return;
        }

        Storage::disk(self::DISK)->delete($transaction->attachment_path);
        $transaction->update(['attachment_path' => null]);
    }

    public function path(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        return Storage::disk(self::DISK)->path($relativePath);
    }

    public function exists(?string $relativePath): bool
    {
        if (! $relativePath) {
            return false;
        }

        return Storage::disk(self::DISK)->exists($relativePath);
    }

    public static function validationRules(): array
    {
        return [
            'nullable',
            'file',
            sprintf('mimes:%s', AllowedMime::imageExtensions()),
            sprintf('max:%d', self::MAX_SIZE_KB),
        ];
    }
}
