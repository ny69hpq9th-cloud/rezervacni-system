<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';

// Load PHPMailer only if files are present on the server
define('PHPMAILER_AVAILABLE',
    file_exists(__DIR__ . '/phpmailer/PHPMailer.php') &&
    file_exists(__DIR__ . '/phpmailer/SMTP.php') &&
    file_exists(__DIR__ . '/phpmailer/Exception.php')
);

if (PHPMAILER_AVAILABLE) {
    require_once __DIR__ . '/phpmailer/Exception.php';
    require_once __DIR__ . '/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/phpmailer/SMTP.php';
}

function sendMail(string $to, string $subject, string $htmlBody): bool {
    if (PHPMAILER_AVAILABLE) {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addReplyTo(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);

            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log('PHPMailer error [' . $to . ']: ' . $e->getMessage());
            return false;
        }
    }

    // Fallback: PHP mail() — used when PHPMailer files are not yet uploaded
    $headers  = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $encSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    return mail($to, $encSubject, $htmlBody, $headers);
}

function emailTemplate(string $heading, string $body, string $lang = ''): string {
    if (!$lang) $lang = currentLang();
    $name = PLATFORM_NAME;
    $url  = PLATFORM_URL;
    $htmlLang = $lang === 'en' ? 'en' : 'cs';
    return <<<HTML
<!DOCTYPE html>
<html lang="{$htmlLang}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$heading}</title>
</head>
<body style="margin:0;padding:20px;background:#f8fafc;font-family:Inter,Arial,sans-serif;">
<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1)">
  <div style="background:#2563eb;padding:32px;text-align:center">
    <h1 style="margin:0;color:#fff;font-size:22px;letter-spacing:-.3px">{$name}</h1>
    <p style="margin:6px 0 0;color:#bfdbfe;font-size:14px">{$heading}</p>
  </div>
  <div style="padding:32px;color:#374151;line-height:1.7;font-size:15px">
    {$body}
  </div>
  <div style="background:#f8fafc;padding:20px;text-align:center;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb">
    {$name} &bull; <a href="{$url}" style="color:#6b7280">{$url}</a>
  </div>
</div>
</body>
</html>
HTML;
}

function emailWelcome(array $user): void {
    $lang     = currentLang();
    $trialEnd = date('j. n. Y', strtotime($user['created_at']) + (TRIAL_DAYS * 86400));
    $bookUrl  = PLATFORM_URL . '/rezervace/' . $user['slug'];
    $dashUrl  = PLATFORM_URL . '/login.php';
    $name     = e($user['business_name']);

    $hi      = __('email.welcome_hi',   ['name'      => $name]);
    $body1   = __('email.welcome_body', ['platform'  => PLATFORM_NAME, 'trial_end' => $trialEnd]);
    $pageStr = __('email.welcome_page');
    $dashBtn = __('email.welcome_dash');
    $hint    = __('email.welcome_hint');

    $body = <<<HTML
<p>{$hi}</p>
<p>{$body1}</p>
<p>{$pageStr}<br>
<a href="{$bookUrl}" style="color:#2563eb">{$bookUrl}</a></p>
<p style="margin-top:24px">
<a href="{$dashUrl}" style="display:inline-block;padding:12px 28px;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">{$dashBtn}</a>
</p>
<p style="color:#6b7280;font-size:14px;margin-top:24px">{$hint}</p>
HTML;

    $subject = __('email.welcome_subject', ['name' => PLATFORM_NAME]);
    $heading = __('email.welcome_heading');
    sendMail($user['email'], $subject, emailTemplate($heading, $body, $lang));
}

function emailBookingCustomer(array $booking, array $service, array $business): void {
    $lang    = currentLang();
    $bName   = e($business['business_name']);
    $sName   = e($service['name']);
    $date    = formatDate($booking['date']);
    $time    = formatTime($booking['time']);
    $cName   = e($booking['customer_name']);
    $addr    = !empty($business['address']) ? e($business['address']) : '';
    $contact = !empty($business['notification_email']) ? e($business['notification_email']) : e($business['email']);

    $addrRow = $addr ? "<tr><td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px'>" . __('email.booking_address') . "</td><td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;font-weight:500'>{$addr}</td></tr>" : '';

    $hi       = __('email.booking_hi',   ['name' => $cName]);
    $bodyText = __('email.booking_body');
    $ctaText  = __('email.booking_contact');
    $colBiz   = __('email.booking_biz');
    $colSvc   = __('email.booking_service');
    $colDate  = __('email.booking_date');
    $colTime  = __('email.booking_time');

    $body = <<<HTML
<p>{$hi}</p>
<p>{$bodyText}</p>
<table style="width:100%;border-collapse:collapse;margin:20px 0;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden">
  <tr><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">{$colBiz}</td><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;font-weight:500">{$bName}</td></tr>
  <tr><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">{$colSvc}</td><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;font-weight:500">{$sName}</td></tr>
  <tr><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">{$colDate}</td><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;font-weight:500">{$date}</td></tr>
  <tr><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">{$colTime}</td><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;font-weight:500">{$time}</td></tr>
  {$addrRow}
</table>
<p style="color:#6b7280;font-size:14px">{$ctaText} <a href="mailto:{$contact}" style="color:#2563eb">{$contact}</a>.</p>
HTML;

    $subject = __('email.booking_subject', ['business' => $business['business_name']]);
    $heading = __('email.booking_heading');
    sendMail($booking['customer_email'], $subject, emailTemplate($heading, $body, $lang));
}

function emailBookingBusiness(array $booking, array $service, array $business): void {
    $lang    = currentLang();
    $sName   = e($service['name']);
    $date    = formatDate($booking['date']);
    $time    = formatTime($booking['time']);
    $cName   = e($booking['customer_name']);
    $cEmail  = e($booking['customer_email']);
    $cPhone  = e($booking['customer_phone']);
    $notes   = !empty($booking['notes']) ? e($booking['notes']) : '—';
    $dashUrl = PLATFORM_URL . '/dashboard/bookings.php';

    $bodyText  = __('email.notify_body');
    $colCust   = __('email.notify_customer');
    $colEmail  = __('email.notify_email');
    $colPhone  = __('email.notify_phone');
    $colSvc    = __('email.notify_service');
    $colDate   = __('email.notify_date');
    $colTime   = __('email.notify_time');
    $colNote   = __('email.notify_note');
    $viewBtn   = __('email.notify_view');

    $body = <<<HTML
<p>{$bodyText}</p>
<table style="width:100%;border-collapse:collapse;margin:20px 0;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden">
  <tr><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">{$colCust}</td><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;font-weight:500">{$cName}</td></tr>
  <tr><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">{$colEmail}</td><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb"><a href="mailto:{$cEmail}" style="color:#2563eb">{$cEmail}</a></td></tr>
  <tr><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">{$colPhone}</td><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;font-weight:500">{$cPhone}</td></tr>
  <tr><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">{$colSvc}</td><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;font-weight:500">{$sName}</td></tr>
  <tr><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">{$colDate}</td><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;font-weight:500">{$date}</td></tr>
  <tr><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">{$colTime}</td><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;font-weight:500">{$time}</td></tr>
  <tr><td style="padding:8px 12px;color:#6b7280;font-size:13px">{$colNote}</td><td style="padding:8px 12px">{$notes}</td></tr>
</table>
<p><a href="{$dashUrl}" style="display:inline-block;padding:12px 28px;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">{$viewBtn}</a></p>
HTML;

    $to      = !empty($business['notification_email']) ? $business['notification_email'] : $business['email'];
    $subject = __('email.notify_subject', ['customer' => $booking['customer_name']]);
    $heading = __('email.notify_heading');
    sendMail($to, $subject, emailTemplate($heading, $body, $lang));
}

function emailCancellation(array $booking, array $service, array $business): void {
    $lang    = currentLang();
    $cName   = e($booking['customer_name']);
    $sName   = e($service['name']);
    $date    = formatDate($booking['date']);
    $time    = formatTime($booking['time']);
    $bookUrl = PLATFORM_URL . '/rezervace/' . $business['slug'];

    $hi       = __('email.cancel_hi',    ['name' => $cName]);
    $bodyText = __('email.cancel_body');
    $colSvc   = __('email.cancel_service');
    $colDate  = __('email.cancel_date');
    $colTime  = __('email.cancel_time');
    $rebook   = __('email.cancel_rebook');
    $rebookLnk= __('email.cancel_link');

    $body = <<<HTML
<p>{$hi}</p>
<p>{$bodyText}</p>
<table style="width:100%;border-collapse:collapse;margin:20px 0;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden">
  <tr><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">{$colSvc}</td><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;font-weight:500">{$sName}</td></tr>
  <tr><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px">{$colDate}</td><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;font-weight:500">{$date}</td></tr>
  <tr><td style="padding:8px 12px;color:#6b7280;font-size:13px">{$colTime}</td><td style="padding:8px 12px;font-weight:500">{$time}</td></tr>
</table>
<p>{$rebook} <a href="{$bookUrl}" style="color:#2563eb">{$rebookLnk}</a></p>
HTML;

    $subject = __('email.cancel_subject');
    $heading = __('email.cancel_heading');
    sendMail($booking['customer_email'], $subject, emailTemplate($heading, $body, $lang));
}
