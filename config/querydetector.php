<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Query Detector
    |--------------------------------------------------------------------------
    |
    | This option controls whether the query detector is enabled or not.
    | When enabled, it will detect N+1 query problems in your application.
    |
    */

    'enabled' => env('QUERY_DETECTOR_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Output
    |--------------------------------------------------------------------------
    |
    | Here you can configure the output methods for detected queries.
    | Available outputs: Log, Alert, Json
    |
    */

    'output' => [
        BeyondCode\QueryDetector\Outputs\Log::class,
        BeyondCode\QueryDetector\Outputs\Json::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Threshold
    |--------------------------------------------------------------------------
    |
    | The threshold for the number of queries that should be executed
    | before the detector triggers a warning.
    |
    */

    'threshold' => env('QUERY_DETECTOR_THRESHOLD', 3),

    /*
    |--------------------------------------------------------------------------
    | Ignore
    |--------------------------------------------------------------------------
    |
    | Here you can specify which queries should be ignored by the detector.
    |
    */

    'ignore' => [
        // 'SELECT * FROM migrations',
    ],
];
