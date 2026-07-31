<?php

namespace App\Actions\General;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class BaseAction
{
    protected function executeAction(
        callable $callback,
        string|array $message = '',
        array $properties = [],
        bool $shouldLog = false
    ) {
        try {
            return DB::transaction(function () use ($callback, $message, $properties, $shouldLog) {
                $result = $callback();

                if ($shouldLog && !empty($message)) {
                    $subject = $result instanceof Model ? $result : null;
                    $this->recordActivity($message, $subject, $properties, 'success');
                }

                return $result;
            });
        } catch (Exception $e) {
            // 🛑 حماية تسجيل الخطأ: تجنّب تسجيل الخطأ بداخل Transaction مكسورة في PostgreSQL
            try {
                $this->recordErrorActivity($e, $message);
            } catch (Exception $logException) {
                Log::error("تعذر كتابة Activity Log للخطأ: " . $logException->getMessage());
            }

            Log::error("خطأ في تنفيذ الأكشن [" . get_class($this) . "]: " . $e->getMessage());
            throw $e;
        }
    }


    protected function recordActivity(string|array $message, ?Model $subject, array $properties, string $status = 'success'): void
    {
        $description = is_array($message) ? $message : [
            'ar' => $message,
            'en' => $properties['en_message'] ?? $message
        ];

        $properties['status'] = $status;
        $properties['action_class'] = static::class;

        $activity = activity()
            ->useLog('actions')
            ->event($properties['event_type'] ?? $this->guessEventType())
            ->causedBy(auth()->user());

        if ($subject) {
            $activity->performedOn($subject);
        }

        $activity->withProperties($properties)
            ->log(is_array($description) ? json_encode($description) : $description);
    }

    /**
     * تسجيل الأخطاء والـ Exceptions تلقائياً للأدمن
     */
    protected function recordErrorActivity(Exception $e, string|array $message): void
    {
        activity()
            ->useLog('system_errors')
            ->event('failed')
            ->causedBy(auth()->user())
            ->withProperties([
                'status'        => 'error',
                'action_class'  => static::class,
                'error_message' => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'trace'         => collect($e->getTrace())->take(3)->toArray() // أول 3 أسطر فقط لتجنب الضخامة
            ])
            ->log(json_encode([
                'ar' => 'فشل في تنفيذ العملية: ' . (is_string($message) ? $message : ($message['ar'] ?? '')),
                'en' => 'Operation failed: ' . (is_string($message) ? $message : ($message['en'] ?? ''))
            ]));
    }

    private function guessEventType(): string
    {
        $className = strtolower(class_basename($this));

        return match (true) {
            str_contains($className, 'create') || str_contains($className, 'store')  => 'created',
            str_contains($className, 'update') || str_contains($className, 'edit')   => 'updated',
            str_contains($className, 'delete') || str_contains($className, 'destroy') => 'deleted',
            default => 'notified',
        };
    }
}
