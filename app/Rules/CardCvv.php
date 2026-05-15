<?php

namespace App\Rules;

use App\Support\CardBrands;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CardCvv implements ValidationRule
{
    public function __construct(private readonly ?string $cardNumberRaw)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $pan = preg_replace('/\D+/', '', (string)($this->cardNumberRaw ?? '')) ?? '';
        $brand = CardBrands::detect($pan);

        if (!$brand || !CardBrands::isSupported($brand)) {
            return;
        }

        $cvv = preg_replace('/\D+/', '', (string) $value) ?? '';
        if ($cvv === '' || !ctype_digit($cvv)) {
            $fail('CVV must be digits only.');
            return;
        }

        $expected = CardBrands::expectedCvvLength($brand);
        if ($expected !== null && strlen($cvv) !== $expected) {
            $fail("CVV must be {$expected} digits for {$brand}.");
        }
    }
}