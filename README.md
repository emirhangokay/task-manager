# ✅ Görev Yönetim

Modern, tam özellikli bir To-Do / Görev Yönetim uygulaması. PHP + MySQL backend, saf JavaScript frontend ile geliştirilmiştir.

---

## 📸 Ekran Görüntüleri

> *(Ekran görüntülerinizi buraya ekleyin)*

| Dashboard | Görev Ekleme | Karanlık Tema |
|-----------|-------------|--------------|
| ![Dashboard](screenshots/dashboard.png) | ![Görev Ekle](screenshots/add-task.png) | ![Dark Mode](screenshots/dark-mode.png) |

---

## ✨ Özellikler

### Kullanıcı Sistemi
- Kayıt ve giriş (bcrypt şifreleme)
- PHP Session ile güvenli oturum yönetimi
- Oturum açmadan hiçbir sayfaya erişilemez

### Görev Yönetimi
- Görev ekleme, düzenleme, silme (AJAX, sayfa yenilemesiz)
- Tek tıkla tamamlama (checkbox)
- Durum değiştirme: Bekliyor → Devam Ediyor → Tamamlandı
- Öncelik seviyeleri: Düşük / Orta / Yüksek
- Opsiyonel son tarih ve açıklama

### Kategoriler
- Özel kategoriler oluşturma (renk seçimiyle)
- 12 preset renk seçeneği
- Kategori düzenleme ve silme
- Kayıt olunca 4 varsayılan kategori otomatik oluşur: Genel, İş, Kişisel, Acil

### Filtreleme & Sıralama
- Duruma, kategoriye ve önceliğe göre filtrele
- 5 farklı sıralama seçeneği (en yeni, en eski, öncelik, son tarih, alfabetik)
- Canlı arama (başlık ve açıklama)

### Dashboard
- Toplam / Bekleyen / Devam Eden / Tamamlanan / Gecikmiş sayıları
- Renk kodlu istatistik kartları

### Tasarım & UX
- Aydınlık / Karanlık tema (localStorage'da kaydedilir)
- Tam responsive tasarım (mobil hamburger menü)
- CSS animasyonları ve geçişler
- Toast bildirimleri
- Loading spinner

### Güvenlik
- SQL Injection koruması (PDO Prepared Statements)
- XSS koruması (htmlspecialchars)
- CSRF token koruması
- Session güvenliği (session_regenerate_id)
- Şifre minimum 6 karakter, bcrypt hash

---

## 🛠️ Teknolojiler

| Katman     | Teknoloji                  |
|------------|---------------------------|
| Backend    | PHP 8+, PDO               |
| Veritabanı | MySQL 8+                  |
| Frontend   | HTML5, CSS3, Vanilla JS   |
| Kimlik Doğrulama | PHP Sessions        |
| Stil       | BEM metodolojisi, CSS Vars |

---

## 🚀 Kurulum

### Gereksinimler
- PHP 8.0+
- MySQL 8.0+
- Web sunucusu (Apache / Nginx) veya PHP built-in server

### Adımlar

**1. Repoyu klonla**
```bash
git clone https://github.com/emirhangokay/task-manager.git
cd task-manager
```

**2. MySQL'de veritabanı oluştur**
```sql
CREATE DATABASE task_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**3. `database.sql` dosyasını import et**
```bash
mysql -u root -p task_manager < database.sql
```

**4. Veritabanı bağlantısını yapılandır**

`config/database.php` dosyasını kendi bilgilerinizle güncelleyin:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'task_manager');
define('DB_USER', 'root');       // MySQL kullanıcı adınız
define('DB_PASS', '');           // MySQL şifreniz
```

**5. PHP geliştirme sunucusunu başlat**
```bash
php -S localhost:8000
```

> **Not:** Proje kök dizininden çalıştırın. URL'ler `/index.php` gibi kök göreceli olarak yazılmıştır.

**6. Tarayıcıda açın**

```
http://localhost:8000
```

Kayıt olun → Giriş yapın → Görev eklemeye başlayın! 🎉

---

## 🗄️ Veritabanı Şeması

```
users
 ├── id (PK, AUTO_INCREMENT)
 ├── username (UNIQUE)
 ├── email (UNIQUE)
 ├── password (bcrypt)
 └── created_at

categories
 ├── id (PK)
 ├── user_id (FK → users.id)
 ├── name
 └── color (HEX, ör. #4F46E5)

tasks
 ├── id (PK)
 ├── user_id (FK → users.id)
 ├── category_id (FK → categories.id, NULL olabilir)
 ├── title
 ├── description (NULL olabilir)
 ├── priority ENUM(low, medium, high)
 ├── status ENUM(pending, in_progress, completed)
 ├── due_date (NULL olabilir)
 ├── created_at
 └── updated_at
```

---

## 📁 Dosya Yapısı

```
task-manager/
├── config/
│   └── database.php          # PDO bağlantısı
├── includes/
│   ├── auth.php              # Session, login/register
│   ├── functions.php         # Yardımcı fonksiyonlar
│   └── header.php            # Ortak HTML head
├── api/
│   ├── tasks.php             # Görev CRUD (AJAX)
│   ├── categories.php        # Kategori CRUD (AJAX)
│   └── auth.php              # Auth API (AJAX)
├── assets/
│   ├── css/style.css         # Tüm stiller (BEM)
│   └── js/app.js             # Tüm JavaScript
├── database.sql              # Veritabanı şeması
├── index.php                 # Ana sayfa
├── login.php                 # Giriş
├── register.php              # Kayıt
├── logout.php                # Çıkış
└── README.md
```

---

## 📄 Lisans

MIT License — özgürce kullanabilir, değiştirebilir ve dağıtabilirsiniz.
