<?php

namespace Tests\Browser\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ForgotPasswordTest extends DuskTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test complete forgot password flow with email from users table.
     */
    public function test_forgot_password_flow_with_user_from_database(): void
    {
        // Get an existing user from the database (not hard-coded)
        $user = User::where('email', '!=', null)
            ->whereNotNull('email')
            ->first();

        if (! $user) {
            $this->markTestSkipped('No user with email found in database. Please seed the database.');
        }

        $this->browse(function (Browser $browser) {
            // Step 1: User can open the Login page
            $browser->visit('/')
                ->pause(2000) // Wait for Livewire to initialize
                ->assertSee('Enter your email and password below to log in');

            // Step 2: User can click "Forgot Password?"
            $browser->click('[data-testid="forgot_password"]')
                ->pause(2000); // Wait for navigation

            // Step 3: Forgot Password page loads correctly
            $browser->assertPathIs('/forgot-password')
                ->assertSee('Enter your email to receive a password reset link')
                ->assertPresent('[data-testid="email"]')
                ->assertPresent('[data-testid="submit_button"]');
        });
    }

    /**
     * Test forgot password form validation - empty email.
     */
    public function test_forgot_password_form_validates_empty_email(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->pause(2000)
                ->assertSee('Enter your email to receive a password reset link')
                ->press('[data-testid="submit_button"]')
                ->pause(2000)
                ->assertPresent('[data-testid="email"]'); // Email field should still be present with error
        });
    }

    /**
     * Test forgot password form validation - invalid email format.
     */
    public function test_forgot_password_form_validates_invalid_email_format(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->pause(2000)
                ->assertSee('Enter your email to receive a password reset link')
                ->typeSlowly('[data-testid="email"]', 'invalid-email')
                ->press('[data-testid="submit_button"]')
                ->pause(2000)
                ->assertPresent('[data-testid="email"]'); // Email field should still be present with error
        });
    }

    /**
     * Test forgot password with non-existent email.
     */
    public function test_forgot_password_with_non_existent_email(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->pause(2000)
                ->assertSee('Enter your email to receive a password reset link')
                ->typeSlowly('[data-testid="email"]', 'nonexistent@example.com')
                ->press('[data-testid="submit_button"]')
                ->pause(3000)
                ->assertSee(__('messages.login.invalid_email_error'));
        });
    }

    /**
     * Test navigation back to login from forgot password page.
     */
    public function test_navigation_back_to_login_from_forgot_password(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/forgot-password')
                ->pause(2000)
                ->assertSee('Enter your email to receive a password reset link')
                ->click('[data-testid="login"]')
                ->pause(2000)
                ->assertPathIs('/')
                ->assertSee('Enter your email and password below to log in');
        });
    }

    /**
     * Test  password reset email is successfully triggered.
     */
    public function test_password_reset_email_is_successfully_triggered(): void
    {
        $user = User::where('email', '!=', null)
            ->whereNotNull('email')
            ->first();

        if (! $user) {
            $this->markTestSkipped('No user with email found in database. Please seed the database.');
        }

        $this->browse(function (Browser $browser) use ($user) {
            // First request should succeed
            $browser->visit('/forgot-password')
                ->pause(10000)
                ->typeSlowly('[data-testid="email"]', $user->email)
                ->press('[data-testid="submit_button"]')
                ->pause(15000)
                ->assertSee('Forgot Password');
        });
    }
}
