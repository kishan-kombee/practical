<?php

namespace Tests\Browser\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    /**
     * Test login form validation - empty email.
     */
    public function test_login_form_validates_empty_email(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitFor('#login-button', 20)
                ->assertSee('Enter your email and password below to log in')
                ->typeSlowly('[data-testid="password"]', 'password123')
                ->press('#login-button')
                ->pause(1000)
                ->assertPresent('#email');
        });
    }

    /**
     * Test login form validation - empty password.
     */
    public function test_login_form_validates_empty_password(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitFor('#email', 20)
                ->typeSlowly('#email', 'test@example.com')
                ->press('#login-button')
                ->pause(1000)
                ->assertPresent('[data-testid="password"]');
        });
    }

    /**
     * Test login form validation - invalid email format.
     */
    public function test_login_form_validates_invalid_email_format(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitFor('#email', 20)
                ->typeSlowly('#email', 'invalid-email')
                ->typeSlowly('[data-testid="password"]', 'password123')
                ->press('#login-button')
                ->pause(1000)
                ->assertPresent('#email');
        });
    }

    /**
     * Test login fails with invalid credentials.
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/')
                ->waitFor('#email', 20)
                ->typeSlowly('#email', $user->email)
                ->typeSlowly('[data-testid="password"]', 'wrong-password')
                ->press('#login-button')
                ->pause(1500)
                ->assertSee(__('messages.login.invalid_credentials_error'));
        });
    }

    /**
     * Test successful login with valid credentials.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $plainPassword = 'test@12345';

        $user = User::factory()->create([
            'password' => Hash::make($plainPassword),
        ]);

        $this->browse(function (Browser $browser) use ($user, $plainPassword) {
            $browser->visit('/')
                ->waitFor('#email', 20)
                ->typeSlowly('#email', $user->email)
                ->typeSlowly('[data-testid="password"]', $plainPassword)
                ->press('#login-button')
                ->waitForLocation('/dashboard', 20)
                ->assertPathIs('/dashboard');
        });
    }
}
