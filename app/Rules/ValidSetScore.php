<?php

namespace App\Rules;

use App\Support\SetScoreRule;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a single set given as a [a, b] array. Use on the "sets.*" element
 * so the whole pair is checked together (needed because the rule is about the
 * relationship between the two numbers, not each in isolation).
 *
 *   'rounds.*.sets.*' => ['array', 'size:2', new ValidSetScore],
 *   'sets.*'          => ['array', 'size:2', new ValidSetScore],
 *
 * A [0,0] pair is allowed here (it means "not entered" and is stripped later
 * by the service). Any other pair must be a valid final set.
 */
class ValidSetScore implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value) || count($value) !== 2) {
            $fail('Set con formato inválido.');
            return;
        }

        $a = (int) $value[0];
        $b = (int) $value[1];

        // Allow the empty placeholder; the service filters [0,0] out.
        if ($a === 0 && $b === 0) {
            return;
        }

        $msg = SetScoreRule::error($a, $b);
        if ($msg !== null) {
            $fail($msg);
        }
    }
}
