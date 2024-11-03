<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CheckOtpRequest;
use App\Models\OneTimePassword;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Traits\OtpService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;
    use OtpService;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request)
    {
        $this->validateLogin($request);

        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }

            $email = $request['email'];
            auth()->logout();
            $this->resend('email', $email);

            return view('auth.otp', compact('email'));
        }

        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    public function verifyOtp(CheckOtpRequest $request)
    {
        $id = $this->verify('email', $request['email'])['data'];

        $otp = OneTimePassword::find($id);
        $user = User::where('email', $otp['data'])->first();
        if($user){
            auth()->login($user);
            return redirect('/home');
        }
        return  redirect()->back()->withErrors('Account not found');
    }
}
