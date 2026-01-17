<?php

namespace Tests\Browser\User;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Laravel Dusk Test Case for User Module
 */
class UserTestCase extends DuskTestCase
{
    /**
     * Test the complete User module.
     */
    public function test_user_module_complete_flow()
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

            // Step 2: Navigate to User
            $browser->waitFor('[data-label="' . __('messages.side_menu.user') . '"]', 10)
                ->click('[data-testid="side_menu_user"]')
                ->waitForLocation('/user', 20)
                ->pause(5000);
        });
    }
}
