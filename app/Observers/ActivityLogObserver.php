<?php

namespace App\Observers;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ActivityLogObserver
{
    public function created(Model $model): void
    {
        app(ActivityLogger::class)->log($model, 'created');
    }

    public function updated(Model $model): void
    {
        $changes = Arr::except($model->getChanges(), ['updated_at']);

        if ($changes === []) {
            return;
        }

        app(ActivityLogger::class)->log($model, 'updated', [
            'fields' => array_keys($changes),
        ]);
    }
}
