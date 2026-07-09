<?php
// app/Observers/EmployeeObserver.php

namespace App\Observers;

use App\Models\Core\Employee;

class EmployeeObserver
{
    public function created(Employee $employee): void
    {
        if ($employee->branch_id) {
            $employee->BranchHistories()->create([
                'branch_id' => $employee->branch_id,
                'started_at' => $employee->created_at ?? now(),
                'ended_at' => null,
                'reason' => 'embauche',
            ]);
        }
    }
}