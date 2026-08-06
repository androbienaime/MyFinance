<?php

namespace App\Actions;

use App\Enums\MerchantStatus;
use App\Exceptions\TransactionRejectedException;
use App\Models\Core\MerchantProfile;
use App\Models\User;

class ApproveMerchantProfileAction
{
    public function approve(MerchantProfile $profile, User $approver): MerchantProfile
    {
        if ($profile->status !== MerchantStatus::Pending) {
            throw new TransactionRejectedException('Ce profil marchand n\'est plus en attente de validation.');
        }

        $profile->update([
            'status' => MerchantStatus::Active,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        return $profile;
    }

    public function reject(MerchantProfile $profile, User $approver, string $reason): MerchantProfile
    {
        if ($profile->status !== MerchantStatus::Pending) {
            throw new TransactionRejectedException('Ce profil marchand n\'est plus en attente de validation.');
        }

        $profile->update([
            'status' => MerchantStatus::Rejected,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $profile;
    }

    public function suspend(MerchantProfile $profile, User $approver, string $reason): MerchantProfile
    {
        if ($profile->status !== MerchantStatus::Active) {
            throw new TransactionRejectedException('Seul un marchand actif peut etre suspendu.');
        }

        $profile->update([
            'status' => MerchantStatus::Suspended,
            'rejection_reason' => $reason,
        ]);

        $profile->tokens()->delete(); // coupe immediatement tout acces API en cours

        return $profile;
    }
}