<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Roles
    |--------------------------------------------------------------------------
    | Single source of truth for the four roles defined by the users.role enum
    | in the database. Referenced by RoleMiddleware and controllers so role
    | strings are never hardcoded across the codebase.
    */

    'admin'     => 'admin',
    'landlord'  => 'landlord',
    'caretaker' => 'caretaker',
    'tenant'    => 'tenant',

    'all' => ['admin', 'landlord', 'caretaker', 'tenant'],

];