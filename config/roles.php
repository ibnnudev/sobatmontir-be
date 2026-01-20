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
        'consumer',
    ],

    // Define permissions for each role
    'permissions' => [
        'owner' => [
            'manage_workshop',
            'manage_products',
            'manage_mechanics',
            'view_reports',
            'adjust_stock',
            'shift.open',
            'shift.close',
            'shift.view',
        ],
        'admin' => [
            'manage_products',
            'manage_mechanics',
            'view_reports',
            'shift.open',
            'shift.close',
            'shift.view',
        ],
        'mechanic' => [
            'view_jobs',
            'update_jobs',
            'shift.open',
            'shift.close',
            'shift.view',
        ],
        'mechanic_in_shop' => [
            'view_jobs',
            'update_jobs',
            'queue.create',
            'queue.call',
            'transaction.create',
            'shift.open',
            'shift.close',
            'shift.view',
        ],
        'mechanic_mobile' => [
            'view_jobs',
            'update_jobs',
            'sos.accept',
            'sos.view',
            'sos.update',
            'transaction.create',
            'shift.open',
            'shift.close',
            'shift.view',
        ],
        'customer' => [
            'view_products',
            'create_service_request',
            'sos.create',
            'sos.view',
            'sos.cancel',
        ],
        'consumer' => [
            'view_products',
            'create_service_request',
            'sos.create',
            'sos.view',
            'sos.cancel',
        ],
    ],
];
