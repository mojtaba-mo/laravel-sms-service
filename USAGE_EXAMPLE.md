# Usage Example

## Basic SMS Usage in Controller

```php
<?php

namespace App\Http\Controllers;

use OtpAuth\SmsService;

class SmsController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function sendSms()
    {
        // Send SMS using predefined template
        $result = $this->smsService->sendByBaseNumber(
            ['کد تایید', '1234'], // Variables for template
            '09123456789',        // Mobile number
            12345                 // Template ID
        );

        if ($this->smsService->isSuccess($result)) {
            return response()->json([
                'success' => true,
                'message' => 'SMS sent successfully',
                'recId' => $result
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => $this->smsService->getReturnValueDescription($result),
                'error_code' => $result
            ]);
        }
    }
}
```

## OTP Usage in Controller

```php
<?php

namespace App\Http\Controllers;

use OtpAuth\SmsService;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string',
            'template_id' => 'required|integer'
        ]);

        $result = $this->smsService->sendOtp(
            $request->mobile,
            $request->template_id
        );

        return response()->json($result);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string',
            'code' => 'required|string'
        ]);

        $result = $this->smsService->verifyOtp(
            $request->mobile,
            $request->code
        );

        return response()->json($result);
    }

    public function generateCode()
    {
        $code = $this->smsService->generateOtpCode(4);
        
        return response()->json([
            'success' => true,
            'code' => $code
        ]);
    }
}
```
```

## Using as Facade

Add to `config/app.php`:

```php
'aliases' => [
    // ... other aliases
    'SmsService' => OtpAuth\SmsService::class,
],
```

Then use:

```php
use SmsService;

$result = SmsService::sendByBaseNumber(['arg1', 'arg2'], '09123456789', 12345);
```

## Environment Variables

Add to your `.env` file:

```env
MELIPAYAMAK_USERNAME=your_username
MELIPAYAMAK_PASSWORD=your_password
MELIPAYAMAK_ENDPOINT=http://api.payamak-panel.com/post/Send.asmx?wsdl
```
