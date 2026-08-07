{{--
    Upgrade-request button for a league that has hit a limit.
    Pre-fills a WhatsApp message with the league name + ID so the admin knows
    exactly which league to bump when the manager reaches out.

    Usage:
        @include('partials.league-upgrade-button', ['league' => $league, 'resource' => 'jornadas'])

    $resource is optional and just tailors the message wording.
--}}
@php
    $waNumber = '522297450000'; // same number as the landing CTA
    $resourceLabel = match ($resource ?? null) {
        'players'  => 'jugadores',
        'jornadas' => 'jornadas',
        'groups'   => 'grupos',
        default    => 'capacidad',
    };
    $waText = rawurlencode(
        "Hola, quiero ampliar los límites de mi liga \"{$league->name}\" (ID {$league->id}). "
        . "Necesito más {$resourceLabel}."
    );
@endphp

<a href="https://wa.me/{{ $waNumber }}?text={{ $waText }}"
   target="_blank" rel="noopener"
   class="btn btn-sm btn-whatsapp">
    <i class="fa-brands fa-whatsapp me-1"></i>
    Solicitar ampliación
</a>
