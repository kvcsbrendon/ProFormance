<!DOCTYPE html>
<html lang="en">
<head>
    <script src="{{ asset('js/theme.js') }}"></script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'ProFormance' }}</title>

    {{-- Global: loads all partials via @import --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    {{-- Global widgets (always needed) --}}
    <link rel="stylesheet" href="{{ asset('css/chatbot.css') }}">
    <link rel="stylesheet" href="{{ asset('css/notification.css') }}">

    {{-- Google auth styling (only on auth pages) --}}
    @if(request()->routeIs('login', 'register', 'auth.*'))
        <link rel="stylesheet" href="{{ asset('css/google.css') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('css/product-features.css') }}">
    <link rel="stylesheet" href="{{ asset('css/faq.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contact.css') }}">

    {{-- Page-specific CSS from child blades --}}
    @yield('styles')
    
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">
    

    @yield('styles')
</head>

<body class="kb-body">

    @include('layouts.partials.header')
    @if (session('success'))
        <div class="kb-account-alert kb-account-alert-success">
            <i class="bi bi-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="kb-account-alert kb-account-alert-error">
            <i class="bi bi-exclamation-circle"></i>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
        </div>
    @endif
    @if (session('error'))
        <div class="kb-cart-alert kb-cart-alert-error">
            <i class="bi bi-exclamation-circle"></i>
            {{ session('error') }}
        </div>
    @endif
    @include('layouts.partials.sidebar-all')


    <main>
        @yield('content')
    </main>
    @include('layouts.partials.cookie')
    <a href="#" id="backToTopBtn" aria-label="Back to Top" title="Go to top">
        <i class="bi bi-chevron-up"></i>
    </a>
    <div class="kb-zoom-overlay" id="zoomOverlay">
    	<button class="kb-zoom-close" id="zoomClose">&times;</button>
    	<img id="zoomImage" src="" alt="Zoomed product image">
	</div>
    @include('layouts.partials.footer')
    @include('layouts.partials.svg-sprite')
    @include('layouts.partials.wishlist-picker')
    @include('layouts.partials.chatbot')

    {{-- ── Global scripts (every page) ── --}}
   
    <script src="{{ asset('js/accordion.js') }}"></script>
    <script src="{{ asset('js/backtotop.js') }}"></script>
    <script src="{{ asset('js/zoom.js') }}"></script>
    <script src="{{ asset('js/review.js') }}"></script>
    <script src="{{ asset('js/notifications.js') }}"></script>
    <script src="{{ asset('js/navigation.js') }}"></script>
    <script src="{{ asset('js/cart.js') }}"></script>
    <script src="{{ asset('js/cookie-banner.js') }}"></script>
    <script src="{{ asset('js/wishlist.js') }}"></script>
    <script src="{{ asset('js/wishlist-picker.js') }}"></script>
    <script src="{{ asset('js/card-validation.js') }}"></script>
    <script src="{{ asset('js/carousel.js') }}"></script>
    <script src="{{ asset('js/checkout.js') }}"></script>
    <script src="{{ asset('js/password-security.js') }}"></script>
    <script src="{{ asset('js/product-search.js') }}"></script>
    <script src="{{ asset('js/admin-sort.js') }}"></script>
    <script src="{{ asset('js/chatbot.js') }}"></script>
    <script src="{{ asset('js/bulk-pricing.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>

    {{-- ── Page-specific scripts injected by child views ── --}}
    @yield('scripts')
    @stack('scripts')
</body>
</html>