@php
$aWinner = $showScore && $m['winner'] === 'a';
$bWinner = $showScore && $m['winner'] === 'b';
$canPropose = !$showScore && $m['status'] !== 'completed';
$hasProposal = !empty($m['pending_proposal']);
@endphp
<article class="public-match" data-match-id="{{ $m['id'] }}">
    <div class="public-match-head">
        <span class="match-when">
            {{ $m['date_display'] ?? '?' }}
            @if ($m['time_slot']) · {{ $m['time_slot'] }} @endif
            @if ($m['rotation_index'] > 1)
            · <span class="text-muted">R{{ $m['rotation_index'] }}</span>
            @endif
        </span>
        <span class="match-where">
            @if ($m['pista'])
            {{ $m['pista'] }}@if ($m['sede']) · {{ $m['sede'] }}@endif
            @endif
        </span>
    </div>

    <div class="public-match-teams">
        <div class="public-team {{ $aWinner ? 'is-winner' : '' }}">
            @foreach ($m['team_a'] as $name)
            <span class="player">{{ $name }}</span>
            @endforeach
        </div>

        @if ($showScore)
        <div class="public-match-score">
            <span>{{ $m['sets_a'] ?? 0 }}</span>
            <span class="vs">—</span>
            <span>{{ $m['sets_b'] ?? 0 }}</span>
        </div>
        @else
        <div class="public-match-score">
            <span class="vs">vs</span>
        </div>
        @endif

        <div class="public-team is-right {{ $bWinner ? 'is-winner' : '' }}">
            @foreach ($m['team_b'] as $name)
            <span class="player">{{ $name }}</span>
            @endforeach
        </div>
    </div>

    @if ($showScore && !empty($m['sets']))
    <div class="public-match-sets">
        @foreach ($m['sets'] as $set)
        <span class="set-tag">{{ $set[0] }}–{{ $set[1] }}</span>
        @endforeach
    </div>
    @endif

    {{-- Pending proposal banner --}}
    @if ($hasProposal && !$showScore)
    <div class="proposal-banner">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <span>
            <strong>{{ $m['pending_proposal']['proposer_name'] }}</strong> propuso
            @foreach ($m['pending_proposal']['sets'] as $set)
            <span class="proposal-set">{{ $set[0] }}–{{ $set[1] }}</span>@if (!$loop->last), @endif
            @endforeach
            · <span class="text-muted">{{ $m['pending_proposal']['created_at'] }}</span>
        </span>
    </div>
    @endif

    @if ($canPropose)
    <div class="public-match-actions">
        <button type="button" class="btn btn-sm btn-outline-primary propose-btn"
            data-match-id="{{ $m['id'] }}">
            <i class="fa-solid fa-pencil me-1"></i>
            {{ $hasProposal ? 'Proponer otro marcador' : 'Proponer marcador' }}
        </button>
    </div>
    @endif
</article>