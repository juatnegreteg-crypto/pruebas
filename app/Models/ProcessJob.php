<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Generic process tracker for async operations (imports, exports, etc.).
 *
 * Status flow: pending → processing → completed | failed
 *
 * @property int $id
 * @property string $uuid
 * @property string $type
 * @property string $status
 * @property string|null $file_path
 * @property int $total_chunks
 * @property int $processed_chunks
 * @property array|null $result
 * @property string|null $error
 */
class ProcessJob extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $table = 'process_jobs';

    protected $fillable = [
        'uuid',
        'type',
        'status',
        'file_path',
        'total_chunks',
        'processed_chunks',
        'result',
        'error',
    ];

    protected $casts = [
        'result' => 'array',
        'total_chunks' => 'integer',
        'processed_chunks' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProcessJob $job) {
            $job->uuid ??= (string) Str::uuid();
        });
    }

    public function markProcessing(int $totalChunks = 0): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'total_chunks' => $totalChunks,
            'processed_chunks' => 0,
        ]);
    }

    public function incrementProgress(): void
    {
        $this->increment('processed_chunks');
    }

    public function markCompleted(array $result): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'result' => $result,
            'processed_chunks' => $this->total_chunks,
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error' => $error,
        ]);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }
}
