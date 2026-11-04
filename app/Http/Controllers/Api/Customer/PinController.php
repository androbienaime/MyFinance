<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Services\CustomerPinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Code PIN du client : l'application mobile s'en sert pour verrouiller/déverrouiller l'app
 * et pour confirmer les paiements QR.
 */
class PinController extends Controller
{
    public function __construct(private CustomerPinService $pins)
    {
    }

    /** GET /customer/pin : un PIN est-il défini ? */
    public function show(Request $request)
    {
        $customer = $request->user('customer');

        return response()->json([
            'has_pin' => $customer->hasPin(),
            'set_at' => $customer->pin_set_at,
        ]);
    }

    /** POST /customer/pin : création (aucun PIN défini). Corps : pin, pin_confirmation. */
    public function store(Request $request)
    {
        $customer = $request->user('customer');

        if ($customer->hasPin()) {
            return response()->json([
                'message' => 'Un code PIN est déjà défini. Utilisez la modification.',
                'has_pin' => true,
            ], 409);
        }

        $customer->setPin($this->validatedNewPin($request));

        return response()->json(['message' => 'Code PIN défini.', 'has_pin' => true], 201);
    }

    /** PUT /customer/pin : modification. Corps : current_pin, pin, pin_confirmation. */
    public function update(Request $request)
    {
        $customer = $request->user('customer');

        $request->validate(['current_pin' => ['required', 'digits:6']]);
        $this->pins->assertValid($customer, $request->input('current_pin'));

        $customer->setPin($this->validatedNewPin($request));

        return response()->json(['message' => 'Code PIN modifié.', 'has_pin' => true]);
    }

    /** POST /customer/pin/reset : PIN oublié, confirmé par le mot de passe. Corps : password, pin, pin_confirmation. */
    public function reset(Request $request)
    {
        $customer = $request->user('customer');

        $request->validate(['password' => ['required', 'string']]);

        $key = 'customer-pin-reset:'.$customer->getKey();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'Trop de tentatives. Réessayez dans quelques minutes.',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        if (! Hash::check($request->input('password'), $customer->password)) {
            RateLimiter::hit($key, 900);

            return response()->json(['message' => 'Mot de passe incorrect.'], 422);
        }
        RateLimiter::clear($key);

        $customer->setPin($this->validatedNewPin($request));
        
        $this->pins->resetAttempts($customer);

        return response()->json(['message' => 'Code PIN réinitialisé.', 'has_pin' => true]);
    }

    /** DELETE /customer/pin : désactivation. Corps : current_pin. */
    public function destroy(Request $request)
    {
        $customer = $request->user('customer');

        $request->validate(['current_pin' => ['required', 'digits:4,6']]);
        $this->pins->assertValid($customer, $request->input('current_pin'));

        $customer->clearPin();

        return response()->json(['message' => 'Code PIN désactivé.', 'has_pin' => false]);
    }

    /** POST /customer/pin/verify : déverrouillage de l'application. Corps : pin. */
    public function verify(Request $request)
    {
        $request->validate(['pin' => ['required', 'digits:6']]);
        $this->pins->assertValid($request->user('customer'), $request->input('pin'));

        return response()->json(['valid' => true]);
    }

    private function validatedNewPin(Request $request): string
    {
        $data = $request->validate(['pin' => ['required', 'digits:6', 'confirmed']]); // + pin_confirmation

        if ($this->pins->isWeak($data['pin'])) {
            throw ValidationException::withMessages([
                'pin' => ['Code trop simple (0000, 1234…). Choisissez-en un autre.'],
            ]);
        }

        return $data['pin'];
    }
}
