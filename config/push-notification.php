<?php

return array(

    'IOSUser'     => array(
        'environment' =>'production',
        'certificate' => app_path().'/apns/user/Eganow_user_production.pem',
        'passPhrase'  =>'Eganow',
        'service'     =>'apns'
    ),
    'IOSProvider' => array(
        'environment' =>'production',
        'certificate' => app_path().'/apns/provider/Eganow_driver_production.pem',
        'passPhrase'  =>'Eganow',
        'service'     =>'apns'
    ),
    'AndroidUser' => array(
        'environment' =>'production',
        'apiKey'      => env('ANDROID_USER_PUSH_KEY','yourAPIKey'),
        'service'     =>'gcm'
    ),
    'AndroidProvider' => array(
        'environment' =>'production',
        'apiKey'      => env('ANDROID_PROVIDER_PUSH_KEY','yourAPIKey'),
        'service'     =>'gcm'
    )

);