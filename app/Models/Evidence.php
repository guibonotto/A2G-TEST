<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'execution_id',
    'file_name',
    'file_path',
    'mime_type',
    'size',
    'uploaded_at',
])]
class Evidence extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'evidences';

    /**
     * Indicates if the model should have timestamps.
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'size' => 'integer',
        ];
    }

    /**
     * Delete the stored file whenever the record is deleted, so the disk
     * never keeps files that no longer belong to any execution.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $evidence): void {
            Storage::disk('local')->delete($evidence->file_path);
        });
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(Execution::class);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
