<?php

declare(strict_types=1);

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Payments\CheckoutGateway;
use App\Payments\CheckoutSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

arch()->preset()->php();
arch()->preset()->security();

arch('every file declares strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('actions are final, invokable and stateless services')
    ->expect('App\Actions')
    ->toBeFinal()
    ->toBeInvokable();

arch('concrete classes outside models are final')
    ->expect(['App\Listeners', 'App\Jobs', 'App\Http\Controllers', 'App\Livewire', 'App\Payments', 'App\Policies', 'App\Mail'])
    ->classes()
    ->toBeFinal()
    ->ignoring(Controller::class);

arch('models are Eloquent models and stay out of the HTTP layer')
    ->expect('App\Models')
    ->classes()
    ->toExtend(Model::class)
    ->not->toUse(Request::class);

arch('domain code does not depend on Livewire or Filament')
    ->expect(['App\Actions', 'App\Cart', 'App\Models', 'App\Payments'])
    ->not->toUse(['Livewire', 'Filament']);

arch('the payment provider is only reached through the gateway')
    ->expect('Stripe')
    ->toOnlyBeUsedIn(['App\Payments', User::class]);

arch('gateways implement the checkout contract')
    ->expect('App\Payments')
    ->classes()
    ->toImplement(CheckoutGateway::class)
    ->ignoring(CheckoutSession::class);

arch('configuration is read through config(), never env()')
    ->expect('env')
    ->not->toBeUsedIn('App');
