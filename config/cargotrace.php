<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CargoTrace Group & Business Rule Configurations
    |--------------------------------------------------------------------------
    | Delay threshold configuration per group scenario (e.g. 12, 18, 24 hours)
    */
    'delay_threshold_hours' => (int) env('CARGOTRACE_DELAY_THRESHOLD_HOURS', 12),

    'weather_api_timeout' => (int) env('WEATHER_API_TIMEOUT', 4),

    'cargo_types' => [
        'GENERAL' => 'General Cargo',
        'REFRIGERATED' => 'Refrigerated / Perishable',
        'HAZARDOUS' => 'Hazardous Material',
        'FRAGILE' => 'Fragile Goods',
        'ELECTRONICS' => 'High-Value Electronics',
        'AUTOMOTIVE' => 'Automotive Parts',
    ],

    'exception_types' => [
        'CUSTOMS_HOLD' => 'Customs Clearance Hold',
        'WEATHER_DELAY' => 'Adverse Weather Interruption',
        'PORT_CONGESTION' => 'Port / Terminal Congestion',
        'DAMAGED' => 'Cargo Damage Inspection',
        'MISROUTED' => 'Misrouted Cargo Rerouting',
        'DOCUMENTATION_ISSUE' => 'Documentation Discrepancy',
    ],
];
