<?php

use App\Models\OneTimePassword;
use App\Models\User;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a 200 status for the home page', function () {
    get('/')->assertStatus(200);
});

it('sends an OTP after a successful login attempt', function () {
    $email = 'test@example.com';
    $password = 'password';

    User::create([
        'name' => 'Test User',
        'email' => $email,
        'password' => bcrypt($password),
        'email_verified_at' => now(),
    ]);

    // open login page
    $response = get('/login');
    $response->assertStatus(200);

    // submit login form
    $response = post('/login', [
        'email' => $email,
        'password' => $password,
    ]);

    $otp = OneTimePassword::where('type', OneTimePassword::TYPE_EMAIL)
        ->where('data', $email)
        ->first();

    expect($otp)->not()->toBeNull();
    $otpCode = $otp->code;

    // check if the user is redirected to the OTP verification page
    $response->assertStatus(200);
    $response->assertViewIs('auth.otp');

    // submit OTP verification form
    $response = post('/login/otp', [
        'type' => OneTimePassword::TYPE_EMAIL,
        'email' => $email,
        'otp' => $otpCode,
    ]);

    // check if the user is redirected to the home page
    $response->assertRedirect('/home');

});

it('sends an invalid OTP for 3 attempt', function () {
    $email = 'test@example.com';
    $password = 'password';

    User::create([
        'name' => 'Test User',
        'email' => $email,
        'password' => bcrypt($password),
        'email_verified_at' => now(),
    ]);

    // open login page
    $response = get('/login');
    $response->assertStatus(200);

    // submit login form
    $response = post('/login', [
        'email' => $email,
        'password' => $password,
    ]);

    $otp = OneTimePassword::where('type', OneTimePassword::TYPE_EMAIL)
        ->where('data', $email)
        ->first();

    expect($otp)->not()->toBeNull();
    $otpCode = $otp->code;

    // check if the user is redirected to the OTP verification page
    $response->assertStatus(200);
    $response->assertViewIs('auth.otp');

    // submit OTP verification form 1 fail
    $response = post('/login/otp', [
        'type' => OneTimePassword::TYPE_EMAIL,
        'email' => $email,
        'otp' => '111111',
    ]);
    $response->assertRedirect('/login');
    $response->assertSessionHasErrors('otp');

    $response = post('/login/otp', [
        'type' => OneTimePassword::TYPE_EMAIL,
        'email' => $email,
        'otp' => '222222',
    ]);
    $response->assertRedirect('/login');
    $response->assertSessionHasErrors('otp');

    $response = post('/login/otp', [
        'type' => OneTimePassword::TYPE_EMAIL,
        'email' => $email,
        'otp' => '333333',
    ]);
    $response->assertRedirect('/login');
    $response->assertSessionHasErrors('otp');

});

