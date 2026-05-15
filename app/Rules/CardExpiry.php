<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CardExpiry implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $raw = trim((string) $value);

        // Accept "MM/YY" only
        if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $raw, $m)) {
            $fail('Expiry must be in MM/YY format.');
            return;
        }

        $mm = (int) $m[1];
        $yy = (int) $m[2];

        // Convert YY to 20YY (assumes cards won’t have 19xx expiry)
        $year = 2000 + $yy;

        // Expire at end of month
        $exp = \Carbon\Carbon::create($year, $mm, 1)->endOfMonth()->endOfDay();
        if ($exp->lt(now())) {
            $fail('Card has expired.');
        }
    }
}
