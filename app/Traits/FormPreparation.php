<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait FormPreparation
{
    private function filterGmailValidation($email): string
    {
        $validatedEmail = Str::lower($email);

        if (config('app.env') === 'production') {
            $emails = explode('@', $email);
            if (count($emails) > 1 && $emails[1] === 'gmail.com') {
                $firstEmail = explode('+', $emails[0]);
                $validEmail = str_replace('.', '', $firstEmail[0]);
                $validatedEmail = $validEmail . '@' . $emails[1];
            }
        }

        $domain = explode('@', $validatedEmail)[1];

//        if ($this->isForbiddenDomain($domain)) {
//            throw ValidationException::withMessages([
//                'email' => [
//                    'Business email only, Personal email not allowed.',
//                ],
//            ]);
//        }
        if ($this->isDisposableDomain($domain)) {
            throw ValidationException::withMessages([
                'email' => [
                    'Disposable email not allowed.',
                ],
            ]);
        }

        return $validatedEmail;
    }

//    private function filterPhoneValidation($phoneCountry, $phone): string
//    {
//        return PhoneNumber::make($phone, $phoneCountry)->formatE164();
//    }

    private function isForbiddenDomain($domain): bool
    {
        $forbiddenDomains = file(__DIR__ . '/../../database/forbidden-domain.conf', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return in_array($domain, $forbiddenDomains);
    }

    private function isDisposableDomain($domain): bool
    {
        $disposableDomain = file(__DIR__ . '/../../database/disposable-domain.conf', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return in_array($domain, $disposableDomain);
    }

    private function validateStrongPassword($password, $field)
    {
        if (!preg_match('/^.*(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\d\x])(?=.*[[:punct:]]).*$/', $password)) {
            throw ValidationException::withMessages([
                $field => [
                    'Password must contain uppercase, lowercase, number and special character',
                ],
            ]);
        }
        return $password;
    }
}
