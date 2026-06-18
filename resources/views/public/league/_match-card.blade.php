@php
$allCompleted = $m['status'] === 'completed';
@endphp
<article class="public-match" data-cancha-id="{{ $m['id'] }}">
    <div class="public-match-head">
        <span class="match-when">
            {{ $m['date_display'] ?? '?' }}
            @if ($m['time_slot']) · {{ $m['time_slot'] }} @endif
        </span>
        <span class="match-where">
            @if ($m['pista'])
            {{ $m['pista'] }}@if ($m['sede']) · {{ $m['sede'] }}@endif
            @endif
        </span>
    </div>

    <div class="cancha-players-list">
        @foreach ($m['players'] as $name)
        <span class="cancha-player">{{ $name }}</span>
        @endforeach
    </div>

    @foreach ($m['rounds'] as $round)
    @php
    $proposal = $round['pending_proposal'] ?? null;
    $hasResult = !empty($round['sets']);
    $aWinner = $hasResult && $round['winner'] === 'a';
    $bWinner = $hasResult && $round['winner'] === 'b';
    @endphp
    <div class="public-round">
        <div class="public-round-label">Set {{ $round['rotation_index'] }}</div>
        <div class="public-round-teams">
            <span class="{{ $aWinner ? 'is-winner' : '' }}">
                {{ implode(' / ', $round['team_a']) }}
            </span>
            @if ($hasResult)
            <span class="public-round-score">
                {{ $round['sets_a'] ?? 0 }}–{{ $round['sets_b'] ?? 0 }}
            </span>
            @else
            <span class="vs">vs</span>
            @endif
            <span class="text-end {{ $bWinner ? 'is-winner' : '' }}">
                {{ implode(' / ', $round['team_b']) }}
            </span>
        </div>
        @if ($hasResult && !empty($round['sets']))
        <div class="public-round-sets">
            @foreach ($round['sets'] as $set)
            <span class="set-tag">{{ $set[0] }}–{{ $set[1] }}</span>
            @endforeach
        </div>
        @endif
        @if ($proposal && !$hasResult)
        <div class="proposal-banner">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>
                <strong>{{ $proposal['proposer_name'] }}</strong> propuso
                @foreach ($proposal['sets'] as $set)
                <span class="proposal-set">{{ $set[0] }}–{{ $set[1] }}</span>@if (!$loop->last), @endif
                @endforeach
            </span>
        </div>
        @endif
        @if (!$showScore && !$hasResult)
        <button type="button" class="btn btn-sm btn-outline-primary propose-btn mt-1"
            data-round-id="{{ $round['id'] }}"
            data-cancha-id="{{ $m['id'] }}">
            <i class="fa-solid fa-pencil me-1"></i>
            Proponer marcador Set {{ $round['rotation_index'] }}
        </button>
        @endif
    </div>
    @endforeach
</article>