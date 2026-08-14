<?php
// app/Actions/RegenerateCustomerActivationCodeAction.php
namespace App\Actions;

use App\Exceptions\TransactionRejectedException;
use App\Models\Core\Customer;
use App\Notifications\CustomerActivationCode;
use Illuminate\Support\Facades\Hash;

class RegenerateCustomerActivationCodeAction
{
    public function handle(Customer $customer): void
    {
        if ($customer->isActivated()) {
            throw new TransactionRejectedException('Ce compte est deja active.');
        }

        $code = (string) random_int(100000, 999999);

        $customer->update([
            'activation_code_hash' => Hash::make($code),
            'activation_expires_at' => now()->addDays(3),
        ]);

        $customer->notify(new CustomerActivationCode($code));
        
        if(env('APP_ENV') === 'local') {
            \Log::info("Temporary activation code : {$code} generated for customer: {$customer->id}  ");
        }
    }
}