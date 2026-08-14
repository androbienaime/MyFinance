<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Actions\GenerateMerchantApiKeyAction;
use App\Actions\RevokeMerchantApiKeyAction;
use App\Exceptions\TransactionRejectedException;
use App\Http\Controllers\Controller;
use App\Models\Core\MerchantApiKey;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function index(Request $request)
    {
        $merchant = $request->user('merchant');

        return response()->json(
            $merchant->apiKeys()
                ->latest()
                ->get()
                ->map(fn (MerchantApiKey $key) => [
                    'id' => $key->id,
                    'name' => $key->name,
                    'key_prefix' => $key->key_prefix,
                    'is_active' => $key->is_active,
                    'last_used_at' => $key->last_used_at,
                    'created_at' => $key->created_at,
                ])
        );
    }

    public function store(Request $request, GenerateMerchantApiKeyAction $action)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);

        $merchant = $request->user('merchant');

        try {
            $result = $action->handle($merchant, $data['name']);

            return response()->json([
                'message' => 'Cle generee. Copiez-la maintenant, elle ne sera plus jamais affichee.',
                'api_key' => $result['plain_text_key'],
                'key_prefix' => $result['key']->key_prefix,
            ], 201);
        } catch (TransactionRejectedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Request $request, MerchantApiKey $apiKey, RevokeMerchantApiKeyAction $action)
    {
        $merchant = $request->user('merchant');

        try {
            $action->handle($merchant, $apiKey);

            return response()->json(['message' => 'Cle revoquee.']);
        } catch (TransactionRejectedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
}