<?php

use Carbon\Carbon;

return [

    'site' => [
        'logo_url' => '/images/logo-letter-1.png',
    ],

    'languages' => [
        'English' => 'en',
        'Hindi' => 'hi',
        'Arabic' => 'ar',
    ],

    'export_csv_file_type' => 'csv',

    'default_datetime_format' => 'd/m/Y H:i:s',
    'default_date_format' => 'd/m/Y',
    'default_time_format' => 'H:i:s',

    'api_datetime_format' => 'Y-m-d H:i:s',
    'api_date_format' => 'Y-m-d',
    'api_time_format' => 'H:i:s',

    'date_formats' => [
        'default' => 'jS F, Y  h:i a',
    ],

    'status' => [
        'inactive' => 'N',
        'active' => 'Y',
    ],

    'status_values' => [
        'N' => 'Inactive',
        'Y' => 'Active',
    ],

    'import_csv_log' => [

        'status' => [
            'key' => [
                'success' => 'Y',
                'fail' => 'N',
                'pending' => 'P',
                'processing' => 'S',
                'convert_decrypted' => 'D',
            ],
            'value' => [
                'success' => 'Success',
                'fail' => 'Fail',
                'pending' => 'Pending',
                'processing' => 'Processing',
                'convert_decrypted' => 'Processing For Decrypted',
            ],
        ],

        'import_flag' => [
            'key' => [
                'success' => 'Y',
                'pending' => 'P',
            ],

            'value' => [
                'value' => [
                    'success' => 'Success',
                    'pending' => 'Pending',
                ],
            ],
        ],

        'import_email_recipients' => [
            'hello@yopmail.com',
        ],

        'models' => [
            'role' => 'roles',
            'category' => 'categories',
            'subcategory' => 'subcategories',
            'product' => 'products',
        ],

        'subject' => [
            'role' => 'Role Import',
        ],

        'folder_name' => [
            'new' => [
                'role' => 'import/new/role',
            ],
        ],
    ],

    'import_type' => [
        'role' => 'roles',
        'category' => 'categories',
        'subcategory' => 'subcategories',
        'product' => 'products',
    ],

    'validation_codes' => [
        'ok' => 200,
        'created' => 201,
        'bad_request' => 400,
        'unauthorized' => 401,
        'forbidden' => 403,
        'not_found' => 404,
        'unprocessable_entity' => 422,
        'unassigned' => 427,
        'rate_limit' => 429,
    ],

    'calender' => [
        'date' => Carbon::now()->toDateString(),
        'date_format' => Carbon::now()->format('Y-m-d'),
        'time' => Carbon::now()->toTimeString(),
        'date_time' => Carbon::now()->toDateTimeString(),
        'start_Of_month' => Carbon::now()->startOfMonth(),
        'last_year_date' => Carbon::now()->subYear()->format('Y-m-d'),
        'import_format' => Carbon::now()->format('d-M-Y'),
    ],

    'file' => [
        'name' => Carbon::now('Asia/Kolkata')->format('d_m_Y') . '_' . Carbon::now('Asia/Kolkata')->format('g_i_a'),
    ],

    'allowed_ip_addresses' => [
        'telescope' => env('TELESCOPE_ALLOWED_IP_ADDRESSES'),
        'pulse' => env('PULSE_ALLOWED_IP_ADDRESSES'),
    ],

    'token_expiry' => env('TOKEN_EXPIRY', (60 * 60 * 24)), // Default 24 hours

    'api_timeout' => env('API_TIMEOUT', 60), // API timeout in seconds (default: 60 seconds)

    'default_single_filesize' => 20,
    'default_file_extensions' => ['jpeg', 'jpg', 'png', 'webp'],

    'email_format' => [
        'type' => ['header' => '1', 'footer' => '2', 'signature' => '3'],
        'type_enum' => ['1', '2', '3'],

        'serialized' => [0 => 'Normal data', 1 => 'json format data'],
        'serialized_enum' => ['0', '1'],
    ],

    'email_template' => [
        'table' => [
            'table_name' => 'email_templates',
            'entity_name' => 'Email Template',
            'entity_name_plural' => 'Email Templates',
        ],
        'type' => [
            'user_login' => '1',
            'import_success' => '2',
            'import_fail' => '3',
            'change_password' => '4',
        ],

        'type_values' => [
            '1' => 'User Login',
            '2' => 'Import Success',
            '3' => 'Import Fail',
            '4' => 'Change Password',
        ],

        'status' => [
            'inactive' => 'N',
            'active' => 'Y',
        ],

        'status_values' => [
            'N' => 'Inactive',
            'Y' => 'Active',
        ],

        'status_message' => [
            'inactive' => 'Inactive',
            'active' => 'Active',
        ],

        'lagends' => [],

        'common_lagends' => [
            'admin_login_url' => '{{admin_login_url}}',
            'front_login_url' => '{{front_login_url}}',
            'reset_password_link' => '{{reset_password_link}}',
        ],
    ],

    'roles' => [
        'admin' => 1,

        'value' => [
            'admin' => 'Admin',
        ],
    ],

    'webPerPage' => '10',
    'webPerPageValues' => [10, 25, 50, 100],

    'apiPerPage' => 10,
    'apiPage' => 1,

    'google_recaptcha_key' => ENV('GOOGLE_RECAPTCHA_KEY'),
    'google_recaptcha_secret' => env('GOOGLE_RECAPTCHA_SECRET'),
    'check_google_recaptcha' => env('CHECK_GOOGLE_RECAPTCHA', false),

    'rate_limiting' => [
        'limit' => [
            'ip' => 1800, // 30 Minute Limit
            'otp' => 1800, // 30 Minute Limit
            'contact_number' => 1800, // 30 Minute Limit
            'forgot_password' => 60, // 1 Minute Limit
            'one_day' => 60 * 60 * 24,
            'one_hour' => 3600,
            'ip_attempt_limit' => 9,
            'email_attempt_limit' => 10,
        ],
        'message' => 'You have exceeded the allowed number of attempts, Please try again later.',
    ],

    'export_template_legend' => [
        '{{exportReport_downloadLink}}',
        '{{exportReport_modelName}}',
        '{{exportReport_dateTime}}',
        '{{exportReport_subject}}',
    ],

    'otp_counter_type' => [
        'login' => 'otpTimer',
    ],

    'status_values' => [
        'Y' => 'Active',
        'N' => 'InActive',
        0 => 'Inactive',
        1 => 'Active',
        2 => 'Inactive',
        3 => 'Active',
        4 => 'Inactive',
        5 => 'Active',
        6 => 'Inactive',
        7 => 'Active',
        'B' => 'Booked',
        'D' => 'Completed',
    ],
    'user_type_values' => [
        'D' => 'DSM',
        'A' => 'Admin',
        'P' => 'Party',
    ],
    'is_active_values' => [
        'Y' => 'Active',
        'N' => 'Inactive',
    ],
    'available_status_values' => [
        0 => 'Not-available',
        1 => 'Available',
        2 => 'Not-available',
        3 => 'Available',
        4 => 'Not-available',
        5 => 'Available',
    ],

    'role' => [
        'status' => [
            'key' => [
                'active' => 'Y',
                'inactive' => 'N',
            ],
            'value' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
            ],
        ],
    ],
    'user' => [
        'status' => [
            'key' => [
                'active' => 'Y',
                'inactive' => 'N',
            ],
            'value' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
            ],
        ],
    ],
    'category' => [
        'status' => [
            'key' => [
                'inactive' => 0,
                'active' => 1,
            ],
            'value' => [
                'inactive' => 'Inactive',
                'active' => 'Active',
            ],
        ],
    ],
    'sub_category' => [
        'status' => [
            'key' => [
                'inactive' => 0,
                'active' => 1,
            ],
            'value' => [
                'inactive' => 'Inactive',
                'active' => 'Active',
            ],
        ],
    ],
    'product' => [
        'available_status' => [
            'key' => [
                'not-available' => 0,
                'available' => 1,
            ],
            'value' => [
                'not-available' => 'Not-available',
                'available' => 'Available',
            ],
        ],
    ],
    'appointment' => [
        'status' => [
            'key' => [
                'booked' => 'B',
                'completed' => 'D',
                'cancelled' => 'N',
            ],
            'value' => [
                'booked' => 'Booked',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
            ],
        ],
    ],
    'sms_template' => [
        'status' => [
            'key' => [
                'active' => 'Y',
                'inactive' => 'N',
            ],
            'value' => [
                'active' => 'Active',
                'inactive' => 'InActive',
            ],
        ],
    ],

];
