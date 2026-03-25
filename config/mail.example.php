<?php
/**
 * config/mail.example.php
 * ============================================================
 * E-posta yapılandırması örneği.
 * Bu dosyayı config/mail.php olarak kopyalayıp doldurun.
 * config/mail.php .gitignore ile gizlidir.
 * ============================================================
 */

define('MAIL_DRIVER',    'resend');          // 'resend' veya 'log'
define('RESEND_API_KEY', 're_xxxxxxxxxxxx'); // Resend API anahtarınız
define('MAIL_FROM',      'noreply@yourdomain.com');
define('MAIL_FROM_NAME', 'TaskFlow');
define('APP_NAME',       'TaskFlow');
