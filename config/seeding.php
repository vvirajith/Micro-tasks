<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Account Passwords
    |--------------------------------------------------------------------------
    |
    | UserSeeder owns the admin roster: who they are and what they are called.
    | Only the secret lives here, read from the environment, so that no real
    | password is ever committed. An entry left empty is skipped rather than
    | seeded with a guessable default.
    |
    | These belong in a config file rather than in the seeder because env() is
    | not safe outside of one: it returns null once the configuration has been
    | cached with `php artisan config:cache`.
    |
    */

    'admin_passwords' => [
        'super' => env('ADMIN_SUPER_PASSWORD'),
        'adithya' => env('ADMIN_ADITHYA_PASSWORD'),
        'lahiru' => env('ADMIN_LAHIRU_PASSWORD'),
    ],

];
