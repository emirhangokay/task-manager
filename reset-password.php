<?php
/**
 * reset-password.php
 * Şifre sıfırlama sayfası — ?token=xxx&uid=yyy
 */

require_once __DIR__ . '/includes/auth.php';

if (currentUserId()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$token = $_GET['token'] ?? '';
$uid   = (int)($_GET['uid'] ?? 0);

if (!$token || !$uid) {
    header('Location: ' . BASE_URL . '/forgot-password.php');
    exit;
}

$pageTitle = 'Yeni Şifre Belirle';
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

    <h1 class="auth-card__title">Yeni Şifre Belirle</h1>
    <p class="auth-card__subtitle">Hesabınız için yeni bir şifre oluşturun.</p>

    <form id="resetForm" novalidate>
      <div class="form-group">
        <label class="form-label" for="password">Yeni Şifre</label>
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

      <button type="submit" class="btn btn--primary btn--full btn--lg" id="resetBtn">
        <span id="resetBtnText">Şifremi Güncelle</span>
        <span id="resetSpinner" class="spinner spinner--sm hidden" style="border-top-color:white;border-color:rgba(255,255,255,0.3)"></span>
      </button>
    </form>

    <div class="auth-card__footer">
      <a href="<?= BASE_URL ?>/login.php">Giriş sayfasına dön</a>
    </div>

  </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';

document.getElementById('resetForm').addEventListener('submit', async function(e) {
  e.preventDefault();

  const password = document.getElementById('password').value;
  const confirm  = document.getElementById('passwordConfirm').value;

  ['passwordError','passwordConfirmError','generalError'].forEach(id => {
    const el = document.getElementById(id); el.textContent = ''; el.classList.remove('show');
  });

  let valid = true;
  if (!password || password.length < 6)  { showErr('passwordError', 'Şifre en az 6 karakter olmalıdır.'); valid = false; }
  if (password !== confirm)              { showErr('passwordConfirmError', 'Şifreler eşleşmiyor.'); valid = false; }
  if (!valid) return;

  setLoading(true);
  try {
    const res  = await fetch(BASE_URL + '/api/auth.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'reset_password',
        user_id: <?= $uid ?>,
        token: <?= json_encode($token) ?>,
        password,
      }),
    });
    const data = await res.json();
    if (data.success) {
      window.location.href = BASE_URL + '/login.php?reset=1';
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
  document.getElementById('resetBtn').disabled = on;
  document.getElementById('resetBtnText').textContent = on ? 'Güncelleniyor…' : 'Şifremi Güncelle';
  document.getElementById('resetSpinner').classList.toggle('hidden', !on);
}
</script>

</body>
</html>
