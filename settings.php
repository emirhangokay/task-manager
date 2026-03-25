<?php
/**
 * settings.php
 * Profil ve uygulama ayarları sayfası
 */

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pageTitle = 'Ayarlar';
require_once __DIR__ . '/includes/header.php';
?>

<div class="settings-page">

  <!-- Settings Sidebar -->
  <nav class="settings-nav" id="settingsNav">
    <button class="settings-nav__item active" data-tab="profile">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Profil
    </button>
    <button class="settings-nav__item" data-tab="preferences">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M4.93 4.93l1.41 1.41M19.07 19.07l-1.41-1.41M4.93 19.07l1.41-1.41M12 2v2M12 20v2M2 12h2M20 12h2"/></svg>
      Tercihler
    </button>
    <button class="settings-nav__item" data-tab="security">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      Güvenlik
    </button>
    <button class="settings-nav__item settings-nav__item--danger" data-tab="danger">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
      Tehlikeli Alan
    </button>
  </nav>

  <!-- Settings Content -->
  <div class="settings-content">

    <!-- Profile Tab -->
    <div class="settings-tab active" id="tab-profile">
      <div class="settings-section">
        <h2 class="settings-section__title">Profil Bilgileri</h2>

        <div class="avatar-picker" id="avatarPicker">
          <div class="avatar-display" id="avatarDisplay">
            <span id="avatarInitial"></span>
          </div>
          <div>
            <p style="font-weight:600;margin:0 0 4px">Avatar Rengi</p>
            <div class="avatar-colors" id="avatarColors">
              <?php
              $colors = ['#6366f1','#8b5cf6','#ec4899','#ef4444','#f97316','#eab308','#22c55e','#06b6d4','#3b82f6','#64748b'];
              foreach ($colors as $c): ?>
                <button class="avatar-color-swatch" style="background:<?= $c ?>" data-color="<?= $c ?>"></button>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <form id="profileForm">
          <div class="settings-grid">
            <div class="form-group">
              <label class="form-label">Kullanıcı Adı</label>
              <input type="text" id="pfUsername" class="form-control" disabled />
              <p class="form-hint">Kullanıcı adı değiştirilemez.</p>
            </div>
            <div class="form-group">
              <label class="form-label" for="pfDisplayName">Görünen Ad</label>
              <input type="text" id="pfDisplayName" class="form-control" placeholder="Görünen adınız (opsiyonel)" maxlength="100" />
            </div>
            <div class="form-group">
              <label class="form-label">E-posta</label>
              <div style="position:relative">
                <input type="text" id="pfEmail" class="form-control" disabled style="padding-right:120px" />
                <span id="verifiedBadge" class="badge badge--success" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);display:none">Doğrulandı</span>
                <a href="<?= BASE_URL ?>/verify-email.php" id="verifyLink" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:.8125rem;color:var(--accent);display:none">Doğrula</a>
              </div>
            </div>
          </div>

          <div class="form-error" id="profileError" style="margin-bottom:12px"></div>

          <button type="submit" class="btn btn--primary" id="profileBtn">
            <span id="profileBtnText">Kaydet</span>
            <span id="profileSpinner" class="spinner spinner--sm hidden"></span>
          </button>
        </form>
      </div>
    </div>

    <!-- Preferences Tab -->
    <div class="settings-tab" id="tab-preferences">
      <div class="settings-section">
        <h2 class="settings-section__title">Uygulama Tercihleri</h2>
        <form id="prefsForm">
          <div class="settings-grid">
            <div class="form-group">
              <label class="form-label" for="pfTheme">Tema</label>
              <select id="pfTheme" class="form-control">
                <option value="light">Açık</option>
                <option value="dark">Koyu</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label" for="pfDefaultView">Varsayılan Görünüm</label>
              <select id="pfDefaultView" class="form-control">
                <option value="list">Liste</option>
                <option value="kanban">Kanban</option>
                <option value="calendar">Takvim</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label" for="pfLanguage">Dil</label>
              <select id="pfLanguage" class="form-control">
                <option value="tr">Türkçe</option>
                <option value="en">English</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="settings-toggle">
              <input type="checkbox" id="pfNotifications" />
              <span class="settings-toggle__track"><span class="settings-toggle__thumb"></span></span>
              <span class="settings-toggle__label">Bildirimler etkin</span>
            </label>
          </div>

          <div class="form-error" id="prefsError" style="margin-bottom:12px"></div>

          <button type="submit" class="btn btn--primary" id="prefsBtn">
            <span id="prefsBtnText">Kaydet</span>
            <span id="prefsSpinner" class="spinner spinner--sm hidden"></span>
          </button>
        </form>
      </div>
    </div>

    <!-- Security Tab -->
    <div class="settings-tab" id="tab-security">
      <div class="settings-section">
        <h2 class="settings-section__title">Şifre Değiştir</h2>
        <form id="pwForm">
          <div class="form-group">
            <label class="form-label" for="currentPw">Mevcut Şifre</label>
            <input type="password" id="currentPw" class="form-control" autocomplete="current-password" />
          </div>
          <div class="form-group">
            <label class="form-label" for="newPw">Yeni Şifre</label>
            <input type="password" id="newPw" class="form-control" autocomplete="new-password" placeholder="En az 6 karakter" />
          </div>
          <div class="form-group">
            <label class="form-label" for="confirmPw">Yeni Şifre Tekrar</label>
            <input type="password" id="confirmPw" class="form-control" autocomplete="new-password" />
          </div>
          <div class="form-error" id="pwError" style="margin-bottom:12px"></div>
          <button type="submit" class="btn btn--primary" id="pwBtn">
            <span id="pwBtnText">Şifreyi Güncelle</span>
            <span id="pwSpinner" class="spinner spinner--sm hidden"></span>
          </button>
        </form>
      </div>
    </div>

    <!-- Danger Tab -->
    <div class="settings-tab" id="tab-danger">
      <div class="settings-section settings-section--danger">
        <h2 class="settings-section__title" style="color:var(--danger)">Hesabı Sil</h2>
        <p style="color:var(--text-secondary);margin-bottom:20px">
          Bu işlem geri alınamaz. Tüm görevleriniz, kategorileriniz ve verileriniz kalıcı olarak silinecektir.
        </p>
        <button class="btn btn--danger" id="deleteAccountBtn">Hesabımı Sil</button>
      </div>
    </div>

  </div>
</div>

<!-- Delete Account Modal -->
<div class="modal-overlay hidden" id="deleteAccountModal">
  <div class="modal" style="max-width:400px">
    <div class="modal__header">
      <h3 class="modal__title" style="color:var(--danger)">Hesabı Sil</h3>
      <button class="modal__close" id="closeDeleteModal">&times;</button>
    </div>
    <div class="modal__body">
      <p style="color:var(--text-secondary);margin-bottom:16px">
        Devam etmek için şifrenizi girin. Bu işlem geri alınamaz.
      </p>
      <div class="form-group">
        <input type="password" id="deletePassword" class="form-control" placeholder="Şifreniz" />
        <div class="form-error" id="deleteError"></div>
      </div>
    </div>
    <div class="modal__footer">
      <button class="btn btn--ghost" id="cancelDeleteBtn">İptal</button>
      <button class="btn btn--danger" id="confirmDeleteBtn">
        <span id="deleteBtnText">Hesabımı Kalıcı Olarak Sil</span>
        <span id="deleteSpinner" class="spinner spinner--sm hidden" style="border-top-color:white;border-color:rgba(255,255,255,.3)"></span>
      </button>
    </div>
  </div>
</div>

<style>
.settings-page {
  display: flex;
  gap: 24px;
  max-width: 900px;
  margin: 0 auto;
  padding: 24px;
}
.settings-nav {
  width: 200px;
  flex-shrink: 0;
}
.settings-nav__item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 10px 14px;
  border: none;
  background: none;
  border-radius: 8px;
  color: var(--text-secondary);
  font-size: .9375rem;
  cursor: pointer;
  text-align: left;
  transition: background .15s, color .15s;
  margin-bottom: 2px;
}
.settings-nav__item:hover { background: var(--surface-hover); color: var(--text-primary); }
.settings-nav__item.active { background: rgba(99,102,241,.1); color: var(--accent); font-weight: 500; }
.settings-nav__item--danger:hover { background: rgba(239,68,68,.08); color: var(--danger); }
.settings-nav__item--danger.active { background: rgba(239,68,68,.1); color: var(--danger); }
.settings-content { flex: 1; min-width: 0; }
.settings-tab { display: none; }
.settings-tab.active { display: block; }
.settings-section {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 20px;
}
.settings-section--danger { border-color: rgba(239,68,68,.3); }
.settings-section__title {
  font-size: 1.0625rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--border);
}
.settings-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 16px;
}
@media (max-width: 600px) { .settings-grid { grid-template-columns: 1fr; } }
.form-hint { font-size: .8125rem; color: var(--text-muted); margin: 4px 0 0; }
.avatar-picker {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 24px;
  padding: 16px;
  background: var(--bg);
  border-radius: 10px;
}
.avatar-display {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  font-weight: 700;
  color: #fff;
  flex-shrink: 0;
  transition: background .2s;
}
.avatar-colors {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 8px;
}
.avatar-color-swatch {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: 2px solid transparent;
  cursor: pointer;
  transition: transform .15s, border-color .15s;
}
.avatar-color-swatch:hover { transform: scale(1.15); }
.avatar-color-swatch.selected { border-color: var(--text-primary); transform: scale(1.1); }
.settings-toggle {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  user-select: none;
}
.settings-toggle input { display: none; }
.settings-toggle__track {
  width: 40px;
  height: 22px;
  background: var(--border);
  border-radius: 11px;
  position: relative;
  transition: background .2s;
  flex-shrink: 0;
}
.settings-toggle input:checked + .settings-toggle__track { background: var(--accent); }
.settings-toggle__thumb {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 18px;
  height: 18px;
  background: #fff;
  border-radius: 50%;
  transition: left .2s;
  box-shadow: 0 1px 3px rgba(0,0,0,.2);
}
.settings-toggle input:checked + .settings-toggle__track .settings-toggle__thumb { left: 20px; }
.settings-toggle__label { font-size: .9375rem; color: var(--text-primary); }
.badge { display:inline-block;padding:2px 8px;border-radius:999px;font-size:.75rem;font-weight:600 }
.badge--success { background:rgba(34,197,94,.12);color:var(--success) }
</style>

<script>
const BASE_URL = '<?= BASE_URL ?>';

let currentSettings = {};
let selectedColor   = '#6366f1';

// Yükle
async function loadSettings() {
  const res  = await fetch(BASE_URL + '/api/settings.php');
  const data = await res.json();
  if (!data.success) return;

  const { user, settings } = data.data;
  currentSettings = { ...user, ...settings };

  // Profil
  document.getElementById('pfUsername').value    = user.username    || '';
  document.getElementById('pfDisplayName').value = user.display_name || '';
  document.getElementById('pfEmail').value       = user.email        || '';

  if (user.email_verified == 1) {
    document.getElementById('verifiedBadge').style.display = 'inline';
  } else {
    document.getElementById('verifyLink').style.display = 'inline';
  }

  // Avatar
  selectedColor = settings.avatar_color || '#6366f1';
  updateAvatar(user.username, selectedColor);
  document.querySelectorAll('.avatar-color-swatch').forEach(sw => {
    sw.classList.toggle('selected', sw.dataset.color === selectedColor);
  });

  // Tercihler
  document.getElementById('pfTheme').value         = settings.theme        || 'light';
  document.getElementById('pfDefaultView').value   = settings.default_view || 'list';
  document.getElementById('pfLanguage').value      = settings.language      || 'tr';
  document.getElementById('pfNotifications').checked = settings.notifications_enabled == 1;
}

function updateAvatar(username, color) {
  const display = document.getElementById('avatarDisplay');
  display.style.background = color;
  const initial = (username || '?')[0].toUpperCase();
  document.getElementById('avatarInitial').textContent = initial;
}

// Tab geçişi
document.querySelectorAll('.settings-nav__item').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.settings-nav__item').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
  });
});

// Renk seçimi
document.querySelectorAll('.avatar-color-swatch').forEach(sw => {
  sw.addEventListener('click', () => {
    document.querySelectorAll('.avatar-color-swatch').forEach(s => s.classList.remove('selected'));
    sw.classList.add('selected');
    selectedColor = sw.dataset.color;
    updateAvatar(document.getElementById('pfUsername').value, selectedColor);
  });
});

// Profil kaydet
document.getElementById('profileForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const el = document.getElementById('profileError');
  el.textContent = ''; el.classList.remove('show');

  setFormLoading('profile', true);
  try {
    const res  = await fetch(BASE_URL + '/api/settings.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action:       'update',
        display_name: document.getElementById('pfDisplayName').value.trim(),
        avatar_color: selectedColor,
        theme:        currentSettings.theme        || 'light',
        default_view: currentSettings.default_view || 'list',
        language:     currentSettings.language      || 'tr',
        notifications_enabled: currentSettings.notifications_enabled ?? 1,
      }),
    });
    const data = await res.json();
    if (data.success) {
      el.textContent = data.message || 'Kaydedildi.';
      el.style.color = 'var(--success)'; el.classList.add('show');
    } else {
      el.textContent = data.message; el.style.color = ''; el.classList.add('show');
    }
  } catch { el.textContent = 'Sunucu hatası.'; el.classList.add('show'); }
  finally  { setFormLoading('profile', false); }
});

// Tercihler kaydet
document.getElementById('prefsForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const el = document.getElementById('prefsError');
  el.textContent = ''; el.classList.remove('show');

  const theme   = document.getElementById('pfTheme').value;
  const defView = document.getElementById('pfDefaultView').value;
  const lang    = document.getElementById('pfLanguage').value;
  const notifs  = document.getElementById('pfNotifications').checked;

  currentSettings.theme                 = theme;
  currentSettings.default_view          = defView;
  currentSettings.language              = lang;
  currentSettings.notifications_enabled = notifs ? 1 : 0;

  setFormLoading('prefs', true);
  try {
    const res  = await fetch(BASE_URL + '/api/settings.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'update',
        display_name: document.getElementById('pfDisplayName').value.trim(),
        avatar_color: selectedColor,
        theme, default_view: defView, language: lang,
        notifications_enabled: notifs,
      }),
    });
    const data = await res.json();
    if (data.success) {
      // Temayı anında uygula
      document.documentElement.setAttribute('data-theme', theme);
      localStorage.setItem('theme', theme);
      el.textContent = data.message || 'Kaydedildi.';
      el.style.color = 'var(--success)'; el.classList.add('show');
    } else {
      el.textContent = data.message; el.style.color = ''; el.classList.add('show');
    }
  } catch { el.textContent = 'Sunucu hatası.'; el.classList.add('show'); }
  finally  { setFormLoading('prefs', false); }
});

// Şifre değiştir
document.getElementById('pwForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const el = document.getElementById('pwError');
  el.textContent = ''; el.classList.remove('show');

  const current = document.getElementById('currentPw').value;
  const nw      = document.getElementById('newPw').value;
  const conf    = document.getElementById('confirmPw').value;

  if (!current || !nw || !conf) { el.textContent = 'Tüm alanlar zorunludur.'; el.classList.add('show'); return; }
  if (nw !== conf)              { el.textContent = 'Yeni şifreler eşleşmiyor.'; el.classList.add('show'); return; }

  setFormLoading('pw', true);
  try {
    const res  = await fetch(BASE_URL + '/api/settings.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'change_password', current_password: current, new_password: nw, confirm_password: conf }),
    });
    const data = await res.json();
    if (data.success) {
      el.textContent = data.message; el.style.color = 'var(--success)'; el.classList.add('show');
      document.getElementById('pwForm').reset();
    } else {
      el.textContent = data.message; el.style.color = ''; el.classList.add('show');
    }
  } catch { el.textContent = 'Sunucu hatası.'; el.classList.add('show'); }
  finally  { setFormLoading('pw', false); }
});

// Hesap sil
document.getElementById('deleteAccountBtn').addEventListener('click', () => {
  document.getElementById('deletePassword').value = '';
  document.getElementById('deleteError').textContent = '';
  document.getElementById('deleteAccountModal').classList.remove('hidden');
});
document.getElementById('closeDeleteModal').addEventListener('click', closeDeleteModal);
document.getElementById('cancelDeleteBtn').addEventListener('click', closeDeleteModal);
function closeDeleteModal() { document.getElementById('deleteAccountModal').classList.add('hidden'); }

document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
  const pw = document.getElementById('deletePassword').value;
  const el = document.getElementById('deleteError');
  if (!pw) { el.textContent = 'Şifre zorunludur.'; el.classList.add('show'); return; }

  document.getElementById('confirmDeleteBtn').disabled = true;
  document.getElementById('deleteBtnText').textContent = 'Siliniyor…';
  document.getElementById('deleteSpinner').classList.remove('hidden');
  try {
    const res  = await fetch(BASE_URL + '/api/settings.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'delete_account', password: pw }),
    });
    const data = await res.json();
    if (data.success) {
      window.location.href = BASE_URL + '/login.php';
    } else {
      el.textContent = data.message; el.classList.add('show');
      document.getElementById('confirmDeleteBtn').disabled = false;
      document.getElementById('deleteBtnText').textContent = 'Hesabımı Kalıcı Olarak Sil';
      document.getElementById('deleteSpinner').classList.add('hidden');
    }
  } catch {
    el.textContent = 'Sunucu hatası.'; el.classList.add('show');
    document.getElementById('confirmDeleteBtn').disabled = false;
  }
});

function setFormLoading(form, on) {
  const btn = document.getElementById(form + 'Btn');
  const txt = document.getElementById(form + 'BtnText');
  const sp  = document.getElementById(form + 'Spinner');
  if (btn) btn.disabled = on;
  if (txt) txt.textContent = on ? 'Kaydediliyor…' : (form === 'pw' ? 'Şifreyi Güncelle' : 'Kaydet');
  if (sp)  sp.classList.toggle('hidden', !on);
}

loadSettings();
</script>

</body>
</html>
