# Email Troubleshooting Guide

## Common Issues and Solutions

### 1. "Form submitted successfully" but no email sent

**Possible Causes:**
- Gmail App Password is expired or incorrect
- 2-factor authentication not enabled
- SMTP settings are incorrect
- Network/firewall blocking SMTP connections

**Solutions:**

#### A. Generate New Gmail App Password
1. Go to your Google Account settings
2. Navigate to Security > 2-Step Verification
3. Scroll down to "App passwords"
4. Generate a new app password for "Mail"
5. Update the `SMTP_PASSWORD` in `config/email_config.php`

#### B. Enable 2-Factor Authentication
1. Go to your Google Account settings
2. Navigate to Security > 2-Step Verification
3. Enable 2-Step Verification if not already enabled
4. Generate an App Password (see step A)

#### C. Test Email Configuration
1. Run `test_email.php` in your browser
2. Check the output for any error messages
3. Look at your server's error logs for detailed SMTP errors

#### D. Alternative: Use Gmail SMTP with OAuth2
If App Passwords don't work, you can set up OAuth2 authentication.

### 2. SMTP Connection Errors

**Error Messages:**
- "SMTP connect() failed"
- "Authentication failed"
- "Connection timed out"

**Solutions:**
1. Check your internet connection
2. Verify SMTP settings in `config/email_config.php`
3. Try using port 465 with SSL instead of port 587 with TLS
4. Check if your hosting provider blocks SMTP connections

### 3. Email Configuration File Issues

**Check these settings in `config/email_config.php`:**
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'LGU Liquidation System');
```

### 4. Debugging Steps

1. **Enable Debug Mode:**
   - Set `SMTP_DEBUG` to `2` in `config/email_config.php`
   - Check your server's error logs for detailed SMTP communication

2. **Test with Simple Script:**
   - Use `test_email.php` to test basic email functionality
   - Replace the test email address with your actual email

3. **Check File Permissions:**
   - Ensure PHP has read access to the configuration files
   - Check that PHPMailer files are accessible

### 5. Alternative Email Services

If Gmail continues to have issues, consider using:
- **SendGrid** (free tier available)
- **Mailgun** (free tier available)
- **Amazon SES** (very cheap)
- **Your hosting provider's SMTP server**

### 6. Quick Fix Checklist

- [ ] 2-Factor Authentication enabled on Gmail
- [ ] App Password generated and updated in config
- [ ] SMTP settings correct in `email_config.php`
- [ ] PHPMailer files present in `PHPMailer/src/`
- [ ] File paths correct in `EmailSender.php`
- [ ] Server allows outbound SMTP connections
- [ ] Test email script runs without errors

### 7. Emergency Workaround

If emails still don't work, you can temporarily disable email functionality by modifying the `sendReminder` function in `EmailSender.php` to always return true and log the attempt instead of actually sending emails.

```php
public function sendReminder($to, $subject, $message) {
    // Log the email attempt
    error_log("Email would be sent to: $to, Subject: $subject");
    return true; // Always return success for now
}
```

This will allow the system to continue working while you troubleshoot the email issues. 