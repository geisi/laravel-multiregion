<?php

declare(strict_types=1);

return [
    [
        'route' => 'passkey.login-options',
        'action' => 'Laravel\Passkeys\Http\Controllers\PasskeyLoginController@index',
        'method' => 'GET',
        'mode' => 'read',
    ],
    [
        'route' => 'passkey.login',
        'action' => 'Laravel\Passkeys\Http\Controllers\PasskeyLoginController@store',
        'method' => 'POST',
        'mode' => 'write',
    ],
    [
        'route' => 'passkey.confirm-options',
        'action' => 'Laravel\Passkeys\Http\Controllers\PasskeyConfirmationController@index',
        'method' => 'GET',
        'mode' => 'read',
    ],
    [
        'route' => 'passkey.confirm',
        'action' => 'Laravel\Passkeys\Http\Controllers\PasskeyConfirmationController@store',
        'method' => 'POST',
        'mode' => 'write',
    ],
    [
        'route' => 'passkey.registration-options',
        'action' => 'Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController@index',
        'method' => 'GET',
        'mode' => 'read',
    ],
    [
        'route' => 'passkey.store',
        'action' => 'Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController@store',
        'method' => 'POST',
        'mode' => 'write',
    ],
    [
        'route' => 'passkey.destroy',
        'action' => 'Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController@destroy',
        'method' => 'DELETE',
        'mode' => 'write',
    ],
];
