<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\SavedCard;
use App\Models\PasswordReset;
use App\Models\UserSubscription;
use App\Models\SubscribeSaveItem;
use App\Models\Wishlist;
use Carbon\Carbon;

class ModelTest extends TestCase
{
    // ─────────────────────────────────────────
    // SAVED CARD
    // ─────────────────────────────────────────

    /** @test */
    public function saved_card_detects_visa() { $this->assertEquals('visa', SavedCard::detectBrand('4111111111111111')); }

    /** @test */
    public function saved_card_detects_mastercard() { $this->assertEquals('mastercard', SavedCard::detectBrand('5500000000000004')); }

    /** @test */
    public function saved_card_detects_amex() { $this->assertEquals('amex', SavedCard::detectBrand('371449635398431')); }

    /** @test */
    public function saved_card_detects_unknown() { $this->assertEquals('unknown', SavedCard::detectBrand('9999999999')); }

    /** @test */
    public function saved_card_masked_number()
    {
        $card = new SavedCard();
        $card->last_four = '4242';
        $this->assertEquals('•••• •••• •••• 4242', $card->masked_number);
    }

    /** @test */
    public function saved_card_expiry_display()
    {
        $card = new SavedCard();
        $card->expiry_month = 3;
        $card->expiry_year = 2027;
        $this->assertEquals('03/27', $card->expiry_display);
    }

    /** @test */
    public function saved_card_is_expired_past()
    {
        $card = new SavedCard();
        $card->expiry_month = 1;
        $card->expiry_year = 2020;
        $this->assertTrue($card->is_expired);
    }

    /** @test */
    public function saved_card_not_expired_future()
    {
        $card = new SavedCard();
        $card->expiry_month = 12;
        $card->expiry_year = 2030;
        $this->assertFalse($card->is_expired);
    }

    // ─────────────────────────────────────────
    // PASSWORD RESET
    // ─────────────────────────────────────────

    /** @test */
    public function password_reset_not_expired_within_15_min()
    {
        $reset = new PasswordReset();
        $reset->created_at = now();
        $reset->used = false;
        $this->assertFalse($reset->isExpired());
        $this->assertTrue($reset->isValid());
    }

    /** @test */
    public function password_reset_expired_after_15_min()
    {
        $reset = new PasswordReset();
        $reset->created_at = now()->subMinutes(20);
        $reset->used = false;
        $this->assertTrue($reset->isExpired());
        $this->assertFalse($reset->isValid());
    }

    /** @test */
    public function password_reset_invalid_if_used()
    {
        $reset = new PasswordReset();
        $reset->created_at = now();
        $reset->used = true;
        $this->assertFalse($reset->isValid());
    }

    // ─────────────────────────────────────────
    // USER SUBSCRIPTION
    // ─────────────────────────────────────────

    /** @test */
    public function subscription_is_active_when_valid()
    {
        $sub = new UserSubscription();
        $sub->status = 'Active';
        $sub->expires_at = now()->addMonth();
        $this->assertTrue($sub->isActive());
    }

    /** @test */
    public function subscription_not_active_when_expired()
    {
        $sub = new UserSubscription();
        $sub->status = 'Active';
        $sub->expires_at = now()->subDay();
        $this->assertFalse($sub->isActive());
    }

    /** @test */
    public function subscription_not_active_when_cancelled()
    {
        $sub = new UserSubscription();
        $sub->status = 'Cancelled';
        $sub->expires_at = now()->addMonth();
        $this->assertFalse($sub->isActive());
    }

    // ─────────────────────────────────────────
    // SUBSCRIBE & SAVE ITEM
    // ─────────────────────────────────────────

    /** @test */
    public function ss_item_not_suspended_when_null()
    {
        $item = new SubscribeSaveItem();
        $item->is_active = true;
        $item->suspended_at = null;
        $this->assertFalse($item->isSuspended());
    }

    /** @test */
    public function ss_item_suspended_when_set()
    {
        $item = new SubscribeSaveItem();
        $item->is_active = true;
        $item->suspended_at = now();
        $this->assertTrue($item->isSuspended());
    }

    /** @test */
    public function ss_frequency_label_1_week()
    {
        $item = new SubscribeSaveItem();
        $item->frequency_weeks = 1;
        $this->assertEquals('Every week', $item->frequencyLabel());
    }

    /** @test */
    public function ss_frequency_label_4_weeks()
    {
        $item = new SubscribeSaveItem();
        $item->frequency_weeks = 4;
        $this->assertEquals('Every month', $item->frequencyLabel());
    }

    /** @test */
    public function ss_frequency_label_12_weeks()
    {
        $item = new SubscribeSaveItem();
        $item->frequency_weeks = 12;
        $this->assertEquals('Every 3 months', $item->frequencyLabel());
    }

    /** @test */
    public function ss_frequency_label_custom()
    {
        $item = new SubscribeSaveItem();
        $item->frequency_weeks = 6;
        $this->assertEquals('Every 6 weeks', $item->frequencyLabel());
    }

    // ─────────────────────────────────────────
    // WISHLIST
    // ─────────────────────────────────────────

    /** @test */
    public function wishlist_share_url_null_when_private()
    {
        $wishlist = new Wishlist();
        $wishlist->is_public = false;
        $wishlist->slug = 'my-list';
        $this->assertNull($wishlist->getShareUrl());
    }

    /** @test */
    public function wishlist_share_url_null_without_slug()
    {
        $wishlist = new Wishlist();
        $wishlist->is_public = true;
        $wishlist->slug = null;
        $this->assertNull($wishlist->getShareUrl());
    }
}