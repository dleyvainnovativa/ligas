{{--
    Sets up window.__publicLeagueSlug and window.__publicMatches for the propose flow.
    The host page passes $proposeRounds — a flat collection of round payloads
    with at least: id, team_a, team_b, date_display, time_slot.
--}}
<script>
    window.__publicLeagueSlug = @json($league->slug);
    window.__publicMatches    = @json(collect($proposeRounds ?? [])->keyBy('id'));
</script>