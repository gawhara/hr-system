<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Nitaqat visual bands
    |--------------------------------------------------------------------------
    |
    | These thresholds are UI defaults only. Official Nitaqat classification
    | depends on activity, entity size, employee weights, and current MHRSD/Qiwa
    | rules. Keep this table editable until a direct calculator/API is wired.
    |
    */
    'bands' => [
        [
            'key' => 'platinum',
            'label' => 'بلاتيني',
            'minimum_percent' => 80,
            'from' => '#2563eb',
            'to' => '#a78bfa',
            'background' => '#eff6ff',
            'text' => '#1d4ed8',
        ],
        [
            'key' => 'high_green',
            'label' => 'أخضر مرتفع',
            'minimum_percent' => 60,
            'from' => '#047857',
            'to' => '#34d399',
            'background' => '#ecfdf5',
            'text' => '#047857',
        ],
        [
            'key' => 'medium_green',
            'label' => 'أخضر متوسط',
            'minimum_percent' => 40,
            'from' => '#16a34a',
            'to' => '#86efac',
            'background' => '#f0fdf4',
            'text' => '#15803d',
        ],
        [
            'key' => 'low_green',
            'label' => 'أخضر منخفض',
            'minimum_percent' => 20,
            'from' => '#84cc16',
            'to' => '#bef264',
            'background' => '#f7fee7',
            'text' => '#4d7c0f',
        ],
        [
            'key' => 'yellow',
            'label' => 'أصفر',
            'minimum_percent' => 10,
            'from' => '#f59e0b',
            'to' => '#fcd34d',
            'background' => '#fffbeb',
            'text' => '#b45309',
        ],
        [
            'key' => 'red',
            'label' => 'أحمر',
            'minimum_percent' => 0,
            'from' => '#dc2626',
            'to' => '#fb7185',
            'background' => '#fef2f2',
            'text' => '#b91c1c',
        ],
    ],
];
