<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

#[Fillable(['user_id', 'parent_id', 'title', 'content', 'tags', 'position'])]
class Note extends Model
{
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'title' => 'encrypted',
            'content' => 'encrypted',
            'tags' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Note::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Note::class, 'parent_id')->orderBy('position');
    }
}
