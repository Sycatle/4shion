<x-mail::message>
# Nouvelle connexion

Bonjour {{ $user->name }}, votre compte {{ config('app.name') }} vient d’être utilisé depuis un nouvel appareil ou une nouvelle adresse :

- Adresse IP : **{{ $ip }}**
- Appareil : {{ \Illuminate\Support\Str::limit($userAgent, 120) ?: 'inconnu' }}
- Date : {{ now()->translatedFormat('j F Y à H:i') }}

Si c’est bien vous, rien à faire. Sinon, changez immédiatement votre mot de passe :

<x-mail::button :url="route('password.request')">
Réinitialiser mon mot de passe
</x-mail::button>

L’équipe {{ config('app.name') }}
</x-mail::message>
