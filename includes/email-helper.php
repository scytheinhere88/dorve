<?php
/**
 * EMAIL HELPER - Professional Email Templates
 *
 * PRODUCTION READY:
 * - Uses responsive HTML email template
 * - Includes store logo
 * - Professional design
 * - 24-hour token expiry
 *
 * IMPORTANT: For production, use SMTP service like:
 * - SendGrid
 * - Mailgun
 * - AWS SES
 * - Brevo (Sendinblue)
 */

/**
 * Send verification email with professional template
 *
 * @param string $email User email
 * @param string $name User name
 * @param string $verification_link Full verification URL with token
 * @return bool Success status
 */
function sendVerificationEmail($email, $name, $verification_link) {
    $subject = '✓ Verifikasi Email Anda - Dorve House';

    // Get logo URL
    $logo_url = SITE_URL . 'public/images/favicon.png';
    $site_url = SITE_URL;

    // Build professional HTML email
    $message = '
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verifikasi Email - Dorve House</title>
    </head>
    <body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #f8f9fa;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa; padding: 40px 20px;">
            <tr>
                <td align="center">
                    <!-- Main Container -->
                    <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">

                        <!-- Header with Logo -->
                        <tr>
                            <td style="background: linear-gradient(135deg, #1A1A1A 0%, #2D2D2D 100%); padding: 40px 30px; text-align: center;">
                                <img src="' . $logo_url . '" alt="Dorve House" style="width: 60px; height: 60px; margin-bottom: 16px;">
                                <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700; letter-spacing: 2px; font-family: \'Playfair Display\', serif;">DORVE HOUSE</h1>
                                <p style="margin: 8px 0 0 0; color: rgba(255,255,255,0.8); font-size: 14px; letter-spacing: 1px;">PREMIUM FASHION</p>
                            </td>
                        </tr>

                        <!-- Content -->
                        <tr>
                            <td style="padding: 50px 40px;">
                                <h2 style="margin: 0 0 20px 0; color: #1A1A1A; font-size: 24px; font-weight: 700;">Selamat Datang, ' . htmlspecialchars($name) . '! 👋</h2>

                                <p style="margin: 0 0 16px 0; color: #4B5563; font-size: 15px; line-height: 1.7;">
                                    Terima kasih telah bergabung dengan <strong>Dorve House</strong>! Kami sangat senang menyambut Anda sebagai bagian dari keluarga fashion kami.
                                </p>

                                <p style="margin: 0 0 30px 0; color: #4B5563; font-size: 15px; line-height: 1.7;">
                                    Untuk menyelesaikan registrasi dan mengaktifkan akun Anda, silakan klik tombol verifikasi di bawah ini:
                                </p>

                                <!-- CTA Button -->
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td align="center" style="padding: 10px 0 30px 0;">
                                            <a href="' . $verification_link . '" style="display: inline-block; padding: 16px 48px; background: #1A1A1A; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 15px; letter-spacing: 0.5px;">
                                                ✓ VERIFIKASI EMAIL SAYA
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Alternative Link -->
                                <div style="background-color: #F9FAFB; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
                                    <p style="margin: 0 0 8px 0; color: #6B7280; font-size: 13px; font-weight: 600;">
                                        Atau salin link berikut ke browser:
                                    </p>
                                    <p style="margin: 0; color: #3B82F6; font-size: 12px; word-break: break-all; line-height: 1.6;">
                                        ' . $verification_link . '
                                    </p>
                                </div>

                                <!-- Warning Box -->
                                <div style="background-color: #FEF3C7; border-left: 4px solid #F59E0B; padding: 16px 20px; margin-bottom: 30px; border-radius: 4px;">
                                    <p style="margin: 0; color: #92400E; font-size: 14px; line-height: 1.6;">
                                        <strong>⏰ Penting:</strong> Link verifikasi ini berlaku selama <strong>24 jam</strong>. Setelah itu Anda perlu meminta link baru.
                                    </p>
                                </div>

                                <!-- Benefits -->
                                <h3 style="margin: 0 0 16px 0; color: #1A1A1A; font-size: 18px; font-weight: 700;">
                                    Setelah Verifikasi, Anda Akan Mendapatkan:
                                </h3>

                                <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                                    <tr>
                                        <td style="padding: 10px 0;">
                                            <span style="color: #10B981; font-size: 18px; margin-right: 8px;">✓</span>
                                            <span style="color: #4B5563; font-size: 14px;">Akun member aktif & full access</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0;">
                                            <span style="color: #10B981; font-size: 18px; margin-right: 8px;">✓</span>
                                            <span style="color: #4B5563; font-size: 14px;">Referral code untuk ajak teman & dapat komisi</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0;">
                                            <span style="color: #10B981; font-size: 18px; margin-right: 8px;">✓</span>
                                            <span style="color: #4B5563; font-size: 14px;">Wallet & tier system untuk rewards</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0;">
                                            <span style="color: #10B981; font-size: 18px; margin-right: 8px;">✓</span>
                                            <span style="color: #4B5563; font-size: 14px;">Akses belanja produk premium kami</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0;">
                                            <span style="color: #10B981; font-size: 18px; margin-right: 8px;">✓</span>
                                            <span style="color: #4B5563; font-size: 14px;">Notifikasi promo & koleksi terbaru</span>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Divider -->
                                <hr style="border: none; border-top: 1px solid #E5E7EB; margin: 30px 0;">

                                <!-- Footer Note -->
                                <p style="margin: 0; color: #9CA3AF; font-size: 13px; line-height: 1.6;">
                                    Jika Anda tidak mendaftar di Dorve House, abaikan email ini. Akun tidak akan dibuat tanpa verifikasi.
                                </p>
                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td style="background-color: #F9FAFB; padding: 30px 40px; text-align: center; border-top: 1px solid #E5E7EB;">
                                <p style="margin: 0 0 12px 0; color: #6B7280; font-size: 14px; font-weight: 600;">
                                    Butuh Bantuan?
                                </p>
                                <p style="margin: 0 0 20px 0; color: #9CA3AF; font-size: 13px;">
                                    Hubungi kami di <a href="mailto:' . SITE_EMAIL . '" style="color: #3B82F6; text-decoration: none;">' . SITE_EMAIL . '</a>
                                </p>

                                <p style="margin: 0 0 8px 0; color: #9CA3AF; font-size: 12px;">
                                    &copy; ' . date('Y') . ' Dorve House. All rights reserved.
                                </p>
                                <p style="margin: 0; color: #9CA3AF; font-size: 11px;">
                                    Email ini dikirim otomatis. Mohon tidak membalas.
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

    // Email headers for HTML
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=utf-8';
    $headers[] = 'From: Dorve House <' . SITE_EMAIL . '>';
    $headers[] = 'Reply-To: ' . SITE_EMAIL;
    $headers[] = 'X-Mailer: PHP/' . phpversion();

    // Send email
    // NOTE: For production, replace with SMTP service (SendGrid/Mailgun/etc)
    $sent = mail($email, $subject, $message, implode("\r\n", $headers));

    // Log email attempt
    error_log(date('[Y-m-d H:i:s]') . " Email sent to: $email - Status: " . ($sent ? 'SUCCESS' : 'FAILED'));

    return $sent;
}

/**
 * Send password reset email
 *
 * @param string $email User email
 * @param string $name User name
 * @param string $reset_link Full reset URL with token
 * @return bool Success status
 */
function sendPasswordResetEmail($email, $name, $reset_link) {
    $subject = '🔐 Reset Password Anda - Dorve House';

    $logo_url = SITE_URL . 'public/images/favicon.png';

    $message = '
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #f8f9fa;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa; padding: 40px 20px;">
            <tr>
                <td align="center">
                    <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">

                        <tr>
                            <td style="background: linear-gradient(135deg, #1A1A1A 0%, #2D2D2D 100%); padding: 40px 30px; text-align: center;">
                                <img src="' . $logo_url . '" alt="Dorve House" style="width: 60px; height: 60px; margin-bottom: 16px;">
                                <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700; letter-spacing: 2px;">DORVE HOUSE</h1>
                            </td>
                        </tr>

                        <tr>
                            <td style="padding: 50px 40px;">
                                <h2 style="margin: 0 0 20px 0; color: #1A1A1A; font-size: 24px; font-weight: 700;">Reset Password Anda</h2>

                                <p style="margin: 0 0 16px 0; color: #4B5563; font-size: 15px; line-height: 1.7;">
                                    Halo <strong>' . htmlspecialchars($name) . '</strong>,
                                </p>

                                <p style="margin: 0 0 30px 0; color: #4B5563; font-size: 15px; line-height: 1.7;">
                                    Kami menerima permintaan untuk reset password akun Anda. Klik tombol di bawah untuk membuat password baru:
                                </p>

                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td align="center" style="padding: 10px 0 30px 0;">
                                            <a href="' . $reset_link . '" style="display: inline-block; padding: 16px 48px; background: #EF4444; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 15px;">
                                                🔐 RESET PASSWORD
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                                <div style="background-color: #FEE2E2; border-left: 4px solid #EF4444; padding: 16px 20px; margin-bottom: 30px; border-radius: 4px;">
                                    <p style="margin: 0; color: #991B1B; font-size: 14px; line-height: 1.6;">
                                        <strong>⚠️ Keamanan:</strong> Link ini berlaku selama <strong>1 jam</strong>. Jika Anda tidak meminta reset password, abaikan email ini.
                                    </p>
                                </div>

                                <p style="margin: 0; color: #9CA3AF; font-size: 13px;">
                                    Link alternatif: <span style="color: #3B82F6; word-break: break-all; font-size: 11px;">' . $reset_link . '</span>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <td style="background-color: #F9FAFB; padding: 30px 40px; text-align: center; border-top: 1px solid #E5E7EB;">
                                <p style="margin: 0 0 8px 0; color: #9CA3AF; font-size: 12px;">
                                    &copy; ' . date('Y') . ' Dorve House. All rights reserved.
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

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=utf-8';
    $headers[] = 'From: Dorve House <' . SITE_EMAIL . '>';
    $headers[] = 'Reply-To: ' . SITE_EMAIL;

    $sent = mail($email, $subject, $message, implode("\r\n", $headers));

    error_log(date('[Y-m-d H:i:s]') . " Password reset email sent to: $email - Status: " . ($sent ? 'SUCCESS' : 'FAILED'));

    return $sent;
}

/**
 * Send order confirmation email
 *
 * @param string $email User email
 * @param string $name User name
 * @param string $order_number Order number
 * @param float $total_amount Total order amount
 * @return bool Success status
 */
function sendOrderConfirmationEmail($email, $name, $order_number, $total_amount) {
    $subject = '✓ Pesanan Diterima #' . $order_number . ' - Dorve House';

    $logo_url = SITE_URL . 'public/images/favicon.png';
    $order_url = SITE_URL . 'member/orders.php';

    $message = '
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f8f9fa;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa; padding: 40px 20px;">
            <tr>
                <td align="center">
                    <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden;">

                        <tr>
                            <td style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); padding: 40px 30px; text-align: center;">
                                <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">✓ Pesanan Diterima!</h1>
                            </td>
                        </tr>

                        <tr>
                            <td style="padding: 40px;">
                                <p style="margin: 0 0 20px 0; color: #4B5563; font-size: 15px;">
                                    Terima kasih <strong>' . htmlspecialchars($name) . '</strong>! Pesanan Anda telah kami terima.
                                </p>

                                <div style="background-color: #F9FAFB; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                                    <p style="margin: 0 0 8px 0; color: #6B7280; font-size: 13px;">Order Number</p>
                                    <p style="margin: 0; color: #1A1A1A; font-size: 20px; font-weight: 700;">' . $order_number . '</p>
                                </div>

                                <p style="margin: 0 0 20px 0; color: #4B5563; font-size: 15px;">
                                    Total: <strong>' . formatPrice($total_amount) . '</strong>
                                </p>

                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td align="center">
                                            <a href="' . $order_url . '" style="display: inline-block; padding: 14px 32px; background: #1A1A1A; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600;">
                                                Lihat Pesanan
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ';

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=utf-8';
    $headers[] = 'From: Dorve House <' . SITE_EMAIL . '>';

    return mail($email, $subject, $message, implode("\r\n", $headers));
}
