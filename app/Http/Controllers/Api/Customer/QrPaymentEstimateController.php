<?php

namespace App\Http\Controllers\Api\Customer;

use App\Actions\EstimateQrPaymentAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrPaymentEstimateController extends Controller
{
    public function __invoke(Request $request, string $reference, EstimateQrPaymentAction $action): JsonResponse
    {
        $data = $request->validate(['account_code' => ['required', 'string']]);

        return response()->json($action->handle($reference, $request->user(), $data['account_code']));
    }
}
