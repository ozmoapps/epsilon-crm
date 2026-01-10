<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public function log(Model $subject, string $action, array $meta = [], ?int $actorId = null): ActivityLog
    {
        return ActivityLog::create([
            'actor_id' => $actorId ?? auth()->id(),
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'action' => $action,
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }
}
