# Laravel SMS & OTP Service

A Laravel package for sending SMS and managing OTP codes using Melipayamak API's `SendByBaseNumber` method.

## Installation

```bash
composer require mojtaba-mo/laravel-sms-service
```

## Configuration

Publish the configuration file and migration:

```bash
php artisan vendor:publish --tag=sms-config
php artisan vendor:publish --tag=sms-migrations
php artisan migrate
```

Add your Melipayamak credentials and OTP settings to your `.env` file:

```env
MELIPAYAMAK_USERNAME=your_username
MELIPAYAMAK_PASSWORD=your_password
MELIPAYAMAK_ENDPOINT=http://api.payamak-panel.com/post/Send.asmx?wsdl

# OTP Settings (optional)
OTP_TTL_MINUTES=2
OTP_DAILY_LIMIT=3
OTP_MIN_INTERVAL_SECONDS=120
```

## Usage

### Basic SMS Usage

```php
use OtpAuth\SmsService;

// Inject the service
public function __construct(SmsService $smsService)
{
    $this->smsService = $smsService;
}

// Send SMS
$result = $this->smsService->sendByBaseNumber(
    ['arg1', 'arg2'], // Array of variables for the predefined text template
    '09123456789',    // Mobile number (only one number allowed)
    12345             // Predefined text template ID
);

// Check if successful
if ($this->smsService->isSuccess($result)) {
    echo "SMS sent successfully!";
} else {
    echo "Error: " . $this->smsService->getReturnValueDescription($result);
}
```

### OTP Usage

```php
// Send OTP
$otpResult = $this->smsService->sendOtp(
    '09123456789', // Mobile number
    12345          // Template ID
);

if ($otpResult['success']) {
    echo "OTP sent: " . $otpResult['code']; // Generated code
    echo "SMS RecId: " . $otpResult['recId'];
} else {
    echo "Error: " . $otpResult['message'];
}

// Verify OTP
$verifyResult = $this->smsService->verifyOtp(
    '09123456789', // Mobile number
    '1234'         // User entered code
);

if ($verifyResult['success']) {
    echo "OTP verified successfully!";
} else {
    echo "Error: " . $verifyResult['message'];
}

// Generate OTP code without sending SMS
$code = $this->smsService->generateOtpCode(4); // 4-digit code
```

### Using Facade (Optional)

You can also use the service as a facade by adding it to your `config/app.php`:

```php
'aliases' => [
    // ... other aliases
    'SmsService' => OtpAuth\SmsService::class,
],
```

Then use it like:

```php
use SmsService;

$result = SmsService::sendByBaseNumber(['arg1', 'arg2'], '09123456789', 12345);
```

## API Response Codes

The service returns various response codes as per Melipayamak API documentation:

- **recId** (15+ digits): SMS sent successfully
- **-10**: Link found in variables
- **-7**: Error in sender number
- **-6**: Internal error
- **-5**: Text doesn't match predefined template variables
- **-4**: Invalid or unapproved template ID
- **-3**: Sender line not defined in system
- **-2**: Number limit exceeded (only one number allowed per request)
- **-1**: Web service access disabled
- **0**: Invalid username or password
- **2**: Insufficient credit
- **6**: System under maintenance
- **7**: Text contains filtered words
- **10**: User not active
- **11**: Not sent
- **12**: User documents incomplete
- **16**: Recipient number not found
- **17**: SMS text is empty

## Helper Methods

- `isSuccess($returnValue)`: Check if the response indicates successful sending
- `getReturnValueDescription($returnValue)`: Get human-readable description of the response code

## Requirements

- PHP >= 8.0
- Laravel >= 12.0
- SOAP extension enabled

## License

MIT