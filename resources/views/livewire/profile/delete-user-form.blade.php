<?php

use App\Actions\Account\DeleteAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    public ?string $error = null;

    /**
     * Erase the currently authenticated user (orders are kept anonymised for accounting).
     */
    public function deleteUser(DeleteAccount $deleteAccount): void
    {
        $this->error = null;
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $user = Auth::user();

        try {
            $deleteAccount($user);
        } catch (LogicException $e) {
            $this->error = $e->getMessage();

            return;
        }

        // Not Logout: signing out re-saves the user (remember token), which would resurrect an erased row.
        Auth::guard('web')->forgetUser();
        Session::invalidate();
        Session::regenerateToken();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-6">
    <header>
        <h2 class="text-3xl">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-muted">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <p class="text-sm"><a href="{{ route('account.data.export') }}" class="underline underline-offset-4">Télécharger mes données (JSON)</a></p>

    @if ($error)
        <p class="border border-ink bg-white px-4 py-3 text-sm" role="alert">{{ $error }}</p>
    @endif

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Delete Account') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6">

            <h2 class="text-3xl">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="mt-1 text-sm text-muted">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                <x-text-input
                    wire:model="password"
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="{{ __('Password') }}"
                />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Delete Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
