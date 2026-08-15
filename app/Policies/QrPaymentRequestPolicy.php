<?php

namespace App\Policies;

use App\Models\Core\QrPaymentRequest;
use App\Models\User;

class QrPaymentRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('qr_payment_requests.view_any');
    }

    public function view(User $user, QrPaymentRequest $request): bool
    {
        return $user->can('qr_payment_requests.view');
    }

    public function cancel(User $user, QrPaymentRequest $request): bool
    {
        return $user->can('qr_payment_requests.cancel');
    }

    public function create(User $user): bool
    {
        return false; // uniquement genere par le marchand via l'API
    }

    public function update(User $user, QrPaymentRequest $request): bool
    {
        return false;
    }

    public function delete(User $user, QrPaymentRequest $request): bool
    {
        return false; // jamais de suppression, c'est un enregistrement financier
    }
}