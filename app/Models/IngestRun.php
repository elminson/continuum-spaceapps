<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One pull of one NASA FIRMS feed. */
class IngestRun extends Model
{
    public const DONE = 'done';

    public const FAILED = 'failed';

    public const RUNNING = 'running';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'finished_at' => 'datetime', 'from' => 'date', 'to' => 'date'];
    }

    public static function start(string $feed): self
    {
        return self::query()->create(['feed' => $feed, 'status' => self::RUNNING, 'started_at' => now()]);
    }

    /** @param array{read: int, kept: int, inserted: int, from: ?string, to: ?string} $result */
    public function done(array $result): self
    {
        $this->fill([...$result, 'status' => self::DONE, 'finished_at' => now()])->save();

        return $this;
    }

    public function failed(string $error): self
    {
        $this->fill(['status' => self::FAILED, 'finished_at' => now(), 'error' => str($error)->limit(190)->toString()])->save();

        return $this;
    }

    /** @return array<string, mixed> */
    public function row(): array
    {
        return [
            'id' => $this->id,
            'feed' => $this->feed,
            'status' => $this->status,
            'started_at' => $this->started_at->toIso8601String(),
            'seconds' => $this->finished_at ? $this->finished_at->diffInSeconds($this->started_at, true) : null,
            'read' => $this->read,
            'kept' => $this->kept,
            'inserted' => $this->inserted,
            'from' => $this->from?->toDateString(),
            'to' => $this->to?->toDateString(),
            'error' => $this->error,
        ];
    }
}
