<?php

namespace App\Http\Requests\Auth;

use App\Models\OneTimePassword;
use App\Traits\FormPreparation;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckOtpRequest extends FormRequest
{
    use FormPreparation;

    protected int $maxAttempts = 3;

    public function rules()
    {
        return [
            'type' => [
                'required',
                Rule::in([OneTimePassword::TYPE_EMAIL]),
            ],
            'email' => [
                Rule::when(($this['type'] === OneTimePassword::TYPE_EMAIL), 'required', 'nullable'),
                'email',
                Rule::exists('one_time_passwords', 'data'),
            ],
            'otp' => ['required', 'string', 'min:6', 'max:6', 'regex:/^[0-9]+$/'],
        ];
    }

    protected function prepareForValidation()
    {
        if ($this['type'] === OneTimePassword::TYPE_EMAIL) {
            if ($this['email']) {
                $this['email'] = $this->filterGmailValidation($this['email']);
            }
        }
    }

    protected function passedValidation()
    {
        $field = $this['type'];
        if ($this['type'] !== OneTimePassword::TYPE_EMAIL) {
            $field = 'phone';
        }

        $otp = OneTimePassword::where('type', $this['type'])
            ->where('data', $this[$field])
            ->first();

        if ($otp['verified_at'] === null) {
            $invalid_otp = false;
            $message = null;
            if ($otp['code'] !== $this['otp']) {
                $invalid_otp = true;
                $message = 'Invalid OTP Code';
            }

            $valid_until = $otp['created_at']->addMinutes(30);
            if (now() > $valid_until) {
                $invalid_otp = true;
                $message = 'OTP Code expired';
            }

            if ($invalid_otp) {
                $this->limiter()->hit($this->throttleKey($this));
                $nb_attempts_left = $this->limiter()->retriesLeft($this->throttleKey($this), $this->maxAttempts);
                if ($nb_attempts_left == 0) {
                    $otp['created_at'] = now()->subMinutes(31);
                    $otp->save();
                }
                throw ValidationException::withMessages([
                    'otp' => [
                        $message,
                    ],
                ]);
            }
        }
    }

    protected function limiter()
    {
        return app(RateLimiter::class);
    }

    protected function throttleKey($request): string
    {
        return $request->getBaseUrl();
    }
}
