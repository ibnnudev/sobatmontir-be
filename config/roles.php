<?php

return [
    // Define application roles
    'roles' => [
        'owner',
        'admin',
        'mechanic',
        'mechanic_in_shop',
        'mechanic_mobile',
        'customer',
    ],

    // Define permissions for each role
    'permissions' => [
        'owner' => [
            'manage_workshop',
            'manage_products',
            'manage_mechanics',
            'view_reports',
            'adjust_stock',
        ],
        'admin' => [
            'manage_products',
            'manage_mechanics',
            'view_reports',
        ],
        'mechanic' => [
            'view_jobs',
            'update_jobs',
        ],
        'mechanic_in_shop' => [
            'view_jobs',
            'update_jobs',
            'queue.create',
            'queue.call',
            'transaction.create',
        ],
        'mechanic_mobile' => [
            'view_jobs',
            'update_jobs',
            'sos.accept',
            'transaction.create',
        ],
        'customer' => [
            'view_products',
            'create_service_request',
        ],
    ],
];
