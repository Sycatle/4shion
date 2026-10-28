<?php

declare(strict_types=1);

namespace App\Livewire\Shop;

use App\Actions\Checkout\StartCheckout;
use App\Cart\CartManager;
use App\Data\ShippingAddress;
use App\Exceptions\Cart\CartException;
use App\Models\Address;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('components.layouts.shop')]
#[Title('Commande')]
final class CheckoutPage extends Component
{
    private const int MAX_ATTEMPTS_PER_MINUTE = 5;

    public string $email = '';

    public string $firstName = '';

    public string $lastName = '';

    public string $line1 = '';

    public ?string $line2 = null;

    public string $postalCode = '';

    public string $city = '';

    public ?string $phone = null;

    public bool $saveAddress = true;

    public bool $acceptTerms = false;

    public ?int $savedAddressId = null;

    public ?string $error = null;

    public ?int $shippingMethodId = null;

    public function mount(CartManager $cart): void
    {
        if ($cart->count() === 0) {
            $this->redirectRoute('cart', navigate: true);

            return;
        }

        $this->shippingMethodId = ShippingMethod::default()?->id;
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $address = $user->addresses()->orderByDesc('is_default')->latest()->first();

        if ($address !== null) {
            $this->fillFrom($address);

            return;
        }

        [$this->firstName, $this->lastName] = array_pad(explode(' ', $user->name, 2), 2, '');
    }

    public function useSavedAddress(int $addressId): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->fillFrom($user->addresses()->findOrFail($addressId));
    }

    public function submit(StartCheckout $startCheckout): void
    {
        $this->error = null;
        $this->email = trim($this->email);

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('checkout-invalid');

            throw $e;
        }

        // Each attempt reserves stock and opens a payment session: cap them per customer.
        $throttleKey = 'checkout:'.(auth()->id() ?? session()->getId());

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS_PER_MINUTE)) {
            $this->error = sprintf('Trop de tentatives de paiement. Réessayez dans %d secondes.', RateLimiter::availableIn($throttleKey));

            return;
        }

        RateLimiter::hit($throttleKey);

        $user = auth()->user();

        if (! $user instanceof User) {
            $user = $this->registerGuest();

            if (! $user instanceof User) {
                return;
            }
        }

        $address = new ShippingAddress(
            firstName: $this->firstName,
            lastName: $this->lastName,
            line1: $this->line1,
            line2: $this->line2 ?: null,
            postalCode: $this->postalCode,
            city: $this->city,
            phone: $this->phone ?: null,
        );

        try {
            $url = $startCheckout($user, $address, $this->selectedShippingMethod());
        } catch (CartException $e) {
            $this->error = $e->getMessage();

            return;
        } catch (Throwable $e) {
            report($e);
            $this->error = 'Le paiement est momentanément indisponible. Veuillez réessayer dans quelques instants.';

            return;
        }

        if ($this->saveAddress) {
            $user->addresses()->updateOrCreate(
                ['line1' => $address->line1, 'postal_code' => $address->postalCode],
                [
                    'first_name' => $address->firstName,
                    'last_name' => $address->lastName,
                    'line2' => $address->line2,
                    'city' => $address->city,
                    'country' => $address->country,
                    'phone' => $address->phone,
                    'is_default' => ! $user->addresses()->exists(),
                ],
            );
        }

        $this->redirect($url);
    }

    /**
     * Guest checkout: the order needs an owner, so a light account is created and signed in.
     * The customer then gets a link to choose a password; the order stays reachable meanwhile.
     */
    private function registerGuest(): ?User
    {
        $email = Str::lower(trim($this->email));

        if (User::query()->where('email', $email)->exists()) {
            $this->error = 'Un compte existe déjà avec cet e-mail. Connectez-vous pour continuer votre commande.';

            return null;
        }

        $user = User::create([
            'name' => trim("{$this->firstName} {$this->lastName}"),
            'email' => $email,
            'password' => Str::random(40),
        ]);

        Auth::login($user);
        session()->regenerate();
        Password::sendResetLink(['email' => $email]);

        return $user;
    }

    /** @return array<string, list<string>> */
    protected function rules(): array
    {
        return [
            'email' => [auth()->check() ? 'nullable' : 'required', 'email:rfc', 'max:255'],
            'firstName' => ['required', 'string', 'max:100'],
            'lastName' => ['required', 'string', 'max:100'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'postalCode' => ['required', 'regex:/^\d{5}$/'],
            'city' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'acceptTerms' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'email' => 'e-mail',
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
        return [
            'postalCode.regex' => 'Le code postal doit comporter 5 chiffres.',
            'acceptTerms.accepted' => 'Vous devez accepter les conditions de vente pour continuer.',
        ];
    }

    /** The method the customer picked, if it is still offered. */
    private function selectedShippingMethod(): ?ShippingMethod
    {
        return $this->shippingMethodId === null ? null : ShippingMethod::query()->active()->find($this->shippingMethodId);
    }

    private function fillFrom(Address $address): void
    {
        $this->savedAddressId = $address->id;
        $this->firstName = $address->first_name;
        $this->lastName = $address->last_name;
        $this->line1 = $address->line1;
        $this->line2 = $address->line2;
        $this->postalCode = $address->postal_code;
        $this->city = $address->city;
        $this->phone = $address->phone;
    }

    public function render(CartManager $cart): View
    {
        $user = auth()->user();
        $items = $cart->items();

        return view('livewire.shop.checkout-page', [
            'items' => $items,
            'totals' => $cart->totalsFor($items, null, $this->selectedShippingMethod()),
            'shippingMethods' => ShippingMethod::query()->active()->get(),
            'addresses' => $user instanceof User ? $user->addresses()->get() : new Collection,
        ]);
    }
}
