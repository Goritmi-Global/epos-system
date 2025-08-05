@component('mail::message')
# Hello {{ $user->name }},

Thanks for registering! Below are your account details:

- **Email**: {{ $user->email }}
- **Password**: {{ $password }}
- **PIN**: {{ $pin }}

Click the button below to verify your account:

@component('mail::button', ['url' => route('verify.account', ['id' => $user->id])])
Verify Account
@endcomponent

Thanks,  
{{ config('app.name') }}
@endcomponent
