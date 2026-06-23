<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Password reset email body (inline CSS for mail-client compatibility).
 * Vars: $name (string), $reset_url (string), $ttl_minutes (int)
 */
$name = isset($name) && $name !== '' ? $name : 'there';
$reset_url = isset($reset_url) ? $reset_url : '#';
$ttl_minutes = isset($ttl_minutes) ? (int) $ttl_minutes : 30;
$brand = '#8666BC';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset your password</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f7; font-family:Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f7; padding:24px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; background-color:#ffffff; border-radius:12px; overflow:hidden;">
          <tr>
            <td style="background-color:<?= $brand ?>; padding:24px; text-align:center;">
              <span style="color:#ffffff; font-size:20px; font-weight:bold; letter-spacing:0.5px;">Forbes Skin</span>
            </td>
          </tr>
          <tr>
            <td style="padding:32px 28px 8px 28px; color:#2b2b2b;">
              <p style="margin:0 0 16px 0; font-size:16px;">Hi <?= html_escape($name) ?>,</p>
              <p style="margin:0 0 16px 0; font-size:14px; line-height:1.6; color:#555;">
                We received a request to reset your password. Click the button below to choose a new one.
                This link expires in <?= $ttl_minutes ?> minutes and can only be used once.
              </p>
            </td>
          </tr>
          <tr>
            <td style="padding:8px 28px 28px 28px; text-align:center;">
              <a href="<?= html_escape($reset_url) ?>"
                 style="display:inline-block; background-color:<?= $brand ?>; color:#ffffff; text-decoration:none; font-size:15px; font-weight:bold; padding:13px 28px; border-radius:8px;">
                Reset Password
              </a>
            </td>
          </tr>
          <tr>
            <td style="padding:0 28px 28px 28px; color:#888; font-size:12px; line-height:1.6;">
              <p style="margin:0 0 8px 0;">If the button doesn't work, copy and paste this link into your browser:</p>
              <p style="margin:0 0 16px 0; word-break:break-all;"><a href="<?= html_escape($reset_url) ?>" style="color:<?= $brand ?>;"><?= html_escape($reset_url) ?></a></p>
              <p style="margin:0;">If you didn't request a password reset, you can safely ignore this email — your password won't change.</p>
            </td>
          </tr>
        </table>
        <p style="max-width:480px; color:#aaa; font-size:11px; margin:16px auto 0 auto; text-align:center;">&copy; <?= date('Y') ?> Forbes Skin. This is an automated message, please do not reply.</p>
      </td>
    </tr>
  </table>
</body>
</html>
