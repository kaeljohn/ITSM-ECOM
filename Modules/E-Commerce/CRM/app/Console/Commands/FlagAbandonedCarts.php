<?php

namespace Modules\Ecommerce\CRM\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\CRM\Models\AbandonedCart;

class FlagAbandonedCarts extends Command
{
    protected $signature = 'crm:flag-abandoned-carts
        {--hours=2 : Hours of inactivity before considering a cart abandoned}';

    protected $description = 'Detect and flag abandoned carts for recovery';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $threshold = now()->subHours($hours);

        // Find carts that were created/updated before the threshold and not converted to orders
        $carts = DB::connection('ecommerce')->table('carts')
            ->where('updated_at', '<', $threshold)
            ->whereNotIn('id', function ($q) {
                $q->select('cart_id')->from('orders')->whereNotNull('cart_id');
            })
            ->get();

        $flagged = 0;
        foreach ($carts as $cart) {
            // Skip if already flagged
            if (AbandonedCart::where('cart_id', $cart->id)->exists()) {
                continue;
            }

            $user = $cart->user_id
                ? DB::connection('ecommerce')->table('users')->find($cart->user_id)
                : null;

            AbandonedCart::create([
                'client_id' => $cart->client_id ?? null,
                'user_id' => $cart->user_id,
                'cart_id' => $cart->id,
                'email' => $user->email ?? null,
                'cart_total' => $cart->total ?? 0,
                'status' => 'pending',
                'abandoned_at' => $cart->updated_at,
            ]);
            $flagged++;
        }

        $this->info("Flagged {$flagged} abandoned carts.");

        return Command::SUCCESS;
    }
}
