<?php

namespace Tests\Browser\Product;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Laravel Dusk Test Case for Product Module
 */
class ProductTestCase extends DuskTestCase
{
    /**
     * Test the complete Product module.
     */
    public function test_product_module_complete_flow()
    {
        // Get existing admin user
        $user = User::where('email', '!=', null)
            ->whereNotNull('email')
            ->where('status', config('constants.user.status.key.active'))
            ->first();

        if (! $user) {
            $this->markTestSkipped('Admin user not found. Please seed the database.');
        }

        $this->browse(function (Browser $browser) use ($user) {
            // 1. Login
            $browser->visit('/')
                ->pause(5000)
                ->waitFor('#email', 20)
                ->typeSlowly('#email', $user->email)
                ->typeSlowly('[data-testid="password"]', '123456')
                ->press('#login-button')
                ->pause(3000)
                ->waitForLocation('/dashboard', 20);

            // Step 2: Navigate to Product
            $browser->waitFor('[data-label="' . __('messages.side_menu.product') . '"]', 10)
                ->click('[data-testid="side_menu_product"]')
                ->waitForLocation('/product', 20)
                ->pause(5000);
        });
    }
}
