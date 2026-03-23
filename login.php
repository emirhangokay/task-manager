<?php
/**
 * login.php
 * ============================================================
 * Kullanıcı giriş sayfası.
 * AJAX ile /api/auth.php'ye POST atar.
 * Giriş yapılmışsa ana sayfaya yönlendirir.
 * ============================================================
 */

require_once __DIR__ . '/includes/auth.php';

// Zaten giriş yapmışsa ana sayfaya git
if (currentUserId()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$pageTitle = 'Giriş Yap';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
  <div class="auth-card">

    <!-- Logo -->
    <div class="auth-card__logo">
      <div class="auth-card__logo-icon">✅</div>
      <div class="auth-card__logo-text">Görev Yönetim</div>
    </div>

    <h1 class="auth-card__title">Tekrar Hoş Geldiniz</h1>
    <p class="auth-card__subtitle">Devam etmek için giriş yapın.</p>

    <!-- Giriş Formu -->
    <form id="loginForm" novalidate>
      <div class="form-group">
        <label class="form-label" for="login">Kullanıcı Adı veya E-posta</label>
        <input
          type="text"
          id="login"
          name="login"
          class="form-control"
          placeholder="kullanici@ornek.com"
          autocomplete="username"
          required
        />
        <div class="form-error" id="loginError"></div>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Şifre</label>
        <input
          type="password"
          id="password"
          name="password"
          class="form-control"
          placeholder="••••••••"
          autocomplete="current-password"
          required
        />
        <div class="form-error" id="passwordError"></div>
      </div>

      <div class="form-error" id="generalError" style="margin-bottom:.5rem;font-size:.9rem;"></div>

      <button type="submit" class="btn btn--primary" id="loginBtn">
        <span id="loginBtnText">Giriş Yap</span>
        <span id="loginSpinner" class="spinner hidden"></span>
      </button>
    </form>

    <div class="auth-card__footer">
      Hesabınız yok mu?
      <a href="<?= BASE_URL ?>/register.php">Kayıt Olun</a>
    </div>

  </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
document.getElementById('loginForm').addEventListener('submit', async function(e) {
  e.preventDefault();

  const loginVal = document.getElementById('login').value.trim();
  const passVal  = document.getElementById('password').value;

  // Hata mesajlarını temizle
  ['loginError', 'passwordError', 'generalError'].forEach(id => {
    const el = document.getElementById(id);
    el.textContent = '';
    el.classList.remove('show');
  });

  // Frontend doğrulama
  let valid = true;
  if (!loginVal) {
    showFieldError('loginError', 'Kullanıcı adı veya e-posta zorunludur.');
    valid = false;
  }
  if (!passVal) {
    showFieldError('passwordError', 'Şifre zorunludur.');
    valid = false;
  }
  if (!valid) return;

  // Butonu devre dışı bırak
  setLoading(true);

  try {
    const res = await fetch(BASE_URL + '/api/auth.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'login', login: loginVal, password: passVal }),
    });
    const data = await res.json();

    if (data.success) {
      window.location.href = BASE_URL + '/index.php';
    } else {
      showFieldError('generalError', data.message);
    }
  } catch {
    showFieldError('generalError', 'Sunucu bağlantısı kurulamadı.');
  } finally {
    setLoading(false);
  }
});

function showFieldError(id, msg) {
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
