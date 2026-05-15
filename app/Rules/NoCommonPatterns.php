<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoCommonPatterns implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $pw = mb_strtolower((string) $value);

        // Strip spaces
        $pw = preg_replace('/\s+/', '', $pw);

        // Common keyboard patterns
        $badSubstrings = [
            'password', 'passw0rd', 'qwerty', 'asdf', 'zxcv',
            '1234', '12345', '123456', '1111', '0000',
            'admin', 'letmein'
        ];

        foreach ($badSubstrings as $bad) {
            if (str_contains($pw, $bad)) {
                $fail('Your password is too common or predictable.');
                return;
            }
        }

        // Repeated character 4+ times: aaaa, 1111, !!!!
        if (preg_match('/(.)\1{3,}/', $pw)) {
            $fail('Your password contains repeated characters and is too easy to guess.');
            return;
        }

        // Simple increasing sequences like 0123, 1234, abcd (length 4+)
        if ($this->hasSequentialRun($pw, 4)) {
            $fail('Your password contains a sequence (e.g. 1234) and is too easy to guess.');
            return;
        }
    }

    private function hasSequentialRun(string $s, int $minLen): bool
    {
        // check only alphanumerics
        $chars = str_split(preg_replace('/[^a-z0-9]/', '', $s));
        $n = count($chars);

        $run = 1;
        for ($i = 1; $i < $n; $i++) {
            $prev = ord($chars[$i-1]);
            $cur  = ord($chars[$i]);

            if ($cur === $prev + 1) {
                $run++;
                if ($run >= $minLen) return true;
            } else {
                $run = 1;
            }
        }
        return false;
    }
}