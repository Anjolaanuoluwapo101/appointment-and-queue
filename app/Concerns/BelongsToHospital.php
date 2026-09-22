<?php

namespace App\Concerns;

use App\Models\Hospital;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Scopes tenant models to the current hospital.
 *
 * The current hospital id is bound in the container as `currentHospitalId`
 * (by ResolveHospital middleware). When unbound — console, seeders, tests
 * without context — no constraint is applied; use forHospital() explicitly.
 */
trait BelongsToHospital
{
    public static function bootBelongsToHospital(): void
    {
        static::addGlobalScope('hospital', function (Builder $builder): void {
            if (app()->bound('currentHospitalId')) {
                $hospitalId = app('currentHospitalId');
                if ($hospitalId !== null) {
                    $builder->where($builder->getModel()->getTable().'.hospital_id', $hospitalId);
                }
            }
        });
    }

    /** @return BelongsTo<Hospital, $this> */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    /** @param Builder<static> $query */
    public function scopeForHospital(Builder $query, int $hospitalId): void
    {
        $query->withoutGlobalScope('hospital')->where('hospital_id', $hospitalId);
    }
}
