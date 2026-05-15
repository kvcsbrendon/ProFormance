<?php

namespace App\Support;

final class CardBrands
{
    public const VISA = 'visa';
    public const MASTERCARD = 'mastercard';
    public const AMEX = 'amex';

    public const DISCOVER = 'discover';
    public const JCB = 'jcb';
    public const DINERS = 'diners';

    public static function detect(string $pan): ?string
    {
        $pan = preg_replace('/\D+/', '', $pan) ?? '';
        if ($pan === '') return null;

        // AmEx: 34 or 37 (15)
        if (preg_match('/^(34|37)/', $pan)) return self::AMEX;

        // Visa: starts with 4 (typically 16 here)
        if (preg_match('/^4/', $pan)) return self::VISA;

        // MasterCard: 51–55 or 2221–2720
        if (preg_match('/^(5[1-5])/', $pan)) return self::MASTERCARD;
        if (strlen($pan) >= 4) {
            $first4 = (int) substr($pan, 0, 4);
            if ($first4 >= 2221 && $first4 <= 2720) return self::MASTERCARD;
        }

        // Unsupported (but detect so we can show message)
        if (preg_match('/^(6011|65|64[4-9])/', $pan)) return self::DISCOVER;
        if (preg_match('/^35/', $pan)) return self::JCB;
        if (preg_match('/^(300|301|302|303|304|305|36|38)/', $pan)) return self::DINERS;

        return null;
    }

    public static function isSupported(?string $brand): bool
    {
        return in_array($brand, [self::VISA, self::MASTERCARD, self::AMEX], true);
    }

    public static function expectedLength(?string $brand): ?int
    {
        return match ($brand) {
            self::AMEX => 15,
            self::VISA, self::MASTERCARD => 16,
            default => null,
        };
    }

    public static function expectedCvvLength(?string $brand): ?int
    {
        return match ($brand) {
            self::AMEX => 4,
            self::VISA, self::MASTERCARD => 3,
            default => null,
        };
    }
}
