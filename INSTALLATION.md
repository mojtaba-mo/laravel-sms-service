# نصب پکیج Laravel SMS Service

## نسخه‌های مختلف

### 1. نسخه کامل (Full Version)
شامل تمام قابلیت‌ها + OTP با دیتابیس

```bash
composer require mojtaba-mo/laravel-sms-service
```

**وابستگی‌ها:**
- `illuminate/support`
- `illuminate/database` (برای OTP)
- `illuminate/http` (اختیاری)

### 2. نسخه سبک (Light Version)
فقط برای ارسال SMS بدون دیتابیس

```bash
composer require mojtaba-mo/laravel-sms-service-light
```

**وابستگی‌ها:**
- فقط `illuminate/support`

## حجم پکیج

- **نسخه کامل**: ~2MB
- **نسخه سبک**: ~500KB

## استفاده

### نسخه کامل
```php
use OtpAuth\SmsService;

$sms = new SmsService();
$result = $sms->sendOtp('09123456789', 12345);
```

### نسخه سبک
```php
use OtpAuth\LightSmsService;

$sms = new LightSmsService();
$result = $sms->sendByBaseNumber(['کد شما: 1234'], '09123456789', 12345);
```
