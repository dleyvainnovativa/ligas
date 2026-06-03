<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\League;
use App\Services\MatchProposalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PublicMatchProposalController extends Controller
{
    public function __construct(private MatchProposalService $proposals) {}

    public function store(Request $request, string $slug, GameMatch $match)
    {
        $league = League::where('slug', $slug)
            ->whereIn('status', [League::STATUS_ACTIVE, League::STATUS_COMPLETED])
            ->firstOrFail();

        // Ensure the match actually belongs to this public league
        $belongs = $match->cancha->jornada->group->league_id === $league->id;
        abort_unless($belongs, 404);

        // If the match is already completed by the manager, lock proposals
        if ($match->status === GameMatch::STATUS_COMPLETED) {
            abort(422, 'Este partido ya tiene un resultado oficial.');
        }

        // Rate limit: 5 proposals per hour per IP per league (soft anti-spam)
        $key = "propose:{$league->id}:" . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            abort(429, 'Demasiados intentos. Intenta de nuevo más tarde.');
        }
        RateLimiter::hit($key, 3600);

        $data = $request->validate([
            'name'   => ['required', 'string', 'min:2', 'max:120'],
            'sets'   => ['required', 'array', 'min:1', 'max:5'],
            'sets.*' => ['array', 'size:2'],
            'sets.*.0' => ['integer', 'min:0', 'max:99'],
            'sets.*.1' => ['integer', 'min:0', 'max:99'],
        ]);

        $proposal = $this->proposals->propose($match, $data['sets'], $data['name'], $request);

        // Bind a token cookie so we know which browser proposed
        $cookie = Cookie::make(
            name: 'pl_proposer',
            value: $proposal->proposer_token,
            minutes: 60 * 24 * 365,         // 1 year
            httpOnly: false,                 // readable by JS so we can show "tu propusiste"
            sameSite: 'lax',
        );

        // Invalidate public payload cache so the page reflects the new proposal
        \Illuminate\Support\Facades\Cache::forget("public_league:{$league->id}:v2");

        return response()
            ->json([
                'ok' => true,
                'proposal' => [
                    'id'             => $proposal->id,
                    'proposer_name'  => $proposal->proposer_name,
                    'sets'           => $proposal->sets,
                    'created_at'     => $proposal->created_at->toIso8601String(),
                ],
            ])
            ->cookie($cookie);
    }
}
