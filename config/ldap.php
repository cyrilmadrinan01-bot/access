<?php

return[
    'connections' => [
        'default' => [
            'hosts' => ['ldap.company.com'],
            'base_dn' => 'dc=company,dc=com',
            'username' => env('LDAP_USERNAME'),
            'password' => env('LDAP_PASSWORD'),
        ],
    ],
];
