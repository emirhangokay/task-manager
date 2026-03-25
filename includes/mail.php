<?php
/**
 * includes/mail.php
 * ============================================================
 * E-posta gönderme yardımcıları — Resend API veya log driver.
 * ============================================================
 */

if (!defined('MAIL_DRIVER')) {
    $mailCfg = __DIR__ . '/../config/mail.php';
    if (file_exists($mailCfg)) {
        require_once $mailCfg;
    } else {
        define('MAIL_DRIVER',    'log');
        define('RESEND_API_KEY', '');
        define('MAIL_FROM',      'noreply@example.com');
        define('MAIL_FROM_NAME', 'TaskFlow');
        define('APP_NAME',       'TaskFlow');
    }
}

/**
 * Temel e-posta gönder.
 * @return bool
 */
function sendMail(string $to, string $toName, string $subject, string $htmlBody): bool {
    if (MAIL_DRIVER === 'log') {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
        $line = date('[Y-m-d H:i:s]') . " TO: $to | SUBJECT: $subject\n$htmlBody\n" . str_repeat('-', 60) . "\n";
        file_put_contents($logDir . '/mail.log', $line, FILE_APPEND);
        return true;
    }

    $payload = json_encode([
        'from'    => MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
        'to'      => [['email' => $to, 'name' => $toName]],
        'subject' => $subject,
        'html'    => $htmlBody,
    ]);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . RESEND_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode >= 200 && $httpCode < 300;
}

/**
 * E-posta doğrulama kodu gönder.
 */
function sendVerificationEmail(string $to, string $username, string $code): bool {
    $appName = APP_NAME;
    $subject = "$appName — E-posta Doğrulama Kodunuz";
    $html    = <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head><meta charset="UTF-8"><style>
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f8fafc;margin:0;padding:40px 16px}
  .card{max-width:480px;margin:0 auto;background:#fff;border-radius:12px;padding:40px;box-shadow:0 2px 12px rgba(0,0,0,.08)}
  .logo{display:flex;align-items:center;gap:10px;margin-bottom:32px}
  .logo-icon{width:38px;height:38px;background:#6366f1;border-radius:10px;display:flex;align-items:center;justify-content:center}
  h2{margin:0 0 8px;color:#0f172a;font-size:1.25rem}
  p{color:#475569;line-height:1.6;margin:0 0 20px}
  .code-box{background:#f1f5f9;border:2px dashed #6366f1;border-radius:10px;text-align:center;padding:20px;margin:24px 0}
  .code{font-size:2.5rem;font-weight:700;letter-spacing:8px;color:#6366f1;font-family:monospace}
  .expire{font-size:.8125rem;color:#94a3b8;margin-top:8px}
  .footer{margin-top:32px;font-size:.8125rem;color:#94a3b8;border-top:1px solid #e2e8f0;padding-top:20px}
</style></head>
<body>
<div class="card">
  <div class="logo">
    <div class="logo-icon"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M3 10l5 5L17 6" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
    <strong style="font-size:1.125rem;color:#0f172a">$appName</strong>
  </div>
  <h2>E-posta Adresinizi Doğrulayın</h2>
  <p>Merhaba <strong>$username</strong>,<br>Hesabınızı doğrulamak için aşağıdaki 6 haneli kodu kullanın.</p>
  <div class="code-box">
    <div class="code">$code</div>
    <div class="expire">Bu kod 30 dakika geçerlidir.</div>
  </div>
  <p style="font-size:.9rem">Bu isteği siz yapmadıysanız bu e-postayı görmezden gelebilirsiniz.</p>
  <div class="footer">$appName &mdash; Görev Yönetim Uygulaması</div>
</div>
</body></html>
HTML;
    return sendMail($to, $username, $subject, $html);
}

/**
 * Şifre sıfırlama bağlantısı gönder.
 */
function sendPasswordResetEmail(string $to, string $username, string $resetUrl): bool {
    $appName = APP_NAME;
    $subject = "$appName — Şifre Sıfırlama";
    $html    = <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head><meta charset="UTF-8"><style>
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f8fafc;margin:0;padding:40px 16px}
  .card{max-width:480px;margin:0 auto;background:#fff;border-radius:12px;padding:40px;box-shadow:0 2px 12px rgba(0,0,0,.08)}
  .logo{display:flex;align-items:center;gap:10px;margin-bottom:32px}
  .logo-icon{width:38px;height:38px;background:#6366f1;border-radius:10px;display:flex;align-items:center;justify-content:center}
  h2{margin:0 0 8px;color:#0f172a;font-size:1.25rem}
  p{color:#475569;line-height:1.6;margin:0 0 20px}
  .btn{display:inline-block;background:#6366f1;color:#fff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:600;font-size:.9375rem}
  .expire{font-size:.8125rem;color:#94a3b8;margin-top:16px}
  .footer{margin-top:32px;font-size:.8125rem;color:#94a3b8;border-top:1px solid #e2e8f0;padding-top:20px}
</style></head>
<body>
<div class="card">
  <div class="logo">
    <div class="logo-icon"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M3 10l5 5L17 6" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
    <strong style="font-size:1.125rem;color:#0f172a">$appName</strong>
  </div>
  <h2>Şifrenizi Sıfırlayın</h2>
  <p>Merhaba <strong>$username</strong>,<br>Şifrenizi sıfırlamak için aşağıdaki butona tıklayın.</p>
  <a href="$resetUrl" class="btn">Şifremi Sıfırla</a>
  <p class="expire">Bu bağlantı 1 saat geçerlidir. İsteği siz yapmadıysanız görmezden gelin.</p>
  <div class="footer">$appName &mdash; Görev Yönetim Uygulaması</div>
</div>
</body></html>
HTML;
    return sendMail($to, $username, $subject, $html);
}
