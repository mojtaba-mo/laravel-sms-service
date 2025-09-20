<?php

namespace OtpAuth;

use OtpAuth\Models\Otp;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use function config;

class SmsService
{
    protected string $username;
    protected string $password;
    protected string $endpoint;

    public function __construct()
    {
        $config = config('sms.melipayamak');
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->endpoint = $config['endpoint'];
    }

    /**
     * Send SMS using Melipayamak SendByBaseNumber method
     * 
     * @param array $text Array of variables for the predefined text template
     * @param string $to Mobile number (only one number allowed)
     * @param int $bodyId Predefined text template ID approved by system admin
     * @return string Return value as per API documentation
     */
    public function sendByBaseNumber(array $text, string $to, int $bodyId): string
    {
        try {
            // Initialize SOAP client
            ini_set("soap.wsdl_cache_enabled", "0");
            $sms = new \SoapClient($this->endpoint, ["encoding" => "UTF-8"]);
            
            // Prepare data for API call
            $data = [
                "username" => $this->username,
                "password" => $this->password,
                "text" => $text,
                "to" => $to,
                "bodyId" => $bodyId
            ];
            
            // Call the API
            $result = $sms->SendByBaseNumber($data)->SendByBaseNumberResult;
            
            // Log the result for debugging
            Log::info('SMS SendByBaseNumber Result', [
                'to' => $to,
                'bodyId' => $bodyId,
                'result' => $result
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('SMS SendByBaseNumber Error: ' . $e->getMessage(), [
                'to' => $to,
                'bodyId' => $bodyId,
                'error' => $e->getMessage()
            ]);
            
            return '0'; // Return error code for invalid username/password
        }
    }

    /**
     * Get return value description based on API response
     * 
     * @param string $returnValue
     * @return string
     */
    public function getReturnValueDescription(string $returnValue): string
    {
        $descriptions = [
            'recId' => 'ارسال موفق پیامک',
            '-10' => 'در میان متغییر های ارسالی ، لینک وجود دارد',
            '-7' => 'خطایی در شماره فرستنده رخ داده است با پشتیبانی تماس بگیرید',
            '-6' => 'خطای داخلی رخ داده است با پشتیبانی تماس بگیرید',
            '-5' => 'متن ارسالی باتوجه به متغیرهای مشخص شده در متن پیشفرض همخوانی ندارد',
            '-4' => 'کد متن ارسالی صحیح نمی‌باشد و یا توسط مدیر سامانه تأیید نشده است',
            '-3' => 'خط ارسالی در سیستم تعریف نشده است، با پشتیبانی سامانه تماس بگیرید',
            '-2' => 'محدودیت تعداد شماره، محدودیت هربار ارسال یک شماره موبایل می‌باشد',
            '-1' => 'دسترسی برای استفاده از این وبسرویس غیرفعال است. با پشتیبانی تماس بگیرید',
            '0' => 'نام کاربری یا رمزعبور صحیح نمی‌باشد',
            '2' => 'اعتبار کافی نمی‌باشد',
            '6' => 'سامانه درحال بروزرسانی می‌باشد',
            '7' => 'متن حاوی کلمه فیلتر شده می‌باشد، با واحد اداری تماس بگیرید',
            '10' => 'کاربر موردنظر فعال نمی‌باشد',
            '11' => 'ارسال نشده',
            '12' => 'مدارک کاربر کامل نمی‌باشد',
            '16' => 'شماره گیرنده ای یافت نشد',
            '17' => 'متن پیامک خالی می باشد'
        ];

        // Check if it's a successful recId (more than 15 digits)
        if (is_numeric($returnValue) && strlen($returnValue) > 15) {
            return $descriptions['recId'];
        }

        return $descriptions[$returnValue] ?? 'خطای نامشخص';
    }

    /**
     * Check if the return value indicates successful sending
     * 
     * @param string $returnValue
     * @return bool
     */
    public function isSuccess(string $returnValue): bool
    {
        // Successful if it's a recId (more than 15 digits)
        return is_numeric($returnValue) && strlen($returnValue) > 15;
    }

    /**
     * Generate and send OTP code
     * 
     * @param string $mobile Mobile number
     * @param int $bodyId Template ID for SMS
     * @return array
     */
    public function sendOtp(string $mobile, int $bodyId): array
    {
        $dailyLimit = (int) config('sms.daily_limit', 3);
        $minInterval = (int) config('sms.min_interval_seconds', 120);
        $ttl = (int) config('sms.ttl_minutes', 2);

        // Check daily limit
        $countToday = Otp::where('mobile', $mobile)->whereDate('created_at', Carbon::today())->count();
        if ($countToday >= $dailyLimit) {
            return ['success' => false, 'message' => 'بیش از حد مجاز درخواست داده‌اید'];
        }

        // Check minimum interval
        $last = Otp::where('mobile', $mobile)->latest()->first();
        if ($last && $last->created_at->diffInSeconds(now()) < $minInterval) {
            return ['success' => false, 'message' => 'لطفا کمی صبر کنید'];
        }

        // Generate random OTP code
        $code = random_int(1000, 9999);

        try {
            DB::beginTransaction();

            // Create OTP record
            $otp = Otp::create([
                'mobile' => $mobile,
                'code' => (string) $code,
                'expires_at' => Carbon::now()->addMinutes($ttl),
                'used' => false,
            ]);

            // Send SMS using SendByBaseNumber
            $result = $this->sendByBaseNumber(
                [(string) $code], // Pass code as template variable
                $mobile,
                $bodyId
            );

            if (!$this->isSuccess($result)) {
                DB::rollBack();
                return [
                    'success' => false, 
                    'message' => 'خطا در ارسال پیامک: ' . $this->getReturnValueDescription($result)
                ];
            }

            DB::commit();

            return [
                'success' => true, 
                'message' => 'کد تایید ارسال شد',
                'code' => $code, // Return the generated code
                'recId' => $result
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('OTP Send Failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'خطا در ارسال کد تایید'];
        }
    }

    /**
     * Verify OTP code
     * 
     * @param string $mobile Mobile number
     * @param string $code OTP code to verify
     * @return array
     */
    public function verifyOtp(string $mobile, string $code): array
    {
        $otp = Otp::where('mobile', $mobile)
                  ->where('code', $code)
                  ->latest()
                  ->first();

        if (!$otp) {
            return ['success' => false, 'message' => 'کد تایید نامعتبر است'];
        }

        if ($otp->used) {
            return ['success' => false, 'message' => 'کد تایید قبلاً استفاده شده است'];
        }

        if ($otp->isExpired()) {
            return ['success' => false, 'message' => 'کد تایید منقضی شده است'];
        }

        // Mark as used
        $otp->update(['used' => true]);

        return ['success' => true, 'message' => 'کد تایید صحیح است'];
    }

    /**
     * Generate random OTP code without sending SMS
     * 
     * @param int $length Code length (default 4)
     * @return string
     */
    public function generateOtpCode(int $length = 4): string
    {
        $min = pow(10, $length - 1);
        $max = pow(10, $length) - 1;
        return (string) random_int($min, $max);
    }
}
