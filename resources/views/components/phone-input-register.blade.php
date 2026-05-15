@props([
    'codeName' => 'country_phone_code',
    'numberName' => 'phone_number',
    'codeValue' => old('country_phone_code', '44'),
    'numberValue' => old('phone_number', ''),
    'required' => true,
    'label' => 'Phone Number'
])

<div class="form-group">
    <label for="{{ $numberName }}">{{ $label }}</label>
    <div class="phone-input-wrapper">
        <input type="hidden" name="{{ $codeName }}" id="{{ $codeName }}" value="{{ $codeValue }}">
        <input type="hidden" name="{{ $numberName }}" id="{{ $numberName }}_full" value="">
        
        <input 
            type="tel" 
            id="{{ $numberName }}" 
            name="{{ $numberName }}"
            class="form-input" 
            style="width: 100%;"
            placeholder="7911 123456"
            value="{{ $numberValue }}"
            {{ $required ? 'required' : '' }}
            {{ $attributes }}
        >
    </div>
    @error($codeName)
        <div class="form-error">{{ $message }}</div>
    @enderror
    @error($numberName)
        <div class="form-error">{{ $message }}</div>
    @enderror
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.querySelector('#{{ $numberName }}');
    const countryCodeInput = document.querySelector('#{{ $codeName }}');
    const phoneFullInput = document.querySelector('#{{ $numberName }}_full');
    
    if (!phoneInput) return;
    
    // Detect country
    function detectCountry() {
        return new Promise((resolve) => {
            Promise.any([
                fetch('https://ipapi.co/json/').then(res => res.json()).then(data => data.country_code.toLowerCase()),
                fetch('https://ipinfo.io/json?token=2620d0b0460b0f').then(res => res.json()).then(data => data.country.toLowerCase()),
                fetch('http://ip-api.com/json/').then(res => res.json()).then(data => data.countryCode.toLowerCase())
            ])
            .then(resolve)
            .catch(() => resolve('gb'));
        });
    }
    
    detectCountry().then(detectedCountry => {
        // Initialize
        const iti = window.intlTelInput(phoneInput, {
            preferredCountries: ['gb'],
            initialCountry: detectedCountry,
            separateDialCode: true,
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
        });
        
        // Set initial number
        let initialCode = '{{ $codeValue }}';
        let initialNumber = '{{ $numberValue }}';
        if (initialNumber && initialCode) {
            iti.setNumber('+' + initialCode + initialNumber.replace(/^0+/, ''));
        }
        
        // Force dropdown to always start at top (UK)
        const originalOpen = iti.open;
        iti.open = function() {
            originalOpen.call(this);
            setTimeout(() => {
                const dropdown = document.querySelector('.iti__country-list');
                if (dropdown) {
                    dropdown.scrollTop = 0;
                }
            }, 10);
        };
        
        // Or attach to click event
        document.addEventListener('click', function(e) {
            if (e.target.closest('.iti__selected-flag')) {
                setTimeout(() => {
                    const dropdown = document.querySelector('.iti__country-list');
                    if (dropdown) {
                        dropdown.scrollTop = 0;
                    }
                }, 10);
            }
        });
        
        // Rest of your event listeners...
        phoneInput.addEventListener('countrychange', function() {
            countryCodeInput.value = iti.getSelectedCountryData().dialCode;
        });
        
        phoneInput.addEventListener('blur', function() {
            if (phoneInput.value.trim() !== '') {
                const countryData = iti.getSelectedCountryData();
                countryCodeInput.value = countryData.dialCode;
                
                const nationalNumber = iti.getNumber(intlTelInputUtils ? 
                    intlTelInputUtils.numberFormat.NATIONAL : 0);
                phoneInput.value = nationalNumber.replace(/[^0-9]/g, '');
                phoneFullInput.value = iti.getNumber();
            }
        });
        
        // Form submit
        const form = phoneInput.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (phoneInput.value.trim() !== '' && !iti.isValidNumber()) {
                    e.preventDefault();
                    alert('Please enter a valid phone number');
                    phoneInput.focus();
                    return false;
                }
            });
        }
    });
});

</script>

<style>
/* Minimal overrides */
.iti {
    width: 100%;
}

.iti__tel-input {
    padding-left: 52px !important;
}

/* Fix for container overflow */
.form-group,
.kb-form-group {
    overflow: visible !important;
    position: relative;
}

/* Fix dropdown positioning */
.iti__country-list {
    z-index: 9999 !important;
}
</style>