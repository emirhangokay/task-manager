<?php
/**
 * register.php
 * ============================================================
 * Kullanıcı kayıt sayfası — modern SaaS tasarımı
 * ============================================================
 */

require_once __DIR__ . '/includes/auth.php';

if (currentUserId()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$pageTitle = 'Kayıt Ol';
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

    <h1 class="auth-card__title">Hesap Oluştur</h1>
    <p class="auth-card__subtitle">Ücretsiz başlayın — kredi kartı gerekmez.</p>

    <form id="registerForm" novalidate>

      <div class="form-group">
        <label class="form-label" for="username">Kullanıcı Adı</label>
        <input
          type="text" id="username" name="username"
          class="form-control"
          placeholder="kullaniciadi"
          autocomplete="username"
          minlength="3" maxlength="50"
          required
        />
        <div class="form-error" id="usernameError"></div>
      </div>

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

      <div class="form-group">
        <label class="form-label" for="password">Şifre</label>
        <input
          type="password" id="password" name="password"
          class="form-control"
          placeholder="En az 6 karakter"
          autocomplete="new-password"
          minlength="6"
          required
        />
        <div class="form-error" id="passwordError"></div>
      </div>

      <div class="form-group">
        <label class="form-label" for="passwordConfirm">Şifre Tekrar</label>
        <input
          type="password" id="passwordConfirm"
          class="form-control"
          placeholder="Şifreyi tekrar girin"
          autocomplete="new-password"
          required
        />
        <div class="form-error" id="passwordConfirmError"></div>
      </div>

      <div class="form-error" id="generalError" style="margin-bottom:12px"></div>

      <button type="submit" class="btn btn--primary btn--full btn--lg" id="registerBtn">
        <span id="registerBtnText">Ücretsiz Kaydol</span>
        <span id="registerSpinner" class="spinner spinner--sm hidden" style="border-top-color:white;border-color:rgba(255,255,255,0.3)"></span>
      </button>

    </form>

    <div class="auth-card__footer">
      Zaten hesabınız var mı?
      <a href="<?= BASE_URL ?>/login.php">Giriş Yapın</a>
    </div>

  </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';

document.getElementById('registerForm').addEventListener('submit', async function(e) {
  e.preventDefault();

  const username = document.getElementById('username').value.trim();
  const email    = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  const confirm  = document.getElementById('passwordConfirm').value;

  ['usernameError','emailError','passwordError','passwordConfirmError','generalError'].forEach(id => {
    const el = document.getElementById(id); el.textContent = ''; el.classList.remove('show');
  });

  let valid = true;
  if (!username || username.length < 3)  { showErr('usernameError', 'En az 3 karakter olmalıdır.'); valid = false; }
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showErr('emailError', 'Geçerli bir e-posta girin.'); valid = false; }
  if (!password || password.length < 6)  { showErr('passwordError', 'Şifre en az 6 karakter olmalıdır.'); valid = false; }
  if (password !== confirm)              { showErr('passwordConfirmError', 'Şifreler eşleşmiyor.'); valid = false; }
  if (!valid) return;

  setLoading(true);
  try {
    const res  = await fetch(BASE_URL + '/api/auth.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'register', username, email, password }),
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
  document.getElementById('registerBtn').disabled = on;
  document.getElementById('registerBtnText').textContent = on ? 'Kaydediliyor…' : 'Ücretsiz Kaydol';
  document.getElementById('registerSpinner').classList.toggle('hidden', !on);
}
</script>

</body>
</html>
