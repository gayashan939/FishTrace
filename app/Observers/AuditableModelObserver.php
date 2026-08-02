<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditableModelObserver
{
    public function __construct(private AuditLogger $audit) {}

    public function created(Model $model): void
    {
        if ($this->hasAuthenticatedActor()) {
            $this->audit->record(Str::snake(class_basename($model)).'_CREATED', $model, null, $model->getAttributes());
        }
    }

    public function updated(Model $model): void
    {
        if (! $this->hasAuthenticatedActor()) {
            return;
        }
        $changes = $model->getChanges();
        unset($changes['updated_at']);
        if ($changes === []) {
            return;
        }
        $old = [];
        foreach (array_keys($changes) as $key) {
            $old[$key] = $model->getRawOriginal($key);
        }
        $this->audit->record(Str::snake(class_basename($model)).'_UPDATED', $model, $old, $changes);
    }

    public function deleted(Model $model): void
    {
        if ($this->hasAuthenticatedActor()) {
            $this->audit->record(Str::snake(class_basename($model)).'_DELETED', $model, $model->getAttributes(), null);
        }
    }

    private function hasAuthenticatedActor(): bool
    {
        return request()->user() instanceof User;
    }
}
