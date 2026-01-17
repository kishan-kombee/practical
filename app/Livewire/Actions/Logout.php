<?php

namespace App\Livewire\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke()
    {
        Auth::guard('web')->logout();
        // Session::invalidate();
        // Session::regenerateToken();
        session()->invalidate();
        session_start();
        session_unset();
        Session::flush();

        return redirect('/');
    }
}
