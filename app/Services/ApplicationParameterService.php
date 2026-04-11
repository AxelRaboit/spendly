<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ApplicationParameter;

class ApplicationParameterService
{
    public function get(string $key, ?string $default = null): ?string
    {
        return ApplicationParameter::where('key', $key)->value('value') ?? $default;
    }

    public function set(string $key, ?string $value): void
    {
        ApplicationParameter::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key);

        return $value !== null ? (int) $value : $default;
    }

    public function getBool(string $key, bool $default = true): bool
    {
        $value = $this->get($key);

        return $value !== null ? $value === '1' : $default;
    }

    public function getFloat(string $key, float $default = 0.0): float
    {
        $value = $this->get($key);

        return $value !== null ? (float) $value : $default;
    }

    /**
     * @return array<int, array{key: string, value: string|null, description: string|null}>
     */
    public function all(): array
    {
        return ApplicationParameter::orderBy('key')
            ->get()
            ->map(fn (ApplicationParameter $parameter): array => [
                'key' => $parameter->key,
                'value' => $parameter->value,
                'description' => $parameter->description,
            ])
            ->values()
            ->toArray();
    }
}
