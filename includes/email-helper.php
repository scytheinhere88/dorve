<?php
/**
 * Email Helper Functions
 * Professional email system for Dorve House
 */

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com'); // Change based on your email provider
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com'); // Change this
define('SMTP_PASSWORD', 'your-app-password'); // Change this
define('FROM_EMAIL', 'noreply@dorve.id');
define('FROM_NAME', 'Dorve.id - Pusat Fashion Indonesia');
define('SITE_URL', 'https://dorve.id/');

/**
 * Send Email using PHP mail() function
 * For production, consider using PHPMailer or SendGrid
 */
function sendEmail($to, $subject, $html_body, $from_name = FROM_NAME, $from_email = FROM_EMAIL) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . $from_name . " <" . $from_email . ">" . "\r\n";
    $headers .= "Reply-To: " . $from_email . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // For production, use PHPMailer or SMTP library
    // This is a basic implementation
    return mail($to, $subject, $html_body, $headers);
}

/**
 * Get Email Template Wrapper
 */
function getEmailTemplate($title, $content) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($title) . '</title>
    </head>
    <body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #F3F4F6;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #F3F4F6; padding: 40px 20px;">
            <tr>
                <td align="center">
                    <table width="600" cellpadding="0" cellspacing="0" style="background-color: #FFFFFF; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <!-- Header -->
                        <tr>
                            <td style="background: linear-gradient(135deg, #1A1A1A 0%, #2D2D2D 100%); padding: 40px 40px 30px; text-align: center;">
                                <h1 style="margin: 0; color: #FFFFFF; font-size: 32px; font-weight: 700; letter-spacing: 2px;">DORVE.ID</h1>
                                <p style="margin: 8px 0 0; color: rgba(255,255,255,0.8); font-size: 14px;">Pusat Fashion Indonesia</p>
                            </td>
                        </tr>
                        
                        <!-- Content -->
                        <tr>
                            <td style="padding: 40px;">
                                ' . $content . '
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td style="background-color: #F9FAFB; padding: 30px 40px; border-top: 1px solid #E5E7EB;">
                                <p style="margin: 0 0 12px; font-size: 14px; color: #6B7280; text-align: center;">
                                    Butuh bantuan? <a href="https://dorve.id/pages/contact.php" style="color: #1A1A1A; text-decoration: none; font-weight: 600;">Hubungi Kami</a>
                                </p>
                                <p style="margin: 0; font-size: 12px; color: #9CA3AF; text-align: center;">
                                    © 2025 Dorve House. All rights reserved.<br>
                                    Email ini dikirim otomatis, mohon jangan balas.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ';
}

/**
 * Send Verification Email
 */
function sendVerificationEmail($email, $name, $verification_link) {
    $subject = 'Verifikasi Email Anda - Dorve House';
    
    $content = '
    <h2 style="margin: 0 0 24px; font-size: 24px; color: #1F2937; font-weight: 700;">Selamat Datang di Dorve.id, ' . htmlspecialchars($name) . '! 🎉</h2>
    
    <p style="margin: 0 0 16px; font-size: 16px; color: #374151; line-height: 1.6;">
        Terima kasih sudah bergabung di <strong>Dorve.id</strong> - Pusat Fashion Indonesia! Untuk melanjutkan, silakan verifikasi email Anda dengan klik tombol di bawah:
    </p>
    
    <table width="100%" cellpadding="0" cellspacing="0" style="margin: 32px 0;">
        <tr>
            <td align="center">
                <a href="' . $verification_link . '" style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #1A1A1A 0%, #2D2D2D 100%); color: #FFFFFF; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; letter-spacing: 0.5px;">Verifikasi Email</a>
            </td>
        </tr>
    </table>
    
    <p style="margin: 24px 0 0; font-size: 14px; color: #6B7280; line-height: 1.6;">
        Atau copy link berikut ke browser Anda:<br>
        <a href="' . $verification_link . '" style="color: #3B82F6; word-break: break-all;">' . $verification_link . '</a>
    </p>
    
    <div style="margin-top: 32px; padding: 20px; background-color: #FEF3C7; border-left: 4px solid #F59E0B; border-radius: 6px;">
        <p style="margin: 0; font-size: 14px; color: #92400E; line-height: 1.6;">
            ⚠️ <strong>Link verifikasi berlaku selama 24 jam.</strong><br>
            Jika Anda tidak mendaftar di Dorve.id, abaikan email ini.
        </p>
    </div>
    ';
    
    $html = getEmailTemplate('Verifikasi Email', $content);
    return sendEmail($email, $subject, $html);
}

/**
 * Send Password Reset Email
 */
function sendPasswordResetEmail($email, $name, $reset_link) {
    $subject = 'Reset Password Anda - Dorve House';
    
    $content = '
    <h2 style="margin: 0 0 24px; font-size: 24px; color: #1F2937; font-weight: 700;">Reset Password</h2>
    
    <p style="margin: 0 0 16px; font-size: 16px; color: #374151; line-height: 1.6;">
        Halo <strong>' . htmlspecialchars($name) . '</strong>,
    </p>
    
    <p style="margin: 0 0 16px; font-size: 16px; color: #374151; line-height: 1.6;">
        Kami menerima permintaan untuk reset password akun Anda. Klik tombol di bawah untuk membuat password baru:
    </p>
    
    <table width="100%" cellpadding="0" cellspacing="0" style="margin: 32px 0;">
        <tr>
            <td align="center">
                <a href="' . $reset_link . '" style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%); color: #FFFFFF; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; letter-spacing: 0.5px;">Reset Password</a>
            </td>
        </tr>
    </table>
    
    <p style="margin: 24px 0 0; font-size: 14px; color: #6B7280; line-height: 1.6;">
        Atau copy link berikut ke browser Anda:<br>
        <a href="' . $reset_link . '" style="color: #3B82F6; word-break: break-all;">' . $reset_link . '</a>
    </p>
    
    <div style="margin-top: 32px; padding: 20px; background-color: #FEE2E2; border-left: 4px solid #EF4444; border-radius: 6px;">
        <p style="margin: 0; font-size: 14px; color: #991B1B; line-height: 1.6;">
            ⚠️ <strong>Link reset berlaku selama 1 jam.</strong><br>
            Jika Anda tidak meminta reset password, abaikan email ini atau hubungi customer service.
        </p>
    </div>
    ';
    
    $html = getEmailTemplate('Reset Password', $content);
    return sendEmail($email, $subject, $html);
}

/**
 * Send Order Confirmation Email
 */
function sendOrderConfirmationEmail($email, $name, $order_number, $order_total, $order_items) {
    $subject = 'Pesanan Anda Dikonfirmasi #' . $order_number;
    
    $items_html = '';
    foreach ($order_items as $item) {
        $items_html .= '
        <tr>
            <td style="padding: 12px; border-bottom: 1px solid #E5E7EB;">' . htmlspecialchars($item['name']) . '</td>
            <td style="padding: 12px; border-bottom: 1px solid #E5E7EB; text-align: center;">' . $item['quantity'] . '</td>
            <td style="padding: 12px; border-bottom: 1px solid #E5E7EB; text-align: right;">Rp ' . number_format($item['price'], 0, ',', '.') . '</td>
        </tr>
        ';
    }
    
    $content = '
    <h2 style="margin: 0 0 24px; font-size: 24px; color: #1F2937; font-weight: 700;">Terima Kasih, ' . htmlspecialchars($name) . '! 🎉</h2>
    
    <p style="margin: 0 0 16px; font-size: 16px; color: #374151; line-height: 1.6;">
        Pesanan Anda telah kami terima dan sedang diproses.
    </p>
    
    <div style="margin: 24px 0; padding: 20px; background-color: #DBEAFE; border-radius: 8px;">
        <p style="margin: 0 0 8px; font-size: 14px; color: #1E40AF; font-weight: 600;">Order Number</p>
        <p style="margin: 0; font-size: 24px; color: #1E3A8A; font-weight: 700;">#' . $order_number . '</p>
    </div>
    
    <table width="100%" cellpadding="0" cellspacing="0" style="margin: 24px 0; border: 1px solid #E5E7EB; border-radius: 8px; overflow: hidden;">
        <thead>
            <tr style="background-color: #F9FAFB;">
                <th style="padding: 12px; text-align: left; font-size: 14px; color: #6B7280; font-weight: 600;">Item</th>
                <th style="padding: 12px; text-align: center; font-size: 14px; color: #6B7280; font-weight: 600;">Qty</th>
                <th style="padding: 12px; text-align: right; font-size: 14px; color: #6B7280; font-weight: 600;">Price</th>
            </tr>
        </thead>
        <tbody>
            ' . $items_html . '
            <tr style="background-color: #F9FAFB;">
                <td colspan="2" style="padding: 12px; font-weight: 600; color: #1F2937;">Total</td>
                <td style="padding: 12px; text-align: right; font-weight: 700; color: #1F2937; font-size: 18px;">Rp ' . number_format($order_total, 0, ',', '.') . '</td>
            </tr>
        </tbody>
    </table>
    
    <table width="100%" cellpadding="0" cellspacing="0" style="margin: 32px 0;">
        <tr>
            <td align="center">
                <a href="' . SITE_URL . 'member/orders.php" style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #1A1A1A 0%, #2D2D2D 100%); color: #FFFFFF; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; letter-spacing: 0.5px;">Lihat Detail Pesanan</a>
            </td>
        </tr>
    </table>
    
    <p style="margin: 24px 0 0; font-size: 14px; color: #6B7280; line-height: 1.6; text-align: center;">
        Kami akan mengirim email update ketika pesanan Anda dikirim.
    </p>
    ';
    
    $html = getEmailTemplate('Order Confirmation', $content);
    return sendEmail($email, $subject, $html);
}

/**
 * Send Shipping Update Email
 */
function sendShippingUpdateEmail($email, $name, $order_number, $tracking_number, $courier) {
    $subject = 'Pesanan Anda Sedang Dikirim #' . $order_number;
    
    $content = '
    <h2 style="margin: 0 0 24px; font-size: 24px; color: #1F2937; font-weight: 700;">Pesanan Dalam Perjalanan! 📦</h2>
    
    <p style="margin: 0 0 16px; font-size: 16px; color: #374151; line-height: 1.6;">
        Halo <strong>' . htmlspecialchars($name) . '</strong>,
    </p>
    
    <p style="margin: 0 0 16px; font-size: 16px; color: #374151; line-height: 1.6;">
        Kabar gembira! Pesanan Anda <strong>#' . $order_number . '</strong> telah dikirim dan sedang dalam perjalanan ke alamat Anda.
    </p>
    
    <div style="margin: 24px 0; padding: 24px; background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%); border-radius: 12px;">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding: 8px 0;">
                    <p style="margin: 0; font-size: 14px; color: #1E40AF; font-weight: 600;">Kurir</p>
                    <p style="margin: 4px 0 0; font-size: 18px; color: #1E3A8A; font-weight: 700;">' . htmlspecialchars($courier) . '</p>
                </td>
            </tr>
            <tr>
                <td style="padding: 16px 0 8px;">
                    <p style="margin: 0; font-size: 14px; color: #1E40AF; font-weight: 600;">Nomor Resi</p>
                    <p style="margin: 4px 0 0; font-size: 20px; color: #1E3A8A; font-weight: 700; letter-spacing: 1px;">' . htmlspecialchars($tracking_number) . '</p>
                </td>
            </tr>
        </table>
    </div>
    
    <table width="100%" cellpadding="0" cellspacing="0" style="margin: 32px 0;">
        <tr>
            <td align="center">
                <a href="' . SITE_URL . 'member/orders.php" style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: #FFFFFF; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; letter-spacing: 0.5px;">Lacak Pesanan</a>
            </td>
        </tr>
    </table>
    
    <p style="margin: 24px 0 0; font-size: 14px; color: #6B7280; line-height: 1.6; text-align: center;">
        Terima kasih sudah berbelanja di Dorve.id! 💚
    </p>
    ';
    
    $html = getEmailTemplate('Shipping Update', $content);
    return sendEmail($email, $subject, $html);
}
