@php
$aWinner = $showScore && $m['winner'] === 'a';
$bWinner = $showScore && $m['winner'] === 'b';
@endphp
<article class="public-match">
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
</article>