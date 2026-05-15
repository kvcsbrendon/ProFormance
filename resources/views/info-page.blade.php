@extends('layouts.app')
@section('title', 'FAQ')

@section('content')
<main>
    <section class="info-section">
        <div class="info-container">
            
            <h2 class="info-heading">{{ $title }}</h2>
            @if(isset($intro))
                <p class="info-intro">{{ $intro }}</p>
            @endif

            <div class="search-wrapper">
                <input type="text" id="infoSearch" class="info-search-input" placeholder="Search this page...">
            </div>

            <div class="info-accordion" id="infoAccordionContainer">
                @foreach($items as $index => $item)
                    <div class="info-accordion-item">
                        <button id="info-btn-{{ $index }}" aria-expanded="false" class="info-accordion-button">
                            <span class="info-accordion-title">{{ $item['q'] }}</span>
                            <span class="info-accordion-icon" aria-hidden="true">+</span>
                        </button>
                        
                        <div class="info-accordion-content">
                            <p>{{ $item['a'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div id="noResultsMsg" class="info-no-results">
                No matching information found.
            </div>

            <div class="info-contact-prompt">
                <p>Hey! If you have any other questions or need further help, we'd love to hear from you.</p>
                <a href="{{ url('/contact') }}" class="btn-contact">Contact Us</a>
            </div>

        </div>
    </section>
</main>
@endsection