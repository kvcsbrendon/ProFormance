document.addEventListener('DOMContentLoaded', () => {
    const passwordInput = document.getElementById('password');
    const confirmInput  = document.getElementById('password_confirmation');
    const strengthFill  = document.getElementById('pw-strength-fill');
    const strengthLabel = document.getElementById('pw-strength-label');
    const breachResult  = document.getElementById('pw-breach-result');

    if (!passwordInput) return;

    const csrf = typeof csrfToken !== 'undefined'
        ? csrfToken
        : document.querySelector('meta[name="csrf-token"]')?.content || '';

    const breachUrl = typeof breachCheckUrl !== 'undefined'
        ? breachCheckUrl
        : '/password/check-breach';

    const requirements = {
        'req-length':  pw => pw.length >= 8,
        'req-upper':   pw => /[A-Z]/.test(pw),
        'req-lower':   pw => /[a-z]/.test(pw),
        'req-number':  pw => /[0-9]/.test(pw),
        'req-special': pw => /[@$!%*?&#]/.test(pw),
    };

    function updateRequirements(pw) {
        for (const [id, test] of Object.entries(requirements)) {
            const el   = document.getElementById(id);
            if (!el) continue;
            const icon = el.querySelector('i');
            const met  = test(pw);

            el.classList.toggle('met', met);
            el.classList.toggle('unmet', !met);
            if (icon) icon.className = met ? 'bi bi-check-circle-fill' : 'bi bi-x-circle';
        }
    }

    function updateMatch() {
        const el = document.getElementById('req-match');
        if (!el || !confirmInput) return;

        const icon = el.querySelector('i');
        const pw   = passwordInput.value;
        const conf = confirmInput.value;

        if (conf.length === 0) {
            el.classList.remove('met', 'unmet');
            if (icon) icon.className = 'bi bi-x-circle';
            return;
        }

        const match = pw === conf;
        el.classList.toggle('met', match);
        el.classList.toggle('unmet', !match);
        if (icon) icon.className = match ? 'bi bi-check-circle-fill' : 'bi bi-x-circle';
    }

    function updateStrength(pw) {
        if (!strengthFill || !strengthLabel) return;

        let score = 0;
        if (pw.length >= 8)  score++;
        if (pw.length >= 12) score++;
        if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
        if (/[0-9]/.test(pw)) score++;
        if (/[@$!%*?&#]/.test(pw)) score++;
        if (pw.length >= 16) score++;
        score = Math.min(score, 5);

        const levels = [
            { label: '',            color: 'transparent', width: '0%' },
            { label: 'Very Weak',   color: '#dc2626',     width: '20%' },
            { label: 'Weak',        color: '#ef4444',     width: '40%' },
            { label: 'Fair',        color: '#f59e0b',     width: '60%' },
            { label: 'Strong',      color: '#16a34a',     width: '80%' },
            { label: 'Very Strong', color: '#065f46',     width: '100%' },
        ];

        const level = levels[score];
        strengthFill.style.width           = level.width;
        strengthFill.style.backgroundColor = level.color;
        strengthLabel.textContent          = pw.length > 0 ? level.label : '';
        strengthLabel.style.color          = level.color;
    }

    let breachTimeout;
    function checkBreach() {
        const pw = passwordInput.value;
        if (!breachResult || pw.length < 8) {
            if (breachResult) breachResult.style.display = 'none';
            return;
        }

        breachResult.style.display = 'flex';
        breachResult.className = 'kb-pw-breach-inline checking';
        breachResult.innerHTML = '<i class="bi bi-arrow-repeat kb-spin"></i> <span>Checking against known data breaches…</span>';

        fetch(breachUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ password: pw }),
        })
        .then(r => r.json())
        .then(data => {
        const countFormatted = Number(data.count || 0).toLocaleString();

        if (data.breached) {
            breachResult.className = 'kb-pw-breach-inline breached';
            breachResult.innerHTML =
                '<i class="bi bi-shield-exclamation"></i>' +
                '<div class="kb-pw-breach-text">' +
                    '<span><strong>Security warning:</strong> This password has been seen in public breaches' +
                    ' (<strong>' + countFormatted + '</strong> times).' +
                    ' Consider choosing a different one.</span>' +

                    '<details class="kb-pw-breach-details">' +
                        '<summary>Why am I seeing this?</summary>' +
                        '<p>' +
                            'We check passwords against the Have I Been Pwned database to help you avoid ' +
                            'using passwords that are already known from previous breaches. ' +
                            'The check is done securely and your actual password is never shared. ' +
                            '<a href="https://haveibeenpwned.com/Passwords" target="_blank" rel="noopener">Learn more</a>.' +
                        '</p>' +
                    '</details>' +
                '</div>';
        } else {
            breachResult.className = 'kb-pw-breach-inline safe';
            breachResult.innerHTML =
                '<i class="bi bi-shield-check"></i>' +
                '<span><strong>Looks good:</strong> This password has not been found in known breaches.</span>';
        }

        breachResult.style.display = ''; // make sure it's visible
    })
    .catch(() => { breachResult.style.display = 'none'; });
        }

    // Bind events
    passwordInput.addEventListener('input', function () {
        updateRequirements(this.value);
        updateStrength(this.value);
        updateMatch();
    });

    confirmInput?.addEventListener('input', updateMatch);

    passwordInput.addEventListener('blur', () => {
        clearTimeout(breachTimeout);
        breachTimeout = setTimeout(checkBreach, 300);
    });

    // Initial state
    updateRequirements('');
    updateStrength('');
});

// ── Password visibility toggle (global, used by register + reset) ──
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon  = btn.querySelector('i');
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

document.addEventListener('DOMContentLoaded', () => {
  const pw = document.getElementById('password');
  const first = document.getElementById('first_name');
  const last = document.getElementById('last_name');
  const email = document.getElementById('email');
  const email2 = document.getElementById('email_confirmation');

  const box = document.getElementById('pw-policy-result');
  const submit = document.querySelector('button[type="submit"]');

  if (!pw || !box || !submit) return; // If this triggers, IDs/script loading is wrong

  const norm = (s) => (s || '').toLowerCase().replace(/[^a-z0-9]/g, '');

  function hasCommon(p) {
    const s = (p || '').toLowerCase();
    const bad = ['password','passw0rd','qwerty','asdf','zxcv','admin','letmein','1234','12345','123456','0000','1111'];
    return bad.some(x => s.includes(x));
  }

  function hasRepeat(p) { return /(.)\1{3,}/i.test(p); } // aaaa
  function hasSequence(p) {
    const s = norm(p);
    let run = 1;
    for (let i = 1; i < s.length; i++) {
      if (s.charCodeAt(i) === s.charCodeAt(i-1) + 1) {
        run++;
        if (run >= 4) return true; // 1234 / abcd
      } else run = 1;
    }
    return false;
  }

  function containsUserData(p) {
    const s = norm(p);
    const parts = [
      norm(first?.value),
      norm(last?.value),
      norm(email?.value?.split('@')[0]),
    ].filter(x => x && x.length >= 3);

    return parts.some(x => s.includes(x));
  }

  function setState(ok, msg) {
    box.style.display = msg ? '' : 'none';
    box.className = 'kb-pw-breach-inline ' + (ok ? 'safe' : 'breached');

    box.innerHTML = ok
      ? `<i class="bi bi-shield-check"></i><span>${msg}</span>`
      : `<i class="bi bi-shield-exclamation"></i><span><strong>Password not accepted:</strong> ${msg}</span>`;

    submit.disabled = !ok;
    pw.setCustomValidity(ok ? '' : msg);
  }

  function validatePw() {
    const val = pw.value || '';
    if (!val) { setState(true, ''); return; }

    if (containsUserData(val)) return setState(false, 'Don’t include your name or email.');
    if (hasCommon(val))        return setState(false, 'Too common (e.g. “12345”, “qwerty”, “password”).');
    if (hasSequence(val))      return setState(false, 'Avoid sequences like “1234” or “abcd”.');
    if (hasRepeat(val))        return setState(false, 'Avoid repeated characters like “aaaa” or “1111”.');

    setState(true, 'Looks good so far.');
  }

  function validateEmailMatch() {
    if (!email || !email2) return;
    const a = email.value.trim();
    const b = email2.value.trim();
    if (!b) { email2.setCustomValidity(''); return; }
    email2.setCustomValidity(a === b ? '' : 'Email addresses do not match.');
  }

  pw.addEventListener('input', validatePw);
  first?.addEventListener('input', validatePw);
  last?.addEventListener('input', validatePw);
  email?.addEventListener('input', () => { validatePw(); validateEmailMatch(); });
  email2?.addEventListener('input', validateEmailMatch);

  validatePw();
});
document.addEventListener('DOMContentLoaded', () => {
  const email = document.getElementById('email');
  const email2 = document.getElementById('email_confirmation');
  const box = document.getElementById('email-match-result');

  // DEBUG: if any are missing, you'll know why nothing shows
  if (!email || !email2 || !box) {
    console.log('Email match checker not wired:', { email: !!email, email2: !!email2, box: !!box });
    return;
  }

  function setBox(type, html) {
    box.style.display = html ? '' : 'none';
    box.className = 'kb-pw-breach-inline ' + (type === 'ok' ? 'safe' : 'breached');
    box.innerHTML = html;
  }

  function validateEmails() {
    const a = email.value.trim();
    const b = email2.value.trim();

    // Don’t show anything until user starts typing confirmation
    if (!b) {
      setBox('ok', '');
      email2.setCustomValidity('');
      return;
    }

    if (a !== b) {
      setBox('bad',
        '<i class="bi bi-exclamation-triangle"></i>' +
        '<span><strong>Emails don’t match:</strong> Please make sure both email fields are identical.</span>'
      );
      email2.setCustomValidity('Emails do not match.');
      return;
    }

    setBox('ok',
      '<i class="bi bi-check-circle"></i>' +
      '<span>Emails match.</span>'
    );
    email2.setCustomValidity('');
  }

  email.addEventListener('input', validateEmails);
  email2.addEventListener('input', validateEmails);

  validateEmails();
});