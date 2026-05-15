<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Luhn implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $pan = preg_replace('/\D+/', '', (string) $value) ?? '';
        if ($pan === '' || !ctype_digit($pan)) {
            $fail('Card number must contain only digits.');
            return;
        }

        $sum = 0;
        $alt = false;

        for ($i = strlen($pan) - 1; $i >= 0; $i--) {
            $n = (int) $pan[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) $n -= 9;
            }
            $sum += $n;
            $alt = !$alt;
        }

        if ($sum % 10 !== 0) {
            $fail('Card number is not valid.');
        }
    }
}
