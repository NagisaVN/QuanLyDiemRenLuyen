<?php

return [
    'default_roles' => [
        'admin' => [
            'manage users', 'manage roles', 'manage master data', 'manage activities',
            'manage all activities', 'view audit logs', 'manage backups', 'self evaluate',
            'review class forms', 'approve final scores', 'export reports',
            'manage_dot_danh_gia', 'open_dot_danh_gia', 'close_dot_danh_gia', 'publish_dot_danh_gia',
            'view student notifications', 'manage notifications',
        ],
        'sinh_vien' => ['self evaluate', 'view student notifications'],
        'gvcn' => ['review class forms'],
        'can_bo_doan_hoi' => ['manage activities'],
        'hoi_dong_khoa' => [
            'approve final scores', 'export reports', 'manage_dot_danh_gia',
            'open_dot_danh_gia', 'close_dot_danh_gia', 'publish_dot_danh_gia',
        ],
    ],
];
