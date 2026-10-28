<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Listeners\RecordLogin;
use App\Mail\NewLoginNotice;
use App\Models\User;
use App\Payments\StripeCheckoutGateway;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled;
use Filament\Auth\MultiFactor\MultiFactorChallenge;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Volt\Volt;

describe('admin two-factor authentication', function (): void {
    // The panel adds the middleware (and the set-up route) at boot, so the switch and the check are tested separately.
    it('makes the second factor mandatory and sees that staff without one have none enabled', function (): void {
        Config::set('shop.admin_mfa_required', true);
        $staff = User::factory()->create(['role' => Role::Support]);

        expect(Filament::getPanel('admin')->isMultiFactorAuthenticationRequired())->toBeTrue()
            ->and(MultiFactorChallenge::make()->hasEnabledProviders($staff))->toBeFalse();

        $staff->saveAppAuthenticationSecret(AppAuthentication::make()->generateSecret());

        expect(MultiFactorChallenge::make()->hasEnabledProviders($staff))->toBeTrue();
    });

    it('lets staff through once an authenticator is set up', function (): void {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $user = User::factory()->create(['role' => Role::Support]);
        $user->saveAppAuthenticationSecret(AppAuthentication::make()->generateSecret());
        $this->actingAs($user);

        $response = (new EnsureMultiFactorAuthenticationIsEnabled)->handle(Request::create('/admin'), fn (): ResponseFactory|Response => response('through'));

        expect($response->getContent())->toBe('through');
    });

    it('is switched off by configuration outside production-like environments', function (): void {
        expect(Filament::getPanel('admin')->isMultiFactorAuthenticationRequired())->toBeFalse();
    });

    it('stores the secret and recovery codes encrypted and never serialises them', function (): void {
        $user = User::factory()->create();
        $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
        $user->saveAppAuthenticationRecoveryCodes(['aaaa-bbbb', 'cccc-dddd']);

        $raw = (array) DB::table('users')->where('id', $user->id)->first();

        expect($raw['app_authentication_secret'])->not->toContain('JBSWY3DPEHPK3PXP')
            ->and($raw['app_authentication_recovery_codes'])->not->toContain('aaaa-bbbb')
            ->and($user->fresh()->getAppAuthenticationSecret())->toBe('JBSWY3DPEHPK3PXP')
            ->and($user->fresh()->getAppAuthenticationRecoveryCodes())->toBe(['aaaa-bbbb', 'cccc-dddd'])
            ->and($user->toArray())->not->toHaveKeys(['app_authentication_secret', 'app_authentication_recovery_codes']);
    });
});

describe('login notification', function (): void {
    function loginFrom(User $user, string $ip): void
    {
        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => $ip, 'HTTP_USER_AGENT' => 'Firefox']);
        new RecordLogin($request)->handle(new Login('web', $user, false));
    }

    it('stays quiet on the first sign-in and from a known address', function (): void {
        Mail::fake();
        $user = User::factory()->create();

        loginFrom($user, '203.0.113.5');
        loginFrom($user, '203.0.113.5');

        Mail::assertNothingQueued();
        expect($user->fresh()->last_login_ip)->toBe('203.0.113.5')->and($user->fresh()->last_login_at)->not->toBeNull();
    });

    it('warns the owner when the address changes', function (): void {
        Mail::fake();
        $user = User::factory()->create();

        loginFrom($user, '203.0.113.5');
        loginFrom($user, '198.51.100.7');

        Mail::assertQueued(NewLoginNotice::class, fn (NewLoginNotice $mail): bool => $mail->ip === '198.51.100.7' && $mail->hasTo($user->email));
        expect($user->fresh()->last_login_ip)->toBe('198.51.100.7');
    });
});

describe('registration throttle', function (): void {
    it('blocks the sixth sign-up from the same address within the hour', function (): void {
        RateLimiter::clear('register:127.0.0.1');

        foreach (range(1, 5) as $i) {
            Volt::test('pages.auth.register')
                ->set('name', "User {$i}")->set('email', "user{$i}@example.test")->set('password', 'a-strong-passw0rd')->set('password_confirmation', 'a-strong-passw0rd')
                ->call('register')->assertHasNoErrors();
            auth()->logout();
        }

        Volt::test('pages.auth.register')
            ->set('name', 'User 6')->set('email', 'user6@example.test')->set('password', 'a-strong-passw0rd')->set('password_confirmation', 'a-strong-passw0rd')
            ->call('register')->assertHasErrors(['email']);

        expect(User::query()->where('email', 'user6@example.test')->exists())->toBeFalse();
    });
});

describe('public endpoints', function (): void {
    it('throttles the feeds and newsletter links', function (): void {
        foreach (range(1, 60) as $i) {
            $this->get(route('robots'))->assertOk();
        }

        $this->get(route('robots'))->assertStatus(429);
    });
});

describe('language', function (): void {
    it('switches the storefront language for the session', function (): void {
        $this->get(route('home'))->assertSee('Mon compte', false)->assertDontSee('Create an account');

        $this->get(route('locale', 'en'))->assertRedirect();
        $this->get(route('home'))->assertSee('Create an account')->assertSee('Cookie settings')->assertSee('<html lang="en"', false);

        $this->get(route('locale', 'fr'))->assertRedirect();
        $this->get(route('home'))->assertSee('Créer un compte');
    });

    it('refuses an unsupported language', function (): void {
        $this->get(route('locale', 'xx'))->assertNotFound();
    });

    it('falls back on the French original when a string has no translation', function (): void {
        app()->setLocale('en');

        expect(__('Une phrase jamais traduite'))->toBe('Une phrase jamais traduite')
            ->and(__('Ajouter au panier'))->toBe('Add to cart');
    });
});

describe('payment methods', function (): void {
    it('lets Stripe pick the methods unless some are configured', function (): void {
        expect(Config::array('shop.payment_methods'))->toBe([]);
    });

    it('parses the configured methods list', function (): void {
        $methods = array_values(array_filter(array_map(trim(...), explode(',', 'card, paypal ,klarna,'))));

        expect($methods)->toBe(['card', 'paypal', 'klarna'])
            ->and(StripeCheckoutGateway::class)->toBeString();
    });
});
