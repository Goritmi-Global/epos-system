@component('mail::message')
<style>
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

<div class="logo-wrapper">
    <img src="https://alexharkness.com/wp-content/uploads/2020/06/logo-2.png" alt="EPOS-10X Global Logo">
</div>

<div class="brand">EPOS-10X Global</div>

# Reset Your Password

Hello {{ $notifiable->name }},

You are receiving this email because we received a password reset request for your account.

@component('mail::button', ['url' => $url, 'color' => 'primary'])
Reset Password
@endcomponent

If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:

[{{ $url }}]({{ $url }})

If you did not request a password reset, no further action is required.

Thanks,  
**EPOS-10X Global**
@endcomponent
