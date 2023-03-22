@component('mail::message')
# Avertissemen de photo GMB
Nom d'établissement : {{$fiche}}
Avertir par : {{$user}}
{{ $message }}

@component('mail::button', ['url' => $photo])
Voir l'image
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
