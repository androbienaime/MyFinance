<?php

namespace App\Services;

use App\Models\Core\Customer;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Vérification du PIN client avec limitation des essais.
 * Utilisé par PinController (verrouillage de l'app, modification…) ET par le paiement QR.
 */
class CustomerPinService
{
    public const MAX_ATTEMPTS = 5;
    public const DECAY_SECONDS = 900; // les échecs sont oubliés après 15 minutes

    private function key(Customer $customer): string
    {
        return 'customer-pin:'.$customer->getKey();
    }

    /** Codes évidents : 0000, 1111, 1234, 4321… */
    public function isWeak(string $pin): bool
    {
        if (preg_match('/^(\d)\1+$/', $pin)) {
            return true;
        }
        $d = array_map('intval', str_split($pin));
        $asc = $desc = true;
        for ($i = 1; $i < count($d); $i++) {
            if ($d[$i] !== $d[$i - 1] + 1) {
                $asc = false;
            }
            if ($d[$i] !== $d[$i - 1] - 1) {
                $desc = false;
            }
        }

        return $asc || $desc;
    }

    /**
     * Vérifie le PIN, sinon interrompt la requête avec une réponse JSON :
     *  - 409 : aucun PIN défini
     *  - 429 : trop d'essais (bloqué)
     *  - 422 : PIN incorrect, avec `attempts_left` ; au 5e échec la session courante est fermée.
     */
    public function assertValid(Customer $customer, string $pin): void
    {
        if (! $customer->hasPin()) {
            throw new HttpResponseException(response()->json([
                'message' => 'Aucun code PIN défini.',
                'has_pin' => false,
            ], 409));
        }

        $key = $this->key($customer);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw new HttpResponseException(response()->json([
                'message' => 'Trop de tentatives. Reconnectez-vous avec votre mot de passe.',
                'attempts_left' => 0,
                'retry_after' => RateLimiter::availableIn($key),
            ], 429));
        }

        if (! $customer->checkPin($pin)) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
            $left = max(0, self::MAX_ATTEMPTS - RateLimiter::attempts($key));

            if ($left === 0) {
                $customer->currentAccessToken()?->delete(); // Sanctum : la session courante est fermée
            }

            throw new HttpResponseException(response()->json([
                'message' => 'Code PIN incorrect.',
                'attempts_left' => $left,
            ], 422));
        }

        RateLimiter::clear($key);
    }

    /** À appeler après une connexion par mot de passe réussie, et après une réinitialisation du PIN. */
    public function resetAttempts(Customer $customer): void
    {
        RateLimiter::clear($this->key($customer));
    }
}
