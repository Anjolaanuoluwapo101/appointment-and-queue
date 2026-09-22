<?php

namespace App\Services;

use App\Models\ConsultationFee;
use App\Models\Department;

/**
 * Server-trusted fee lookup (PRD §7): per-practitioner override wins,
 * then explicit department base row, then departments.base_fee_kobo.
 * Client amounts are never trusted. Returns kobo.
 */
class FeeResolver
{
    public function resolve(int $hospitalId, int $departmentId, ?int $practitionerId = null): int
    {
        if ($practitionerId !== null) {
            $override = ConsultationFee::forHospital($hospitalId)
                ->where('department_id', $departmentId)
                ->where('practitioner_id', $practitionerId)
                ->value('amount_kobo');

            if ($override !== null) {
                return (int) $override;
            }
        }

        $base = ConsultationFee::forHospital($hospitalId)
            ->where('department_id', $departmentId)
            ->whereNull('practitioner_id')
            ->value('amount_kobo');

        if ($base !== null) {
            return (int) $base;
        }

        return (int) Department::forHospital($hospitalId)->where('id', $departmentId)->value('base_fee_kobo');
    }
}
