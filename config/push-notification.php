<?php

return array(

    'appNameIOS'     => array(
        'environment' =>'development',
        'certificate' =>'/path/to/certificate.pem',
        'passPhrase'  =>'password',
        'service'     =>'apns'
    ),
    'twoHugsAndroid' => array(
        'environment' =>'production',
        'apiKey'      => env('GCM_CLOUD_API_KEY', ''),
        'service'     =>'gcm'
    )

);