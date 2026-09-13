<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Fillable([
    'test_case_id',
    'executed_by',
    'status',
    'comment',
    'batch_id',
    'execution_date',
])]
class Execution extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'executions';

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'execution_date' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function testCase(): BelongsTo
    {
        return $this->belongsTo(TestCase::class);
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }

    /**
     * Store the uploaded files on disk and register each one as an evidence
     * of this execution. If any of them fails, the files already written are
     * removed so the disk never keeps evidences that were not persisted.
     *
     * @param  array<int, UploadedFile>  $files
     */
    public function attachEvidences(array $files): void
    {
        $storedPaths = [];

        try {
            foreach ($files as $file) {
                $path = $file->store("evidences/{$this->id}", 'local');
                $storedPaths[] = $path;

                $this->evidences()->create([
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_at' => now(),
                ]);
            }
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);

            throw $exception;
        }
    }

    /**
     * Forces exclusion to pass through Eloquent (therefore through the Evidence hook)
     */
    protected static function booted(): void
    {
        static::deleting(fn (self $execution) => $execution->evidences->each->delete());
    }
}
