<?php

namespace App\Rules;

use App\Support\CardBrands;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CardNumberBrand implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $pan = preg_replace('/\D+/', '', (string) $value) ?? '';
        $brand = CardBrands::detect($pan);

        if (!$brand) {
            $fail('Card number issuer could not be detected.');
            return;
        }

        if (!CardBrands::isSupported($brand)) {
            $fail('Sorry — we currently accept Visa, MasterCard, or American Express only.');
            return;
        }

        $expectedLen = CardBrands::expectedLength($brand);
        if ($expectedLen !== null && strlen($pan) !== $expectedLen) {
            $fail(ucfirst($brand) . " card numbers must be {$expectedLen} digits.");
        }
    }
}
