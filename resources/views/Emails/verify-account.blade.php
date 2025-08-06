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

<!-- Confirm Button with Custom Color -->
@component('mail::button', ['url' => route('verify.account', ['id' => $user->id]), 'color' => 'primary'])
<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn btn-primary">
<tr>
<td align="center" bgcolor="#004AAD" style="border-radius: 5px;">
    <a href="{{ route('verify.account', ['id' => $user->id]) }}" target="_blank" style="display: inline-block; padding: 12px 24px; color: #ffffff; background-color: #004AAD; border-radius: 5px; text-decoration: none; font-weight: bold;">
        Confirm email
    </a>
</td>
</tr>
</table>
@endcomponent

Or, you can simply copy and paste this code into the app:

<!-- OTP Display -->
<div class="otp-box">
    {{ implode(' ', str_split($otp)) }}
</div>

If you didn’t request this, you can safely ignore this email.

Thanks,  
**EPOS-10X Global**
@endcomponent
