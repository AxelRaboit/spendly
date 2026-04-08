<x-mail::message>
# {{ __('mail.accountDeleted.greeting', ['name' => $userName]) }}

{{ __('mail.accountDeleted.body', ['app' => $appName]) }}

{{ __('mail.accountDeleted.closing') }}

{{ __('mail.accountDeleted.signature', ['app' => $appName]) }}
</x-mail::message>
