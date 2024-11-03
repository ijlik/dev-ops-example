<?php

namespace App\Traits;

use App\Models\OneTimePassword;
use App\Models\User;
use App\Notifications\UserOneTimePassword;
use Illuminate\Validation\ValidationException;

trait OtpService
{
    private function resend($type, $data){
        $field = $type;
        if ($type !== OneTimePassword::TYPE_EMAIL) {
            $field = 'phone';
        }
        $user = User::where($field, $data)->first();
        if(!$user){
            throw ValidationException::withMessages([
                $field => [
                    'Invalid credential',
                ],
            ]);
        }

        $otp = OneTimePassword::where('type', $type)
            ->where('data', $data)
            ->first();

        if ($otp !== null) {
            if (now()->diffInSeconds($otp['created_at']) < 60) {
                throw ValidationException::withMessages([
                    $field => [
                        'To many attempt sending otp. Try again later',
                    ],
                ]);
            }

            if ($otp['created_at']->addMinutes(30) > now()) {
                $otp['created_at'] = now();
                $otp->save();
                // Resend Previous OTP
                if ($otp['type'] === OneTimePassword::TYPE_EMAIL) {
                    $user->notify(new UserOneTimePassword($otp['code']));
                } else {
                    // Resend another otp
                }

                return [
                    'data' => [
                        'type' => $otp['type'],
                        $field => $otp['data'],
                    ]
                ];
            }

            $otp->delete();
        }

        $otpCode = $user->hasWhitelistOtp() ? $user['whitelist_otp'] : rand(100000, 999999);

        $newOtp = new OneTimePassword;
        $newOtp['type'] = $type;
        $newOtp['data'] = $data;
        $newOtp['code'] = $otpCode;
        $newOtp->save();

        if ($type === OneTimePassword::TYPE_EMAIL) {
            $user->notify(new UserOneTimePassword($newOtp['code']));
        }

        return [
            'data' => [
                'type' => $newOtp['type'],
                $field => $newOtp['data'],
            ]
        ];
    }

    public function verify($type, $data)
    {
        $otp = OneTimePassword::where('type', $type)
            ->where('data', $data)
            ->first();

        if ($otp['verified_at'] === null) {
            $otp['verified_at'] = now();
        }
        $otp->save();

        return [
            'data' => $otp['id']
        ];
    }
}
