<?php
/**
 * verify-email.php
 * E-posta doğrulama sayfası — 6 haneli kod girişi
 */

require_once __DIR__ . '/includes/auth.php';

if (!currentUserId()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$pageTitle = 'E-posta Doğrulama';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
  <div class="auth-card">

    <div class="auth-card__brand">
      <div class="auth-card__brand-icon">
        <svg width="22" height="22" viewBox="0 0 20 20" fill="none">
          <path d="M3 10l5 5L17 6" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <span class="auth-card__brand-name">TaskFlow</span>
    </div>

    <div style="text-align:center;margin-bottom:24px">
      <div style="width:56px;height:56px;background:rgba(99,102,241,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
          <polyline points="22,6 12,13 2,6"/>
        </svg>
      </div>
      <h1 class="auth-card__title" style="margin-bottom:8px">E-postanızı Doğrulayın</h1>
      <p class="auth-card__subtitle">E-posta adresinize gönderilen 6 haneli kodu girin.</p>
    </div>

    <form id="verifyForm" novalidate>

      <div class="otp-group" id="otpGroup">
        <input class="otp-input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code" autofocus />
        <input class="otp-input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" />
        <input class="otp-input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" />
        <input class="otp-input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" />
        <input class="otp-input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" />
        <input class="otp-input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" />
      </div>

      <div class="form-error" id="generalError" style="margin-bottom:12px;text-align:center"></div>

      <button type="submit" class="btn btn--primary btn--full btn--lg" id="verifyBtn">
        <span id="verifyBtnText">Doğrula</span>
        <span id="verifySpinner" class="spinner spinner--sm hidden" style="border-top-color:white;border-color:rgba(255,255,255,0.3)"></span>
      </button>

    </form>

    <div style="text-align:center;margin-top:20px;font-size:.875rem;color:var(--text-secondary)">
      Kod gelmedi mi?
      <button id="resendBtn" class="link-btn">Tekrar gönder</button>
      <span id="resendCountdown" class="hidden" style="color:var(--text-muted)"></span>
    </div>

    <div class="auth-card__footer">
      <a href="<?= BASE_URL ?>/index.php">Ana sayfaya dön</a>
    </div>

  </div>
</div>

<style>
.otp-group {
  display: flex;
  gap: 10px;
  justify-content: center;
  margin-bottom: 24px;
}
.otp-input {
  width: 48px;
  height: 56px;
  text-align: center;
  font-size: 1.5rem;
  font-weight: 700;
  border: 2px solid var(--border);
  border-radius: 10px;
  background: var(--surface);
  color: var(--text-primary);
  transition: border-color .15s, box-shadow .15s;
  outline: none;
}
.otp-input:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(99,102,241,.18);
}
.otp-input.filled {
  border-color: var(--accent);
  background: rgba(99,102,241,.06);
}
.link-btn {
  background: none;
  border: none;
  color: var(--accent);
  font-size: inherit;
  cursor: pointer;
  padding: 0;
  font-weight: 500;
  text-decoration: underline;
}
.link-btn:disabled { opacity: .5; cursor: not-allowed; }
</style>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const inputs   = Array.from(document.querySelectorAll('.otp-input'));

// OTP input otomatik geçiş
inputs.forEach((inp, i) => {
  inp.addEventListener('input', () => {
    inp.value = inp.value.replace(/\D/g, '').slice(-1);
    inp.classList.toggle('filled', !!inp.value);
    if (inp.value && i < inputs.length - 1) inputs[i + 1].focus();
  });
  inp.addEventListener('keydown', e => {
    if (e.key === 'Backspace' && !inp.value && i > 0) inputs[i - 1].focus();
  });
  inp.addEventListener('paste', e => {
    e.preventDefault();
    const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
    text.split('').forEach((ch, j) => {
      if (inputs[j]) { inputs[j].value = ch; inputs[j].classList.add('filled'); }
    });
    const nextEmpty = inputs.findIndex(el => !el.value);
    (inputs[nextEmpty] || inputs[inputs.length - 1]).focus();
  });
});

document.getElementById('verifyForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const code = inputs.map(el => el.value).join('');
  if (code.length < 6) {
    showErr('Lütfen 6 haneli kodu eksiksiz girin.');
    return;
  }
  setLoading(true);
  try {
    const res  = await fetch(BASE_URL + '/api/auth.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'verify_email', code }),
    });
    const data = await res.json();
    if (data.success) {
      window.location.href = BASE_URL + '/index.php?verified=1';
    } else {
      showErr(data.message);
      inputs.forEach(el => { el.value = ''; el.classList.remove('filled'); });
      inputs[0].focus();
    }
  } catch {
    showErr('Sunucu bağlantısı kurulamadı.');
  } finally {
    setLoading(false);
  }
});

// Yeniden gönder
let resendTimer = null;
document.getElementById('resendBtn').addEventListener('click', async function() {
  this.disabled = true;
  try {
    const res  = await fetch(BASE_URL + '/api/auth.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'resend_code' }),
    });
    const data = await res.json();
    showErr(data.message, data.success ? 'success' : 'error');
  } catch {
    showErr('Sunucu bağlantısı kurulamadı.');
  }
  // 60s bekleme
  let secs = 60;
  const countdown = document.getElementById('resendCountdown');
  countdown.classList.remove('hidden');
  countdown.textContent = ` (${secs}s)`;
  resendTimer = setInterval(() => {
    secs--;
    countdown.textContent = ` (${secs}s)`;
    if (secs <= 0) {
      clearInterval(resendTimer);
      countdown.classList.add('hidden');
      document.getElementById('resendBtn').disabled = false;
    }
  }, 1000);
});

function showErr(msg, type = 'error') {
  const el = document.getElementById('generalError');
  el.textContent = msg;
  el.classList.add('show');
  el.style.color = type === 'success' ? 'var(--success)' : 'var(--danger)';
}

function setLoading(on) {
  document.getElementById('verifyBtn').disabled = on;
  document.getElementById('verifyBtnText').textContent = on ? 'Doğrulanıyor…' : 'Doğrula';
  document.getElementById('verifySpinner').classList.toggle('hidden', !on);
}
</script>

</body>
</html>
