<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Hash;

/**
 * Code PIN du CLIENT, stocké dans customers.pin_hash (jamais en clair).
 *
 * Le PIN est « poivré » avec APP_KEY avant le hash : une fuite de la base seule ne permet pas
 * de retrouver un PIN à 4 chiffres par force brute. (Attention : si APP_KEY change, tous les PIN
 * deviennent invalides et doivent être redéfinis.)
 */
trait HasPin
{
    /** Appelé automatiquement par Eloquent à la construction du modèle. */
    public function initializeHasPin(): void
    {
        $this->makeHidden('pin_hash');                 // jamais renvoyé dans le JSON
        $this->mergeCasts(['pin_set_at' => 'datetime']);
    }

    public function hasPin(): bool
    {
        return $this->pin_hash !== null;
    }

    private function pinDigest(string $pin): string
    {
        return hash_hmac('sha256', $pin, (string) config('app.key'));
    }

    public function setPin(string $pin): void
    {
        $this->forceFill([
            'pin_hash' => Hash::make($this->pinDigest($pin)),
            'pin_set_at' => now(),
        ])->save();
    }

    public function checkPin(string $pin): bool
    {
        return $this->hasPin() && Hash::check($this->pinDigest($pin), $this->pin_hash);
    }

    public function clearPin(): void
    {
        $this->forceFill(['pin_hash' => null, 'pin_set_at' => null])->save();
    }
}
