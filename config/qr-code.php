<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default output format
    |--------------------------------------------------------------------------
    |
    | Supported: "png", "svg", "webp", "eps", "pdf", "binary"
    |
    */

    'format' => env('QR_CODE_FORMAT', 'png'),

    /*
    |--------------------------------------------------------------------------
    | Size & margin
    |--------------------------------------------------------------------------
    */

    'size' => (int) env('QR_CODE_SIZE', 300),

    'margin' => (int) env('QR_CODE_MARGIN', 10),

    /*
    |--------------------------------------------------------------------------
    | Error correction
    |--------------------------------------------------------------------------
    |
    | Supported: "low", "medium", "quartile", "high"
    | Use "high" when embedding a logo.
    |
    */

    'error_correction' => env('QR_CODE_ERROR_CORRECTION', 'medium'),

    /*
    |--------------------------------------------------------------------------
    | Encoding
    |--------------------------------------------------------------------------
    */

    'encoding' => env('QR_CODE_ENCODING', 'UTF-8'),

    /*
    |--------------------------------------------------------------------------
    | Colors (RGBA, alpha 0–127 where 0 is opaque for Endroid)
    |--------------------------------------------------------------------------
    */

    'foreground' => [
        'r' => (int) env('QR_CODE_FOREGROUND_R', 0),
        'g' => (int) env('QR_CODE_FOREGROUND_G', 0),
        'b' => (int) env('QR_CODE_FOREGROUND_B', 0),
        'a' => (int) env('QR_CODE_FOREGROUND_A', 0),
    ],

    'background' => [
        'r' => (int) env('QR_CODE_BACKGROUND_R', 255),
        'g' => (int) env('QR_CODE_BACKGROUND_G', 255),
        'b' => (int) env('QR_CODE_BACKGROUND_B', 255),
        'a' => (int) env('QR_CODE_BACKGROUND_A', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Round block size mode
    |--------------------------------------------------------------------------
    |
    | Supported: "margin", "enlarge", "shrink", "none"
    |
    */

    'round_block_size_mode' => env('QR_CODE_ROUND_BLOCK_SIZE_MODE', 'margin'),

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    */

    'disk' => env('QR_CODE_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    |
    | When true, Endroid re-reads the generated image to confirm the payload.
    | Slower; useful in tests or when debugging scanners.
    |
    */

    'validate_result' => (bool) env('QR_CODE_VALIDATE_RESULT', false),

    /*
    |--------------------------------------------------------------------------
    | Input limits
    |--------------------------------------------------------------------------
    */

    'max_data_length' => (int) env('QR_CODE_MAX_DATA_LENGTH', 4296),

];
