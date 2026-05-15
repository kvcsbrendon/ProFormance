<footer class="kb-footer">
    <div class="kb-footer-inner">
        <div class="kb-footer-col kb-footer-brand">
            <a href="{{ route('home') }}" class="kb-footer-logo">
                <img src="{{ asset('images/logo.png') }}" alt="ProFormance logo" class="kb-footer-logo-img">
                <span class="kb-footer-logo-text">ProFormance</span>
            </a>

            <p class="kb-footer-blurb">
                Performance-driven gym wear, nutrition, and gear to help you train smarter
                and feel stronger every day.
            </p>

            <form method="POST" action="{{ route('newsletter.subscribe') }}" class="kb-footer-newsletter">
                @csrf
                <label for="footer-email" class="kb-footer-newsletter-label">
                    Subscribe to our newsletter
                </label>
                <input type="email" name="email_address" class="kb-footer-newsletter-input" placeholder="Email for newsletter" required>
                <button class="kb-footer-newsletter-btn" type="submit">Subscribe</button>
                <p class="kb-footer-newsletter-note">
                    Get early access to drops, offers and training tips. You can unsubscribe any time.
                </p>
            </form>

            <div class="kb-footer-social">
                <a href="#" aria-label="Instagram">
                    <i class="bi bi-instagram"></i>
                </a>
                <a href="#" aria-label="Facebook">
                    <i class="bi bi-facebook"></i>
                </a>
                <a href="#" aria-label="Twitter">
                    <i class="bi bi-twitter-x"></i>
                </a>
            </div>
        </div>

        <div class="kb-footer-col">
            <h4 class="kb-footer-heading">Shop</h4>
            <ul class="kb-footer-links">
                <li><a href="{{ route('products.index', ['deals' => 1]) }}">Deals</a></li>
                <li><a href="{{ route('products.category', 'gym-apparel') }}">Unisex Apparel</a></li>
                <li><a href="{{ route('products.category', 'nutrition-and-supplements') }}">Performance Nutrition</a></li>
                <li><a href="{{ route('products.category', 'recovery-and-wellness') }}">Advanced Supplements</a></li>
                <li><a href="{{ route('products.category', 'accessories') }}">Gym Accessories</a></li>
                <li><a href="{{ route('products.category', 'gym-equipment') }}">Bags &amp; Equipment</a></li>
            </ul>
        </div>

        <div class="kb-footer-col">
            <h4 class="kb-footer-heading">Help</h4>
            <ul class="kb-footer-links">
                <li><a href="{{ route('shipping') }}">Shipping &amp; delivery</a></li>
                <li><a href="{{ route('returns') }}">Returns &amp; refunds</a></li>
                <li><a href="{{ route('size-guide') }}">Size guide</a></li>
                <li><a href="{{ route('contact') }}">Contact us</a></li>
                <li><a href="{{ route('faq') }}">FAQ</a></li>
                <li><a href="{{ route('gift-cards') }}">Gift cards</a></li>
                <li><a href="{{ route('discounts') }}">Student discount</a></li>
                <li><a href="{{ route('discounts') }}">Teacher discount</a></li>
                <li><a href="{{ route('discounts') }}">First responder discount</a></li>
            </ul>
        </div>

        <div class="kb-footer-col">
            <h4 class="kb-footer-heading">About</h4>
            <ul class="kb-footer-links">
                <li><a href="{{ route('about') }}">About us</a></li>
                <li><a href="{{ route('sustainability') }}">Sustainability</a></li>
                <li><a href="{{ route('careers') }}">Careers</a></li>
                <li><a href="{{ route('affiliates') }}">Affiliates</a></li>
            </ul>
        </div>
    </div> {{-- Close kb-footer-inner --}}

    <div class="kb-footer-bottom">
        <p class="kb-footer-copy">
            &copy; {{ date('Y') }} ProFormance. All rights reserved.
        </p>
        <div class="kb-footer-bottom-links">
            <a href="{{ route('privacy') }}">Privacy policy</a>
            <span class="kb-footer-separator">•</span>
            <a href="{{ route('gdpr') }}">GDPR &amp; data protection</a>
            <span class="kb-footer-separator">•</span>
            <a href="{{ route('terms') }}">Terms &amp; conditions</a>
            <span class="kb-footer-separator">•</span>
            <a href="{{ route('cookies') }}">Cookies</a>
        </div>
    </div>

    <div class="kb-footer-payments">
        <div class="kb-payment-section">
            <div class="kb-payment-icons">
                <x-icon name="amex" />
                <x-icon name="mastercard" />
                <x-icon name="paypal" />
                <x-icon name="visa" />
            </div>
        </div>
        <div class="kb-app-badges">
            <a href="#" class="kb-app-badge">
                <img src="{{ asset('images/payments/google-play.svg') }}" alt="Get it on Google Play">
            </a>
            <a href="#" class="kb-app-badge">
                <img src="{{ asset('images/payments/app-store.svg') }}" alt="Download on the App Store">
            </a>
        </div>
    </div>
</footer>