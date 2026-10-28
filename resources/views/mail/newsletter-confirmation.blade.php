<x-mail::message>
# Un dernier clic

Vous avez demandé à recevoir la newsletter {{ config('app.name') }}. Confirmez votre adresse pour finaliser l’inscription :

<x-mail::button :url="route('newsletter.confirm', $subscriber->token)">
Confirmer mon inscription
</x-mail::button>

Si vous n’êtes pas à l’origine de cette demande, ignorez simplement cet e-mail : vous ne recevrez rien.

L’équipe {{ config('app.name') }}
</x-mail::message>
