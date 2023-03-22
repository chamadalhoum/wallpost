<?php

return [
    /*
    |----------------------------------------------------------------------------
    | Google application name
    |----------------------------------------------------------------------------
    */
    'application_name' => env('GOOGLE_APPLICATION_NAME', ''),

    /*
    |----------------------------------------------------------------------------
    | Google OAuth 2.0 access
    |----------------------------------------------------------------------------
    |
    | Keys for OAuth 2.0 access, see the API console at
    | https://developers.google.com/console
    |
    */
    'client_id' => env('GOOGLE_CLIENT_ID', '294086799849-ojrhpes3rn7vbbfrkht9mj1kuv18bq41.apps.googleusercontent.com'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET', 'G4Wpv_w_6xDOxpOiqa38zdMo'),
    'redirect_uri' => env('GOOGLE_REDIRECT', 'https://developers.google.com/oauthplayground'),
    'scopes' => ["https://www.googleapis.com/auth/plus.business.manage"],
    'access_type' => 'offline',
    'approval_prompt' => 'force',

    /*
    |----------------------------------------------------------------------------
    | Google developer key
    |----------------------------------------------------------------------------
    |
    | Simple API access key, also from the API console. Ensure you get
    | a Server key, and not a Browser key.
    |
    */
    'developer_key' => env('GOOGLE_DEVELOPER_KEY','AIzaSyD_MTQsqwvr28LMABJt5VRxO6ZofIR3nWs'),

    /*
    |----------------------------------------------------------------------------
    | Google service account
    |----------------------------------------------------------------------------
    |
    | Set the credentials JSON's location to use assert credentials, otherwise
    | app engine or compute engine will be used.
    |
    */
    'service' => [
        /*
        | Enable service account auth or not.
        */
        'enable' => env('GOOGLE_SERVICE_ENABLED', false),

        /*
         * Path to service account json file. You can also pass the credentials as an array
         * instead of a file path.
         */
        'file' => env('GOOGLE_SERVICE_ACCOUNT_JSON_LOCATION',__dir__."/gmb-cliqeo-ff70907b62a4.json"),
    ],


    /*
    |----------------------------------------------------------------------------
    | Additional config for the Google Client
    |----------------------------------------------------------------------------
    |
    | Set any additional config variables supported by the Google Client
    | Details can be found here:
    | https://github.com/google/google-api-php-client/blob/master/src/Google/Client.php
    |
    | NOTE: If client id is specified here, it will get over written by the one above.
    |
    */
    'config' => [],
    'places' => [
        'key' => env('GOOGLE_PLACES_API_KEY', 'AIzaSyD_MTQsqwvr28LMABJt5VRxO6ZofIR3nWs'),
        'verify_ssl' => true,
        'headers' => []
    ],
    
];

