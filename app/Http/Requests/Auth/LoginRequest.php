<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Mail\VerifyAccountMail;
use Illuminate\Support\Facades\Mail;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required_without:pin',
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $fail('The email field must be a valid email address.');
                    }
                }
            ],
            'password' => ['required_with:email'],
            'pin' => [
                'nullable',
                'required_without:email',
                function ($attribute, $value, $fail) {
                    if (!$this->filled('email') && (!is_numeric($value) || strlen((string) $value) !== 4)) {
                        $fail('PIN must be exactly 4 digits.');
                    }
                }
            ],
        ];
    }


    public function messages(): array
    {
        return [
            'email.required_without' => 'Email is required unless logging in with PIN.',
            'password.required_with' => 'Password is required when using email.',
            'pin.required_without' => 'PIN is required unless logging in with email.',
            'pin.digits' => 'PIN must be exactly 4 digits.',
        ];
    }

    public function authenticate()
    {
        $this->ensureIsNotRateLimited();

        // ✅ Login with email/password
        if ($this->filled('email')) {
            $credentials = $this->only('email', 'password');

            if (!Auth::attempt($credentials, $this->boolean('remember'))) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'email' => __('The provided credentials are incorrect.'),
                ]);
            }

            $user = Auth::user();

            // ✅ Check if account is verified
            if (is_null($user->email_verified_at)) {
                Auth::logout();
                RateLimiter::hit($this->throttleKey());

                $otp = rand(100000, 999999);
                $user->verifying_otp = $otp;
                $user->save();

                
                Mail::to($user->email)->send(new VerifyAccountMail($user,$otp));

                throw ValidationException::withMessages([
                    'unverified' => 'Account not verified. A new OTP has been sent to your email.',
                    'email_address' => $user->email,
                ]);
            }

            RateLimiter::clear($this->throttleKey());
            return;
        }

        // ✅ Login with PIN
        if ($this->filled('pin')) {
            $user = \App\Models\User::whereNotNull('pin')->get()->first(function ($user) {
                return \Hash::check($this->pin, $user->pin);
            });

            if (!$user) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'pin' => __('The provided PIN is incorrect.'),
                ]);
            }

            // ✅ Check if account is verified
            if (is_null($user->email_verified_at)) {
                RateLimiter::hit($this->throttleKey());

                $otp = rand(100000, 999999);
                $user->verifying_otp = $otp;
                $user->save();

                // Mail::to($user->email)->send(new OtpVerificationMail($user, $otp));
                Mail::to($user->email)->send(new VerifyAccountMail($user,$otp));

                throw ValidationException::withMessages([
                    'unverified' => 'Account not verified. A new OTP has been sent to your email.',
                    'email_address' => $user->email,
                ]);
            }

            Auth::login($user, $this->boolean('remember'));
            RateLimiter::clear($this->throttleKey());
            return;
        }

        throw ValidationException::withMessages([
            'email' => 'Please enter email/password or PIN to login.',
        ]);
    }


    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        $key = $this->filled('email') ? $this->string('email') : $this->string('pin');
        return Str::transliterate(Str::lower($key) . '|' . $this->ip());
    }
}

