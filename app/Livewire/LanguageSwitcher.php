<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    public string $currentLang;

    public array $languages;

    public ?User $user = null;

    public function mount(?User $user)
    {
        $this->languages = config('constants.languages');
        $this->currentLang = app()->getLocale();
        $this->user = $user;
    }

    public function switchLang(string $lang)
    {
        $supportedCodes = array_values($this->languages);

        if (! in_array($lang, $supportedCodes, true)) {
            return; // Ignore unsupported language codes
        }

        // Update component state immediately so UI reflects the change
        $this->currentLang = $lang;

        // Persist for authenticated users
        if ($this->user) {
            $this->user->locale = $lang;
            $this->user->save();
        }

        // Always update the session locale so middleware picks it up
        Session::put('locale', $lang);
        $this->redirect(request()->header('referer'), navigate: true);
    }

    public function render()
    {
        return view('livewire.language-switcher');
    }

    public function getCurrentLangLabelProperty(): string
    {
        $label = array_search($this->currentLang, $this->languages, true);

        return is_string($label) ? $label : strtoupper($this->currentLang);
    }
}
