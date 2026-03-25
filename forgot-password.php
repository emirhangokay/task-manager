<?php
/**
 * forgot-password.php
 * Şifre sıfırlama isteği sayfası
 */

require_once __DIR__ . '/includes/auth.php';

if (currentUserId()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$pageTitle = 'Şifremi Unuttum';
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

    <div id="formView">
      <h1 class="auth-card__title">Şifremi Unuttum</h1>
      <p class="auth-card__subtitle">E-posta adresinizi girin, sıfırlama bağlantısı gönderelim.</p>

      <form id="forgotForm" novalidate>
        <div class="form-group">
          <label class="form-label" for="email">E-posta Adresi</label>
          <input
            type="email" id="email" name="email"
            class="form-control"
            placeholder="kullanici@ornek.com"
            autocomplete="email"
            required
          />
          <div class="form-error" id="emailError"></div>
        </div>

        <div class="form-error" id="generalError" style="margin-bottom:12px"></div>

        <button type="submit" class="btn btn--primary btn--full btn--lg" id="forgotBtn">
          <span id="forgotBtnText">Bağlantı Gönder</span>
          <span id="forgotSpinner" class="spinner spinner--sm hidden" style="border-top-color:white;border-color:rgba(255,255,255,0.3)"></span>
        </button>
      </form>
    </div>

    <div id="successView" class="hidden" style="text-align:center;padding:16px 0">
      <div style="width:56px;height:56px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
      </div>
      <h2 style="font-size:1.125rem;font-weight:600;color:var(--text-primary);margin-bottom:8px">E-posta Gönderildi</h2>
      <p style="color:var(--text-secondary);font-size:.9375rem;margin:0">
        Eğer bu e-posta kayıtlıysa, sıfırlama bağlantısı gönderildi.<br>
        Spam klasörünüzü de kontrol edin.
      </p>
    </div>

    <div class="auth-card__footer">
      <a href="<?= BASE_URL ?>/login.php">Giriş sayfasına dön</a>
    </div>

  </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';

document.getElementById('forgotForm').addEventListener('submit', async function(e) {
  e.preventDefault();

  const email = document.getElementById('email').value.trim();
  ['emailError','generalError'].forEach(id => {
    const el = document.getElementById(id); el.textContent = ''; el.classList.remove('show');
  });

  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    const el = document.getElementById('emailError');
    el.textContent = 'Geçerli bir e-posta girin.'; el.classList.add('show');
    return;
  }

  setLoading(true);
  try {
    const res  = await fetch(BASE_URL + '/api/auth.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'forgot_password', email }),
    });
    const data = await res.json();
    if (data.success) {
      document.getElementById('formView').classList.add('hidden');
      document.getElementById('successView').classList.remove('hidden');
    } else {
      const el = document.getElementById('generalError');
      el.textContent = data.message; el.classList.add('show');
    }
  } catch {
    const el = document.getElementById('generalError');
    el.textContent = 'Sunucu bağlantısı kurulamadı.'; el.classList.add('show');
  } finally {
    setLoading(false);
  }
});

function setLoading(on) {
  document.getElementById('forgotBtn').disabled = on;
  document.getElementById('forgotBtnText').textContent = on ? 'Gönderiliyor…' : 'Bağlantı Gönder';
  document.getElementById('forgotSpinner').classList.toggle('hidden', !on);
}
</script>

</body>
</html>
