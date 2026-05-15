<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotSimilarToUserData implements ValidationRule
{
    public function __construct(
        private ?string $firstName,
        private ?string $lastName,
        private ?string $email
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $password = mb_strtolower((string) $value);

        $pieces = [];

        if ($this->firstName) $pieces[] = $this->firstName;
        if ($this->lastName)  $pieces[] = $this->lastName;

        if ($this->email && str_contains($this->email, '@')) {
            $local = explode('@', $this->email)[0];
            $pieces[] = $local;
        }

        // Normalize: lowercase + remove non-letters/digits to catch "john!!!"
        $normalize = fn($s) => preg_replace('/[^a-z0-9]+/i', '', mb_strtolower($s));

        $pNorm = $normalize($password);

        foreach ($pieces as $piece) {
            $pieceNorm = $normalize($piece);

            // Ignore tiny pieces like "a", "li"
            if (mb_strlen($pieceNorm) < 3) continue;

            // If password contains the user data directly
            if ($pieceNorm !== '' && str_contains($pNorm, $pieceNorm)) {
                $fail('Your password should not include your name or email.');
                return;
            }
        }
    }
}