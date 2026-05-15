<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Rules\NoCommonPatterns;
use App\Rules\NotSimilarToUserData;

class PasswordRulesTest extends TestCase
{
    // ─────────────────────────────────────────
    // NO COMMON PATTERNS
    // ─────────────────────────────────────────

    /** @test */
    public function rejects_password_containing_password()
    {
        $rule = new NoCommonPatterns();
        $failed = false;
        $rule->validate('password', 'MyPassword123!', function () use (&$failed) { $failed = true; });
        $this->assertTrue($failed);
    }

    /** @test */
    public function rejects_qwerty()
    {
        $rule = new NoCommonPatterns();
        $failed = false;
        $rule->validate('password', 'Qwerty2024!', function () use (&$failed) { $failed = true; });
        $this->assertTrue($failed);
    }

    /** @test */
    public function rejects_sequential_1234()
    {
        $rule = new NoCommonPatterns();
        $failed = false;
        $rule->validate('password', 'test1234!', function () use (&$failed) { $failed = true; });
        $this->assertTrue($failed);
    }

    /** @test */
    public function rejects_repeated_chars()
    {
        $rule = new NoCommonPatterns();
        $failed = false;
        $rule->validate('password', 'aaaa5678!', function () use (&$failed) { $failed = true; });
        $this->assertTrue($failed);
    }

    /** @test */
    public function accepts_strong_password()
    {
        $rule = new NoCommonPatterns();
        $failed = false;
        $rule->validate('password', 'K7$mPx!nR9vL', function () use (&$failed) { $failed = true; });
        $this->assertFalse($failed);
    }

    /** @test */
    public function rejects_admin()
    {
        $rule = new NoCommonPatterns();
        $failed = false;
        $rule->validate('password', 'Admin!2024', function () use (&$failed) { $failed = true; });
        $this->assertTrue($failed);
    }

    // ─────────────────────────────────────────
    // NOT SIMILAR TO USER DATA
    // ─────────────────────────────────────────

    /** @test */
    public function rejects_password_containing_first_name()
    {
        $rule = new NotSimilarToUserData('John', 'Doe', 'john@example.com');
        $failed = false;
        $rule->validate('password', 'John2024!', function () use (&$failed) { $failed = true; });
        $this->assertTrue($failed);
    }

    /** @test */
    public function rejects_password_containing_email_local()
    {
        $rule = new NotSimilarToUserData('Jane', 'Smith', 'janesmith@example.com');
        $failed = false;
        $rule->validate('password', 'Janesmith99!', function () use (&$failed) { $failed = true; });
        $this->assertTrue($failed);
    }

    /** @test */
    public function accepts_unrelated_password()
    {
        $rule = new NotSimilarToUserData('John', 'Doe', 'john@example.com');
        $failed = false;
        $rule->validate('password', 'K7$mPx!nR9vL', function () use (&$failed) { $failed = true; });
        $this->assertFalse($failed);
    }

    /** @test */
    public function ignores_short_names()
    {
        $rule = new NotSimilarToUserData('Li', 'Wu', 'li@test.com');
        $failed = false;
        $rule->validate('password', 'Lilith2024!', function () use (&$failed) { $failed = true; });
        // "Li" is < 3 chars so should be ignored
        $this->assertFalse($failed);
    }

    /** @test */
    public function case_insensitive_check()
    {
        $rule = new NotSimilarToUserData('Michael', 'Brown', 'mike@test.com');
        $failed = false;
        $rule->validate('password', 'MICHAEL!!99', function () use (&$failed) { $failed = true; });
        $this->assertTrue($failed);
    }
}
