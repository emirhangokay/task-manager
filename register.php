<?php
/**
 * register.php
 * ============================================================
 * Kullanıcı kayıt sayfası.
 * AJAX ile /api/auth.php'ye POST atar.
 * Kayıt başarılıysa otomatik giriş yapılır ve ana sayfaya yönlendirilir.
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

    <!-- Logo -->
    <div class="auth-card__logo">
      <div class="auth-card__logo-icon">✅</div>
      <div class="auth-card__logo-text">Görev Yönetim</div>
    </div>

    <h1 class="auth-card__title">Hesap Oluştur</h1>
    <p class="auth-card__subtitle">Ücretsiz hesap oluşturun, hemen başlayın.</p>

    <!-- Kayıt Formu -->
    <form id="registerForm" novalidate>
      <div class="form-group">
        <label class="form-label" for="username">Kullanıcı Adı</label>
        <input
          type="text"
          id="username"
          name="username"
          class="form-control"
          placeholder="kullaniciadi"
          autocomplete="username"
          minlength="3"
          maxlength="50"
          required
        />
        <div class="form-error" id="usernameError"></div>
      </div>

      <div class="form-group">
        <label class="form-label" for="email">E-posta Adresi</label>
        <input
          type="email"
          id="email"
          name="email"
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
          type="password"
          id="password"
          name="password"
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
          type="password"
          id="passwordConfirm"
          name="password_confirm"
          class="form-control"
          placeholder="Şifreyi tekrar girin"
          autocomplete="new-password"
          required
        />
        <div class="form-error" id="passwordConfirmError"></div>
      </div>

      <div class="form-error" id="generalError" style="margin-bottom:.5rem;font-size:.9rem;"></div>

      <button type="submit" class="btn btn--primary" id="registerBtn">
        <span id="registerBtnText">Kayıt Ol</span>
        <span id="registerSpinner" class="spinner hidden"></span>
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

  // Hataları temizle
  ['usernameError','emailError','passwordError','passwordConfirmError','generalError'].forEach(id => {
    const el = document.getElementById(id);
    el.textContent = '';
    el.classList.remove('show');
  });

  // Frontend doğrulama
  let valid = true;

  if (!username || username.length < 3) {
    showFieldError('usernameError', 'Kullanıcı adı en az 3 karakter olmalıdır.');
    valid = false;
  } else if (username.length > 50) {
    showFieldError('usernameError', 'Kullanıcı adı en fazla 50 karakter olabilir.');
    valid = false;
  }

  if (!email || !isValidEmail(email)) {
    showFieldError('emailError', 'Geçerli bir e-posta adresi girin.');
    valid = false;
  }

  if (!password || password.length < 6) {
    showFieldError('passwordError', 'Şifre en az 6 karakter olmalıdır.');
    valid = false;
  }

  if (password !== confirm) {
    showFieldError('passwordConfirmError', 'Şifreler eşleşmiyor.');
    valid = false;
  }

  if (!valid) return;

  setLoading(true);

  try {
    const res = await fetch(BASE_URL + '/api/auth.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'register', username, email, password }),
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
  document.getElementById('registerBtn').disabled = on;
  document.getElementById('registerBtnText').textContent = on ? 'Kayıt yapılıyor…' : 'Kayıt Ol';
  document.getElementById('registerSpinner').classList.toggle('hidden', !on);
}

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}
</script>

</body>
</html>
