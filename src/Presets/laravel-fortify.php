<?php

declare(strict_types=1);

return [
    [
        'route' => 'login',
        'action' => 'Laravel\Fortify\Http\Controllers\AuthenticatedSessionController@create',
        'method' => 'GET',
        'mode' => 'read',
    ],
    [
        'route' => 'login.store',
        'action' => 'Laravel\Fortify\Http\Controllers\AuthenticatedSessionController@store',
        'method' => 'POST',
        'mode' => 'write',
    ],
    [
        'route' => 'logout',
        'action' => 'Laravel\Fortify\Http\Controllers\AuthenticatedSessionController@destroy',
        'method' => 'POST',
        'mode' => 'write',
    ],
    [
        'route' => 'password.request',
        'action' => 'Laravel\Fortify\Http\Controllers\PasswordResetLinkController@create',
        'method' => 'GET',
        'mode' => 'read',
    ],
    [
        'route' => 'password.reset',
        'action' => 'Laravel\Fortify\Http\Controllers\NewPasswordController@create',
        'method' => 'GET',
        'mode' => 'read',
    ],
    [
        'route' => 'password.email',
        'action' => 'Laravel\Fortify\Http\Controllers\PasswordResetLinkController@store',
        'method' => 'POST',
        'mode' => 'write',
    ],
    [
        'route' => 'password.update',
        'action' => 'Laravel\Fortify\Http\Controllers\NewPasswordController@store',
        'method' => 'POST',
        'mode' => 'write',
    ],
    [
        'route' => 'register',
        'action' => 'Laravel\Fortify\Http\Controllers\RegisteredUserController@create',
        'method' => 'GET',
        'mode' => 'read',
    ],
    [
        'route' => 'register.store',
        'action' => 'Laravel\Fortify\Http\Controllers\RegisteredUserController@store',
        'method' => 'POST',
        'mode' => 'write',
    ],
    [
        'route' => 'verification.notice',
        'action' => 'Laravel\Fortify\Http\Controllers\EmailVerificationPromptController@__invoke',
        'method' => 'GET',
        'mode' => 'read',
    ],
    [
        'route' => 'verification.verify',
        'action' => 'Laravel\Fortify\Http\Controllers\VerifyEmailController@__invoke',
        'method' => 'GET',
        'mode' => 'write', // The email verification link marks the user's email as verified in the database.
    ],
    [
        'route' => 'verification.send',
        'action' => 'Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController@store',
        'method' => 'POST',
        'mode' => 'write',
    ],
    [
        'route' => 'user-profile-information.update',
        'action' => 'Laravel\Fortify\Http\Controllers\ProfileInformationController@update',
        'method' => 'PUT',
        'mode' => 'write',
    ],
    [
        'route' => 'user-password.update',
        'action' => 'Laravel\Fortify\Http\Controllers\PasswordController@update',
        'method' => 'PUT',
        'mode' => 'write',
    ],
    [
        'route' => 'password.confirm',
        'action' => 'Laravel\Fortify\Http\Controllers\ConfirmablePasswordController@show',
        'method' => 'GET',
        'mode' => 'read',
    ],
    [
        'route' => 'password.confirmation',
        'action' => 'Laravel\Fortify\Http\Controllers\ConfirmedPasswordStatusController@show',
        'method' => 'GET',
        'mode' => 'read',
    ],
    [
        'route' => 'password.confirm.store',
        'action' => 'Laravel\Fortify\Http\Controllers\ConfirmablePasswordController@store',
        'method' => 'POST',
        'mode' => 'write',
    ],
    [
        'route' => 'two-factor.login',
        'action' => 'Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController@create',
        'method' => 'GET',
        'mode' => 'read',
    ],
    [
        'route' => 'two-factor.login.store',
        'action' => 'Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController@store',
        'method' => 'POST',
        'mode' => 'write',
    ],
    [
        'route' => 'two-factor.enable',
        'action' => 'Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController@store',
        'method' => 'POST',
        'mode' => 'write',
    ],
    [
        'route' => 'two-factor.confirm',
        'action' => 'Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController@store',
        'method' => 'POST',
        'mode' => 'write',
    ],
    [
        'route' => 'two-factor.disable',
        'action' => 'Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController@destroy',
        'method' => 'DELETE',
        'mode' => 'write',
    ],
    [
        'route' => 'two-factor.qr-code',
        'action' => 'Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController@show',
        'method' => 'GET',
        'mode' => 'read',
    ],
    [
        'route' => 'two-factor.secret-key',
        'action' => 'Laravel\Fortify\Http\Controllers\TwoFactorSecretKeyController@show',
        'method' => 'GET',
        'mode' => 'read',
    ],
    [
        'route' => 'two-factor.recovery-codes',
        'action' => 'Laravel\Fortify\Http\Controllers\RecoveryCodeController@index',
        'method' => 'GET',
        'mode' => 'read',
    ],
    [
        'route' => 'two-factor.regenerate-recovery-codes',
        'action' => 'Laravel\Fortify\Http\Controllers\RecoveryCodeController@store',
        'method' => 'POST',
        'mode' => 'write',
    ],
];
