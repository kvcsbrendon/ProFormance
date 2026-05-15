@extends('layouts.app')
@section('title', 'Contact Us')

@section('content')
<div class="kb-contact-page">
    <div class="kb-contact-hero">
        <div class="kb-contact-hero-inner">
            <h1 class="kb-contact-hero-title">Get in Touch</h1>
            <p class="kb-contact-hero-sub">Got a question, problem, or just want to say hi? We'd love to hear from you.</p>
        </div>
    </div>

    <div class="kb-contact-body">
        <div class="kb-contact-grid">
            <aside class="kb-contact-sidebar">
                <div class="kb-contact-info-card">
                    <div class="kb-contact-info-icon"><i class="bi bi-chat-dots-fill"></i></div>
                    <h3>Live Chat</h3>
                    <p>Chat with our team via the chatbot in the bottom-right corner.</p>
                </div>
                <div class="kb-contact-info-card">
                    <div class="kb-contact-info-icon"><i class="bi bi-envelope-fill"></i></div>
                    <h3>Email Us</h3>
                    <p>support@proformance.co.uk</p>
                    <p class="kb-contact-info-hint">We reply within 24 hours</p>
                </div>
                <div class="kb-contact-info-card">
                    <div class="kb-contact-info-icon"><i class="bi bi-clock-fill"></i></div>
                    <h3>Support Hours</h3>
                    <p>Mon – Fri: 9am – 6pm</p>
                    <p>Sat: 10am – 4pm</p>
                    <p class="kb-contact-info-hint">UK time (GMT/BST)</p>
                </div>
                <div class="kb-contact-info-card">
                    <div class="kb-contact-info-icon"><i class="bi bi-geo-alt-fill"></i></div>
                    <h3>Our Office</h3>
                    <p>ProFormance Ltd</p>
                    <p>London, United Kingdom</p>
                </div>
            </aside>

            <div class="kb-contact-form-card">
                <h2 class="kb-contact-form-title">Send us a message</h2>
                <p class="kb-contact-form-sub">Fill in the form below and we'll get back to you as soon as possible.</p>


                <form method="POST" action="{{ route('contact.store') }}" class="kb-contact-form">
                    @csrf
                    <div class="kb-contact-form-row">
                        <div class="kb-contact-form-group">
                            <label for="first_name">First name <span class="kb-required">*</span></label>
                            <input type="text" id="first_name" name="first_name" class="kb-contact-input" value="{{ old('first_name') }}" required placeholder="John">
                        </div>
                        <div class="kb-contact-form-group">
                            <label for="last_name">Last name <span class="kb-required">*</span></label>
                            <input type="text" id="last_name" name="last_name" class="kb-contact-input" value="{{ old('last_name') }}" required placeholder="Doe">
                        </div>
                    </div>

                    <div class="kb-contact-form-row">
                        <div class="kb-contact-form-group">
                            <label for="email_address">Email <span class="kb-required">*</span></label>
                            <input type="email" id="email_address" name="email_address" class="kb-contact-input" value="{{ old('email_address') }}" required placeholder="john@example.com">
                        </div>
                        <div class="kb-contact-form-group">
                            <label for="subject_select">Subject <span class="kb-required">*</span></label>
                            <select id="subject_select" name="subject_select" class="kb-contact-input kb-contact-select" required>
                                <option value="">Choose a topic…</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject }}" @selected(old('subject_select') === $subject)>{{ $subject }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="kb-contact-form-row">
                        <div class="kb-contact-form-group">
                            <label for="product_search">Product <span class="kb-optional">(optional)</span></label>
                            <div style="position: relative;">
                                <input type="text" id="product_search" name="product_search" class="kb-contact-input" value="{{ old('product_search') }}" placeholder="Search by name or SKU" autocomplete="off">
                                <input type="hidden" id="product_id" name="product_id" value="{{ old('product_id') }}">
                                <input type="hidden" id="variant_id" name="variant_id" value="{{ old('variant_id') }}">
                                <div id="product_suggestions" class="list-group position-absolute w-100" style="z-index: 1000; display:none;"></div>
                            </div>
                        </div>
                        <div class="kb-contact-form-group">
                            <label for="order_id">Order number <span class="kb-optional">(optional)</span></label>
                            <input type="number" id="order_id" name="order_id" class="kb-contact-input" value="{{ old('order_id') }}" placeholder="e.g. 12345">
                        </div>
                    </div>

                    <div class="kb-contact-form-group">
                        <label for="message_description">Message <span class="kb-required">*</span></label>
                        <textarea id="message_description" name="message_description" class="kb-contact-input kb-contact-textarea" rows="6" required placeholder="Tell us how we can help…">{{ old('message_description') }}</textarea>
                    </div>

                    <button type="submit" class="kb-contact-submit-btn">
                        <i class="bi bi-send-fill"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection