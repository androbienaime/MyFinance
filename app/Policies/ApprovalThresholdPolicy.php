<?php

namespace App\Policies;

use App\Models\Core\ApprovalThreshold;
use App\Models\User;

class ApprovalThresholdPolicy
{
    /**
     * Comme BranchPolicy : pas de before() generique. Modifier les
     * seuils d'approbation change directement le niveau de risque
     * accepte sur les transactions — reserve strictement au siege.
     */
    public function viewAny(User $user): bool
    {
        return $user->isHeadOffice();
    }

    public function view(User $user, ApprovalThreshold $threshold): bool
    {
        return $user->isHeadOffice();
    }

    public function create(User $user): bool
    {
        return $user->isHeadOffice();
    }

    public function update(User $user, ApprovalThreshold $threshold): bool
    {
        return $user->isHeadOffice();
    }

    public function delete(User $user, ApprovalThreshold $threshold): bool
    {
        return $user->isHeadOffice();
    }
}