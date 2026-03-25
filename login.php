<?php
/**
 * login.php
 * ============================================================
 * Kullanıcı giriş sayfası — modern SaaS tasarımı
 * ============================================================
 */

require_once __DIR__ . '/includes/auth.php';

if (currentUserId()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$pageTitle = 'Giriş Yap';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
  <div class="auth-card">

    <!-- Brand -->
    <div class="auth-card__brand">
      <div class="auth-card__brand-icon">
        <svg width="22" height="22" viewBox="0 0 20 20" fill="none">
          <path d="M3 10l5 5L17 6" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <span class="auth-card__brand-name">TaskFlow</span>
    </div>

    <h1 class="auth-card__title">Tekrar Hoş Geldiniz</h1>
    <p class="auth-card__subtitle">Devam etmek için hesabınıza giriş yapın.</p>

    <!-- Giriş Formu -->
    <form id="loginForm" novalidate>
      <div class="form-group">
        <label class="form-label" for="login">Kullanıcı Adı veya E-posta</label>
        <input
          type="text" id="login" name="login"
          class="form-control"
          placeholder="kullanici@ornek.com"
          autocomplete="username"
          required
        />
        <div class="form-error" id="loginError"></div>
      </div>

      <div class="form-group">
        <label class="form-label" for="password" style="display:flex;justify-content:space-between">
          Şifre
          <a href="#" style="font-weight:400;font-size:.8125rem;color:var(--accent)">Şifremi Unuttum</a>
        </label>
        <input
          type="password" id="password" name="password"
          class="form-control"
          placeholder="••••••••"
          autocomplete="current-password"
          required
        />
        <div class="form-error" id="passwordError"></div>
      </div>

      <div class="form-error" id="generalError" style="margin-bottom:12px"></div>

      <button type="submit" class="btn btn--primary btn--full btn--lg" id="loginBtn">
        <span id="loginBtnText">Giriş Yap</span>
        <span id="loginSpinner" class="spinner spinner--sm hidden" style="border-top-color:white;border-color:rgba(255,255,255,0.3)"></span>
      </button>
    </form>

    <div class="auth-card__divider">ya da</div>

    <!-- Sosyal login (görsel) -->
    <button class="auth-social-btn" disabled title="Yakında">
      <svg width="18" height="18" viewBox="0 0 18 18"><path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844a4.14 4.14 0 01-1.796 2.716v2.259h2.908c1.702-1.567 2.684-3.875 2.684-6.615z"/><path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 009 18z"/><path fill="#FBBC05" d="M3.964 10.71A5.41 5.41 0 013.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 000 9c0 1.452.348 2.827.957 4.042l3.007-2.332z"/><path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 00.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z"/></svg>
      Google ile Giriş Yap
    </button>

    <div class="auth-card__footer">
      Hesabınız yok mu?
      <a href="<?= BASE_URL ?>/register.php">Ücretsiz Kayıt Olun</a>
    </div>

  </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';

document.getElementById('loginForm').addEventListener('submit', async function(e) {
  e.preventDefault();

  const loginVal = document.getElementById('login').value.trim();
  const passVal  = document.getElementById('password').value;

  ['loginError','passwordError','generalError'].forEach(id => {
    const el = document.getElementById(id);
    el.textContent = ''; el.classList.remove('show');
  });

  let valid = true;
  if (!loginVal) { showErr('loginError', 'Kullanıcı adı veya e-posta zorunludur.'); valid = false; }
  if (!passVal)  { showErr('passwordError', 'Şifre zorunludur.'); valid = false; }
  if (!valid) return;

  setLoading(true);
  try {
    const res  = await fetch(BASE_URL + '/api/auth.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'login', login: loginVal, password: passVal }),
    });
    const data = await res.json();

    if (data.success) {
      window.location.href = BASE_URL + '/index.php';
    } else {
      showErr('generalError', data.message);
    }
  } catch {
    showErr('generalError', 'Sunucu bağlantısı kurulamadı.');
  } finally {
    setLoading(false);
  }
});

function showErr(id, msg) {
  const el = document.getElementById(id);
  if (el) { el.textContent = msg; el.classList.add('show'); }
}

function setLoading(on) {
  document.getElementById('loginBtn').disabled = on;
  document.getElementById('loginBtnText').textContent = on ? 'Giriş yapılıyor…' : 'Giriş Yap';
  document.getElementById('loginSpinner').classList.toggle('hidden', !on);
}
</script>

</body>
</html>
