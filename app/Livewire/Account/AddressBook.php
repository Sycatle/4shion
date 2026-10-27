<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.shop')]
#[Title('Mes adresses')]
final class AddressBook extends Component
{
    public ?int $editingId = null;

    public bool $showForm = false;

    public string $firstName = '';

    public string $lastName = '';

    public string $line1 = '';

    public ?string $line2 = null;

    public string $postalCode = '';

    public string $city = '';

    public ?string $phone = null;

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $addressId): void
    {
        $address = $this->user()->addresses()->findOrFail($addressId);

        $this->editingId = $address->id;
        $this->firstName = $address->first_name;
        $this->lastName = $address->last_name;
        $this->line1 = $address->line1;
        $this->line2 = $address->line2;
        $this->postalCode = $address->postal_code;
        $this->city = $address->city;
        $this->phone = $address->phone;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();
        $user = $this->user();

        $attributes = [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'line1' => $this->line1,
            'line2' => $this->line2 ?: null,
            'postal_code' => $this->postalCode,
            'city' => $this->city,
            'phone' => $this->phone ?: null,
        ];

        if ($this->editingId !== null) {
            $user->addresses()->findOrFail($this->editingId)->update($attributes);
        } else {
            $user->addresses()->create($attributes + ['is_default' => ! $user->addresses()->exists()]);
        }

        $this->resetForm();
    }

    public function makeDefault(int $addressId): void
    {
        $user = $this->user();
        $address = $user->addresses()->findOrFail($addressId);

        $user->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);
    }

    public function delete(int $addressId): void
    {
        $user = $this->user();
        $address = $user->addresses()->findOrFail($addressId);
        $wasDefault = $address->is_default;

        $address->delete();

        if ($wasDefault) {
            $user->addresses()->oldest()->first()?->update(['is_default' => true]);
        }
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    /** @return array<string, list<string>> */
    protected function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:100'],
            'lastName' => ['required', 'string', 'max:100'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'postalCode' => ['required', 'regex:/^\d{5}$/'],
            'city' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'firstName' => 'prénom',
            'lastName' => 'nom',
            'line1' => 'adresse',
            'line2' => 'complément d\'adresse',
            'postalCode' => 'code postal',
            'city' => 'ville',
            'phone' => 'téléphone',
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return ['postalCode.regex' => 'Le code postal doit comporter 5 chiffres.'];
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'showForm', 'firstName', 'lastName', 'line1', 'line2', 'postalCode', 'city', 'phone');
        $this->resetValidation();
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }

    public function render(): View
    {
        return view('livewire.account.address-book', [
            'addresses' => $this->user()->addresses()->orderByDesc('is_default')->orderBy('id')->get(),
        ]);
    }
}
