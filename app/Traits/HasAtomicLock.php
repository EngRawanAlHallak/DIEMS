<?php
// app/Traits/HasAtomicLock.php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait HasAtomicLock
{
    /**
     * Execute a callback inside a Redis atomic lock.
     *
     * Prevents concurrent race conditions (e.g. duplicate logins,
     * double-processing webhooks, parallel ticket creation).
     *
     * @param  string   $key      Unique lock key
     * @param  callable $callback Business logic to protect
     * @param  int      $ttl      Lock TTL in seconds (default 10)
     * @return mixed
     */
    protected function withLock(string $key, callable $callback, int $ttl = 10): mixed
    {
        $lock = Cache::lock("lock:{$key}", $ttl);

        if (! $lock->get()) {
            abort(response()->json([
                'status'  => 'error',
                'message' => 'Another operation is in progress. Please wait and retry.',
            ], 429));
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }
}
