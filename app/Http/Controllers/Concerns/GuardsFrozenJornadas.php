<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Jornada;
use Illuminate\Http\Exceptions\HttpResponseException;

trait GuardsFrozenJornadas
{
    /**
     * Abort with a 422 JSON error if the jornada is frozen (not the latest in
     * its group). Call at the top of any endpoint that mutates a jornada.
     */
    protected function ensureJornadaEditable(Jornada $jornada): void
    {
        if ($jornada->isEditable()) {
            return;
        }

        throw new HttpResponseException(response()->json([
            'message' => "La Jornada {$jornada->number} está bloqueada porque ya existe una jornada posterior. "
                . "Para editarla, elimina primero las jornadas siguientes.",
            'code'    => 'JORNADA_FROZEN',
        ], 422));
    }
}
