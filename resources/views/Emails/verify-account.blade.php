@component('mail::message')
<style>
    .otp-box {
        font-size: 28px;
        letter-spacing: 12px;
        text-align: center;
        font-weight: bold;
        margin: 20px 0;
    }
    .brand {
        font-size: 16px;
        text-align: center;
        margin-bottom: 10px;
        color: #004AAD;
        font-weight: bold;
    }
    .logo-wrapper {
        text-align: center;
        margin-bottom: 20px;
    }
    .logo-wrapper img {
        max-width: 160px;
        height: auto;
    }
</style>

<!-- Logo Section -->
<div class="logo-wrapper">
    <img src="https://alexharkness.com/wp-content/uploads/2020/06/logo-2.png" alt="EPOS-10X Global Logo">
</div>

<!-- Brand -->
<div class="brand">EPOS-10X Global</div>

# Confirm it's you

Hello {{ $user->name }},  
Please confirm your email to activate your account.

<!-- Confirm Button -->
@component('mail::button', ['url' => route('verify.account', ['id' => $user->id]), 'color' => 'primary'])
Confirm email
@endcomponent


{{-- Only show OTP if available --}}
@if(!empty($otp))
Or, you can simply copy and paste this code into the app:

<!-- OTP Display -->
<div class="otp-box">
    {{ implode(' ', str_split($otp)) }}
</div>
@endif

If you didn't request this, you can safely ignore this email.

Thanks,  
**EPOS-10X Global**
@endcomponent
