@php
    $currentCurrency = session('currency', 'gbp');
    $currentLocale = session('locale', 'en');

    $currencies = [
        'gbp' => '£ GBP',
        'eur' => '€ EUR',
        'usd' => '$ USD',
    ];

    $locales = [
        'en' => 'English',
        'es' => 'Español',
        'de' => 'Deutsch',
    ];
@endphp

{{-- ═══ TOP BAR (desktop only — hidden on mobile via CSS) ═══ --}}
<div class="kb-top-bar">
    <div class="kb-top-bar-inner">
        <div class="kb-top-bar-left">
            <form method="POST" action="{{ route('currency.update') }}">
                @csrf
                <div class="kb-dropdown kb-dropdown-left">
                    <button type="button" class="kb-dropdown-toggle">
                        <span class="kb-selected-option">{{ $currencies[$currentCurrency] }}</span>
                        <span class="kb-chevron">▾</span>
                    </button>
                    <div class="kb-dropdown-menu">
                        @foreach ($currencies as $code => $label)
                            <div class="kb-dropdown-item" data-value="{{ $code }}">
                                {{ $label }}
                            </div>
                        @endforeach
                    </div>
                    <input type="hidden" name="currency" value="{{ $currentCurrency }}">
                </div>
            </form>
        </div>

        <div class="kb-top-bar-centre">
            <div class="kb-promo-text">
                <strong>New here?</strong> Use code <span class="kb-promo-code">WELCOME10</span> for 10% off your first order
            </div>
        </div>

        <div class="kb-top-bar-right">
            <div class="kb-top-bar-links">
                <a href="{{ route('contact') }}">Help</a>
                <span></span>
                <span></span>
                @auth
                    <a href="{{ route('account.orders') }}">Track Order</a>
                    <span></span>
                    <span></span>
                @endauth
            </div>
        </div>
    </div>
</div>

{{-- ═══ MAIN HEADER ═══ --}}
<div class="kb-main-header">
    <div class="kb-main-header-inner">

        {{-- Left: hamburger (mobile) + logo --}}
        <div class="kb-mh-left">
            <button type="button" class="kb-mobile-hamburger"
                    onclick="toggleMobileMenu()" aria-label="Open menu">
                <i class="bi bi-list" id="hamburger-icon"></i>
            </button>

            <a href="{{ route('home') }}" class="kb-logo">
                <img src="{{ asset('images/logo.png') }}" alt="Brand logo" class="kb-logo-img">
                <span class="kb-logo-text">ProFormance</span>
            </a>
        </div>

        {{-- Centre: desktop search (hidden on mobile via CSS) --}}
        <div class="kb-mh-center">
            <div class="kb-search-wrapper">
                <form action="{{ route('products.index') }}" method="GET" class="kb-search-bar">
                    @foreach((array) request('category', []) as $cat)
                        <input type="hidden" name="category[]" value="{{ $cat }}">
                    @endforeach
                    @foreach((array) request('brand', []) as $b)
                        <input type="hidden" name="brand[]" value="{{ $b }}">
                    @endforeach
                    @if(request()->filled('min_price'))
                        <input type="hidden" name="min_price" value="{{ request('min_price') }}">
                    @endif
                    @if(request()->filled('max_price'))
                        <input type="hidden" name="max_price" value="{{ request('max_price') }}">
                    @endif
                    @if(request()->filled('sort'))
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                    @endif
                    @if(request()->filled('deals'))
                        <input type="hidden" name="deals" value="1">
                    @endif

                    <input type="text" name="search" placeholder="Search products..."
                        value="{{ request('search') }}">
                    <button type="submit"><i class="bi bi-search"></i></button>
                </form>
            </div>
        </div>

        {{-- Right --}}
        <div class="kb-mh-right">

            {{-- Mobile search icon (hidden on desktop) --}}
            <button type="button" class="kb-mobile-search-btn"
                    onclick="toggleMobileSearch()" aria-label="Search">
                <i class="bi bi-search"></i>
            </button>

            {{-- Cart icon (always visible — label hidden on mobile) --}}
            <div class="kb-cart-hover">
                <a href="/cart" class="kb-cart-btn" aria-label="Cart" id="cart-link">
                    <span class="kb-cart-icon-wrapper">
                        <i class="bi bi-bag kb-cart-icon"></i>
                        <span class="kb-cart-count" id="cart-count">{{ $cartItemCount ?? 0 }}</span>
                    </span>
                    <span class="kb-cart-label">Cart</span>
                </a>

                {{-- Cart preview (desktop only) --}}
                <div class="kb-cart-preview" id="cart-preview">
                    <div class="cart-preview-header">
                        <h4>Your Cart</h4>
                        <small id="preview-total-items">0 items</small>
                    </div>
                    <div class="cart-preview-items" id="preview-items"></div>
                    <div class="cart-preview-total">
                        <strong>Total: <span id="preview-total-price">£0.00</span></strong>
                    </div>
                    <div class="cart-preview-footer">
                        <a href="/cart" class="btn-view-cart">View Cart</a>
                    </div>
                </div>
            </div>

            {{-- Account wrapper (desktop only — hidden on mobile) --}}
            <div class="kb-account-wrapper kb-desktop-only">
                <button type="button" class="kb-account-btn">
                    <i class="bi bi-person-circle kb-account-icon"></i>
                    <div class="kb-account-text">
                        @auth
                            <span class="kb-account-greet">Hello, {{ auth()->user()->first_name }}</span>
                            <span class="kb-account-action">My Account</span>
                        @else
                            <span class="kb-account-greet">Welcome</span>
                            <span class="kb-account-action">Sign in</span>
                        @endauth
                    </div>
                </button>

                <div class="kb-account-menu">
                    @auth
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            @if(auth()->user()->user_role === 'admin')
                                <a href="{{ route('admin.dashboard') }}">Admin Panel</a>
                            @endif
                            <a href="{{ route('account.dashboard') }}">My Account</a>
                            <button type="submit" class="kb-logout-btn g_id_signout">Sign out</button>
                        </form>
                    @else
                        <a href="{{ route('auth.emailCheck') }}">Sign in/up</a>
                    @endauth
                </div>
            </div>

            {{-- Theme toggle --}}
            <button id="theme-toggle" type="button" class="theme-toggle-btn">
                <i class="bi bi-moon-fill" id="theme-icon"></i>
            </button>
        </div>
    </div>

    {{-- Mobile search bar (hidden, expands below header) --}}
    <div class="kb-mobile-search" id="mobile-search-bar">
        <form action="{{ route('products.index') }}" method="GET" class="kb-mobile-search-form">
            <input type="text" name="search" placeholder="Search products..."
                   value="{{ request('search') }}">
            <button type="submit"><i class="bi bi-search"></i></button>
        </form>
    </div>
</div>

{{-- ═══ MOBILE MENU ═══ --}}
<div class="kb-mobile-menu" id="mobile-menu">
    <nav class="kb-mobile-nav">

        {{-- Account --}}
        <div class="kb-mobile-nav-section">
            @auth
                @if(auth()->user()->user_role === 'admin')
                    <a href="{{ route('admin.dashboard') }}" class="kb-mobile-nav-link kb-mobile-nav-admin">
                        <i class="bi bi-shield-lock"></i> Admin Panel
                    </a>
                @endif
                <a href="{{ route('account.dashboard') }}" class="kb-mobile-nav-link">
                    <i class="bi bi-person"></i> My Account
                </a>
                <a href="{{ route('account.orders') }}" class="kb-mobile-nav-link">
                    <i class="bi bi-box-seam"></i> Orders
                </a>
                <a href="{{ route('wishlist.index') }}" class="kb-mobile-nav-link">
                    <i class="bi bi-heart"></i> Wishlist
                </a>
            @else
                <a href="{{ route('auth.emailCheck') }}" class="kb-mobile-nav-link kb-mobile-nav-signin">
                    <i class="bi bi-box-arrow-in-right"></i> Sign in / Register
                </a>
            @endauth
        </div>

        {{-- Shop --}}
        <div class="kb-mobile-nav-section">
            <span class="kb-mobile-nav-label">Shop</span>
            <a href="{{ route('products.index') }}" class="kb-mobile-nav-link">
                <i class="bi bi-grid"></i> All Products
            </a>
            <a href="{{ route('products.index', ['category' => 'gym-equipment']) }}" class="kb-mobile-nav-link">
                <i class="bi bi-bicycle"></i> Gym Equipment
            </a>
            <a href="{{ route('products.index', ['category' => 'gym-apparel']) }}" class="kb-mobile-nav-link">
                <i class="bi bi-person-arms-up"></i> Gym Apparel
            </a>
            <a href="{{ route('products.index', ['category' => 'nutrition-and-supplements']) }}" class="kb-mobile-nav-link">
                <i class="bi bi-cup-straw"></i> Nutrition & Supplements
            </a>
            <a href="{{ route('products.index', ['category' => 'accessories']) }}" class="kb-mobile-nav-link">
                <i class="bi bi-watch"></i> Accessories
            </a>
            <a href="{{ route('products.index', ['category' => 'recovery-and-wellness']) }}" class="kb-mobile-nav-link">
                <i class="bi bi-heart-pulse"></i> Recovery & Wellness
            </a>
            <a href="{{ route('products.index', ['deals' => 1]) }}" class="kb-mobile-nav-link kb-mobile-nav-deals">
                <i class="bi bi-tag"></i> Deals & Offers
            </a>
        </div>

        {{-- Support --}}
        <div class="kb-mobile-nav-section">
            <span class="kb-mobile-nav-label">Support</span>
            <a href="{{ route('contact') }}" class="kb-mobile-nav-link">
                <i class="bi bi-envelope"></i> Contact Us
            </a>
            <a href="{{ route('faq') }}" class="kb-mobile-nav-link">
                <i class="bi bi-question-circle"></i> FAQ
            </a>
        </div>

        {{-- Currency --}}
        <div class="kb-mobile-nav-section">
            <span class="kb-mobile-nav-label">Currency</span>
            <div class="kb-mobile-currency">
                @foreach ($currencies as $code => $label)
                    <form method="POST" action="{{ route('currency.update') }}" style="display:inline;">
                        @csrf
                        <input type="hidden" name="currency" value="{{ $code }}">
                        <button type="submit"
                                class="kb-mobile-currency-btn {{ $currentCurrency === $code ? 'kb-mobile-currency-active' : '' }}">
                            {{ $label }}
                        </button>
                    </form>
                @endforeach
            </div>
        </div>

        {{-- Sign out --}}
        @auth
            <div class="kb-mobile-nav-section kb-mobile-nav-footer">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="kb-mobile-nav-link kb-mobile-nav-logout">
                        <i class="bi bi-box-arrow-right"></i> Sign out
                    </button>
                </form>
            </div>
        @endauth
    </nav>
</div>

{{-- ═══ BOTTOM NAV (desktop only) ═══ --}}
<div class="kb-bottom-header">
    <div class="kb-bottom-header-inner">
        <div class="kb-hamburger-wrapper">
            <button type="button" class="kb-all-btn">
                <i class="bi bi-justify kb-hamburger-icon"></i>
                <span class="kb-all-label">All Categories</span>
            </button>
        </div>

        <div class="kb-bt-center">
            <nav class="kb-nav">
                <ul class="kb-nav-links">
                    <li class="kb-nav-item"><a href="{{ route('products.index') }}" class="kb-nav-link">Products</a></li>
                    <li class="kb-nav-item"><a href="{{ route('products.index', ['category' => 'gym-equipment']) }}" class="kb-nav-link">Gym Equipment</a></li>
                    <li class="kb-nav-item"><a href="{{ route('products.index', ['category' => 'gym-apparel']) }}" class="kb-nav-link">Gym Apparel</a></li>
                    <li class="kb-nav-item"><a href="{{ route('products.index', ['category' => 'nutrition-and-supplements']) }}" class="kb-nav-link">Nutrition &amp; Supplements</a></li>
                    <li class="kb-nav-item"><a href="{{ route('products.index', ['category' => 'accessories']) }}" class="kb-nav-link">Accessories</a></li>
                    <li class="kb-nav-item"><a href="{{ route('products.index', ['category' => 'recovery-and-wellness']) }}" class="kb-nav-link">Recovery &amp; Wellness</a></li>
                    <li class="kb-nav-item"><a href="{{ route('contact') }}" class="kb-nav-link">Contact us</a></li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<script>
function toggleMobileMenu() {
    var menu = document.getElementById('mobile-menu');
    var icon = document.getElementById('hamburger-icon');
    var open = menu.style.display === 'block';

    menu.style.display = open ? 'none' : 'block';
    icon.className = open ? 'bi bi-list' : 'bi bi-x-lg';

    if (!open) {
        document.getElementById('mobile-search-bar').style.display = 'none';
    }
}

function toggleMobileSearch() {
    var bar = document.getElementById('mobile-search-bar');
    var open = bar.style.display === 'block';
    bar.style.display = open ? 'none' : 'block';

    if (!open) {
        bar.querySelector('input[name="search"]').focus();
        document.getElementById('mobile-menu').style.display = 'none';
        document.getElementById('hamburger-icon').className = 'bi bi-list';
    }
}
</script>

<style>
    .kb-promo-code {
        display: inline-block;
        background: rgba(255,255,255,0.2);
        padding: 1px 8px;
        border-radius: 4px;
        font-weight: 700;
        letter-spacing: 1px;
        font-family: monospace;
        font-size: 12px;
        border: 1px dashed rgba(255,255,255,0.4);
    }
    .kb-cart-icon { color: var(--kb-primary-font, #111827); font-size: 20px; transition: color 0.2s; }
    .kb-cart-btn:hover .kb-cart-icon { color: var(--kb-accent, #EB7347); }
    .kb-cart-label { color: var(--kb-primary-font, #111827); }
    .kb-cart-count { background: var(--kb-accent, #EB7347); color: #fff; }
    [data-theme="dark"] .kb-cart-icon,
    [data-theme="dark"] .kb-cart-label { color: var(--kb-primary-font); }
    [data-theme="dark"] .kb-cart-btn:hover .kb-cart-icon,
    [data-theme="dark"] .kb-cart-btn:hover .kb-cart-label { color: var(--kb-accent); }
    [data-theme="dark"] .kb-promo-code { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.25); }
    [data-theme="dark"] .kb-account-icon,
    [data-theme="dark"] .kb-account-greet,
    [data-theme="dark"] .kb-account-action { color: var(--kb-primary-font); }
</style>