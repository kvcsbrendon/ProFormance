<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\Category;
use App\Models\CustomerContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    /**
     * Handle a chat message and return a response.
     */
    public function message(Request $request)
    {
        $request->validate(['message' => 'required|string|max:500']);

        $input = strtolower(trim($request->message));
        $response = $this->processMessage($input, $request->message);

        return response()->json($response);
    }

    private function processMessage(string $input, string $original): array
    {
        // ── Greetings ──
        if ($this->matches($input, ['hi', 'hello', 'hey', 'hiya', 'howdy', 'good morning', 'good afternoon', 'good evening', 'sup', 'yo'])) {
            $greeting = Auth::check() ? 'Hi ' . Auth::user()->first_name . '!' : 'Hi there!';
            return $this->reply(
                "$greeting Welcome to ProFormance. I can help you with:\n\n• Finding products\n• Checking your order status\n• Shipping & returns info\n• Account help\n\nWhat can I help you with?",
                $this->quickReplies(['Browse products', 'Track my order', 'Shipping info', 'Contact support'])
            );
        }
        // ── Quick reply actions (exact matches) ──
        if (in_array($input, ['browse products', 'view all products', 'shop', 'shop now'])) {
            return $this->reply(
                "Here's our full product range — use filters to narrow it down!",
                $this->withLink('Browse All Products', '/products')
            );
        }

        if (in_array($input, ['track my order', 'my orders', 'order history'])) {
            return $this->handleOrderTracking($input);
        }

        if (in_array($input, ['shipping info', 'delivery info'])) {
            return $this->handleShipping();
        }

        if (in_array($input, ['returns & refunds', 'returns and refunds', 'return policy'])) {
            return $this->handleReturns();
        }

        if (in_array($input, ['contact support', 'speak to someone', 'talk to someone'])) {
            return $this->handleContactSupport();
        }

        if (in_array($input, ['my wishlists', 'my wishlist', 'wishlists'])) {
            return $this->reply(
                "Here are your wishlists:",
                $this->withLink('View Wishlists', '/wishlist')
            );
        }

        if (in_array($input, ['change password', 'reset password'])) {
            return $this->reply(
                "You can change your password in your security settings:",
                $this->withLink('Security Settings', '/account/security')
            );
        }

        if (in_array($input, ['student discount'])) {
            return $this->reply(
                "We offer exclusive discounts for students!",
                $this->withLink('Student Discount', '/help/student-discount')
            );
        }

        // ── Goodbye ──
        if ($this->matches($input, ['bye', 'goodbye', 'thanks', 'thank you', 'cheers', 'ta', 'that\'s all', 'thats all'])) {
            return $this->reply("You're welcome! If you need anything else, just ask. Happy training! 💪");
        }

        // ── Product search ──
        if ($this->matches($input, ['product', 'find', 'search', 'looking for', 'do you sell', 'do you have', 'have you got', 'show me', 'i want', 'i need', 'buy', 'shop', 'browse'])) {
            return $this->handleProductSearch($input);
        }

        // ── Category browsing ──
        if ($this->matches($input, ['category', 'categories', 'what do you sell', 'what products', 'range', 'catalogue', 'catalog'])) {
            return $this->handleCategories();
        }

        // ── Order tracking ──
        if ($this->matches($input, ['order', 'track', 'tracking', 'where is my', 'delivery status', 'my order', 'order status', 'when will', 'dispatch', 'shipped'])) {
            return $this->handleOrderTracking($input);
        }

        // ── Shipping ──
        if ($this->matches($input, ['shipping', 'delivery', 'how long', 'ship', 'deliver', 'postage', 'shipping cost', 'delivery time', 'next day', 'standard delivery', 'international'])) {
            return $this->handleShipping();
        }

        // ── Returns & Refunds ──
        if ($this->matches($input, ['return', 'refund', 'exchange', 'send back', 'money back', 'return policy', 'damaged', 'wrong item', 'broken'])) {
            return $this->handleReturns();
        }

        // ── Size guide ──
        if ($this->matches($input, ['size', 'sizing', 'size guide', 'what size', 'fit', 'measurements', 'too big', 'too small'])) {
            return $this->reply(
                "You can find our detailed size guide with measurements for all apparel here:",
                $this->withLink('View Size Guide', '/size-guide')
            );
        }

        // ── Account help ──
        if ($this->matches($input, ['account', 'profile', 'my details', 'settings', 'change email', 'update', 'edit profile'])) {
            return $this->handleAccountHelp($input);
        }

        // ── Password ──
        if ($this->matches($input, ['password', 'forgot password', 'reset password', 'change password', 'can\'t login', 'cant login', 'locked out'])) {
            return $this->reply(
                "To reset your password, click the link below and enter your email address. You'll receive a reset link valid for 15 minutes.",
                $this->withLink('Reset Password', '/forgot-password')
            );
        }

        // ── Wishlist ──
        if ($this->matches($input, ['wishlist', 'wish list', 'save for later', 'favourites', 'favorites'])) {
            return $this->reply(
                "You can save products to wishlists by clicking the heart icon on any product. Create multiple wishlists, share them with friends, and even let people buy gifts from your list!",
                $this->withLink('View Wishlists', '/wishlist')
            );
        }

        // ── Discount / Promo ──
        if ($this->matches($input, ['discount', 'promo', 'coupon', 'code', 'voucher', 'sale', 'offer', 'deal', 'student discount'])) {
            return $this->reply(
                "You can apply discount codes at checkout in the 'Discount Code' field. We regularly run promotions — keep an eye on our homepage and newsletter for the latest deals!\n\nWe also offer student, teacher, and first responder discounts.",
                $this->quickReplies(['Student discount', 'Browse products'])
            );
        }

        // ── Student discount ──
        if ($this->matches($input, ['student'])) {
            return $this->reply(
                "We offer exclusive discounts for students! Check out the details here:",
                $this->withLink('Student Discount', '/student-discount')
            );
        }

        // ── Payment ──
        if ($this->matches($input, ['payment', 'pay', 'card', 'paypal', 'how to pay', 'accepted cards', 'payment method'])) {
            return $this->reply(
                "We accept Visa, Mastercard, American Express, and PayPal. All payments are processed securely. You can also save your card details for faster checkout next time.");
        }
        

        // ── Contact / Support ──
        if ($this->matches($input, ['contact', 'support', 'help', 'speak to', 'talk to', 'human', 'agent', 'email', 'phone', 'complaint', 'problem', 'issue'])) {
            return $this->handleContactSupport();
        }

        // ── Newsletter ──
        if ($this->matches($input, ['newsletter', 'subscribe', 'updates', 'mailing list'])) {
            return $this->reply("You can subscribe to our newsletter at the bottom of our homepage to get the latest product launches, deals, and training tips straight to your inbox!");
        }

        // ── FAQ ──
        if ($this->matches($input, ['faq', 'frequently asked', 'questions', 'help page'])) {
            return $this->reply(
                "Check out our FAQ page for answers to the most common questions:",
                $this->withLink('View FAQ', '/faq')
            );
        }

        // ── Opening hours / About ──
        if ($this->matches($input, ['hours', 'open', 'about', 'who are you', 'what is proformance', 'about you'])) {
            return $this->reply(
                "ProFormance is your one-stop shop for premium fitness equipment, performance nutrition, and gym apparel. We're all about helping you train harder and recover smarter.",
                $this->withLink('About Us', '/about')
            );
        }

        // ── Fallback ──
        return $this->reply(
            "I'm not sure I understand that. Here are some things I can help with:",
            $this->quickReplies(['Browse products', 'Track my order', 'Shipping info', 'Returns & refunds', 'Contact support'])
        );
    }

    // ═══════════════════════════════════════════
    // INTENT HANDLERS
    // ═══════════════════════════════════════════

    private function handleProductSearch(string $input): array
    {
        // Extract search terms (remove common words)
        $stopWords = ['product', 'products', 'find', 'search', 'looking', 'for', 'do', 'you', 'sell', 'have', 'got',
               'show', 'me', 'i', 'want', 'need', 'buy', 'shop', 'browse', 'any', 'some', 'a', 'the',
               'can', 'please', 'where', 'is', 'are', 'there', 'get', 'all', 'view'];
        $words = array_diff(explode(' ', $input), $stopWords);
        $searchTerm = implode(' ', $words);

        if (empty(trim($searchTerm))) {
            $categories = Category::whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->limit(6)
                ->pluck('category_name')
                ->toArray();

            return $this->reply(
                "What kind of product are you looking for? We have a wide range including:",
                $this->quickReplies(array_merge($categories, ['View all products']))
            );
        }

        $products = Product::where('is_active', true)
            ->where(function ($q) use ($searchTerm) {
                $q->where('product_name', 'like', "%{$searchTerm}%")
                  ->orWhere('product_description', 'like', "%{$searchTerm}%")
                  ->orWhere('short_description', 'like', "%{$searchTerm}%")
                  ->orWhereHas('brand', fn($b) => $b->where('brand_name', 'like', "%{$searchTerm}%"))
                  ->orWhereHas('categories', fn($c) => $c->where('category_name', 'like', "%{$searchTerm}%"));
            })
            ->with('brand')
            ->limit(5)
            ->get();

        if ($products->isEmpty()) {
            return $this->reply(
                "I couldn't find any products matching \"{$searchTerm}\". Try a different search term, or browse our full catalogue.",
                $this->withLink('Browse All Products', '/products')
            );
        }

        $productList = $products->map(function ($p) {
            $price = $p->bestPriceForCurrency();
            $priceStr = $price ? " — {$price->symbol}" . number_format($price->price, 2) : '';
            return "• [{$p->product_name}](/products/{$p->product_id}){$priceStr}";
        })->implode("\n");

        $count = $products->count();
        $msg = "I found {$count} product" . ($count > 1 ? 's' : '') . " matching \"{$searchTerm}\":\n\n{$productList}";

        if ($count === 5) {
            $msg .= "\n\nThere may be more results.";
        }

        return $this->reply($msg, $this->withLink('View All Results', '/products?search=' . urlencode($searchTerm)));
    }

    private function handleCategories(): array
    {
        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($categories->isEmpty()) {
            return $this->reply("Browse our full product range:", $this->withLink('View Products', '/products'));
        }

        $catList = $categories->map(fn($c) => "• [{$c->category_name}](/category/{$c->slug})")->implode("\n");

        return $this->reply("Here are our product categories:\n\n{$catList}");
    }

    private function handleOrderTracking(string $input): array
    {
        if (!Auth::check()) {
            return $this->reply(
                "You'll need to sign in to check your order status.",
                $this->withLink('Sign In', '/login')
            );
        }

        $user = Auth::user();

        // Try to extract order number from input
        preg_match('/(?:ORD-?)?(\d{4,})/i', $input, $matches);

        if (!empty($matches[0])) {
            $orderNum = $matches[0];
            $order = Order::where('user_id', $user->user_id)
                ->where(function ($q) use ($orderNum) {
                    $q->where('order_number', $orderNum)
                      ->orWhere('order_number', 'like', "%{$orderNum}%");
                })
                ->first();

            if ($order) {
                $status = $order->order_status;
                $date = $order->created_at->format('d M Y');
                $total = '£' . number_format($order->total_penny / 100, 2);

                $statusMsg = match($status) {
                    'Pending'   => "Your order is being processed. We'll update you once payment is confirmed.",
                    'Paid'      => "Payment confirmed! Your order is being prepared for dispatch.",
                    'Fulfilled' => "Great news — your order has been dispatched! It should arrive soon.",
                    'Cancelled' => "This order has been cancelled.",
                    'Refunded'  => "This order has been refunded.",
                    default     => "Current status: {$status}.",
                };

                return $this->reply(
                    "📦 **Order {$order->order_number}**\nPlaced: {$date}\nTotal: {$total}\nStatus: **{$status}**\n\n{$statusMsg}",
                    $this->withLink('View Order Details', '/account/orders/' . $order->order_id)
                );
            }

            return $this->reply("I couldn't find an order with that number. Please check the number and try again, or view all your orders:",
                $this->withLink('View My Orders', '/account/orders'));
        }

        // Show recent orders
        $orders = Order::where('user_id', $user->user_id)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        if ($orders->isEmpty()) {
            return $this->reply("You don't have any orders yet. Ready to start shopping?",
                $this->withLink('Browse Products', '/products'));
        }

        $orderList = $orders->map(function ($o) {
            $total = '£' . number_format($o->total_penny / 100, 2);
            return "• **{$o->order_number}** — {$o->order_status} ({$total})";
        })->implode("\n");

        return $this->reply(
            "Here are your recent orders:\n\n{$orderList}\n\nTell me an order number for more details, or view all orders:",
            $this->withLink('View All Orders', '/account/orders')
        );
    }

    private function handleShipping(): array
    {
        // Pull live rates from DB if available
        $rates = [];
        try {
            $dbRates = \App\Models\ShippingRate::where('is_active', true)->orderBy('zone_name')->orderBy('sort_order')->get();
            foreach ($dbRates as $r) {
                $rates[] = "• **{$r->method_label}** ({$r->zone_name}) — £" . number_format($r->price_penny / 100, 2);
            }
        } catch (\Exception $e) {
            // ShippingRate model might not exist yet
        }

        if (empty($rates)) {
            $rates = [
                "• **Standard Delivery** (UK, 3-5 days) — £7.99",
                "• **Next Day Delivery** (UK) — £12.99",
                "• **International Standard** (7-14 days) — £19.99",
            ];
        }

        $rateList = implode("\n", $rates);

        return $this->reply(
            "Our delivery options:\n\n{$rateList}\n\nOrders are typically dispatched within 1-2 business days.",
            $this->withLink('Shipping & Delivery Info', '/shipping-delivery')
        );
    }

    private function handleReturns(): array
    {
        return $this->reply(
            "We want you to be happy with your purchase. Here's our returns policy:\n\n• **30-day returns** on most items\n• Items must be unused and in original packaging\n• Refunds processed within 5-7 business days\n• Free returns on faulty or incorrect items\n\nNeed to start a return? Contact our support team.",
            array_merge(
                $this->withLink('Returns Policy', '/returns-refunds'),
                $this->withLink('Contact Support', '/contact')
            )
        );
    }

    private function handleAccountHelp(string $input): array
    {
        if (!Auth::check()) {
            return $this->reply(
                "You'll need to sign in to manage your account.",
                $this->withLink('Sign In', '/login')
            );
        }

        return $this->reply(
            "Here's what you can do in your account:\n\n• View and track orders\n• Manage addresses\n• Update security settings & 2FA\n• Manage saved cards\n• View wishlists\n• Check messages",
            $this->quickReplies(['My orders', 'Change password', 'My wishlists'])
        );
    }

    private function handleContactSupport(): array
    {
        return $this->reply(
            "I'd be happy to connect you with our support team. You can:\n\n• **Submit a ticket** — we usually respond within 24 hours\n• **Email us** — support@proformance.com\n\nOr tell me more about your issue and I'll try to help!",
            $this->withLink('Contact Support', '/contact')
        );
    }

    // ═══════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════

    private function matches(string $input, array $keywords): bool
    {
        foreach ($keywords as $kw) {
            if (str_contains($input, $kw)) return true;
        }
        return false;
    }

    private function reply(string $message, array $extras = []): array
    {
        return array_merge(['message' => $message], $extras);
    }

    private function quickReplies(array $options): array
    {
        return ['quick_replies' => $options];
    }

    private function withLink(string $label, string $url): array
    {
        return ['links' => [['label' => $label, 'url' => $url]]];
    }
}
