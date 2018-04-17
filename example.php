<?php


// Zammad - System
$zammad_api_client_config = [
    'url' => 'https://zammad.example.com',

    // with username and password
    //'username' => 'zammad-admin@ks-service.at',
    //'password' => 'Wse98R2+',
    'username' => 'zammad-admin@yourdomain',
    'password' => 'securepassword',
    // or with HTTP token:
    //'http_token' => '...',
    // or with OAuth2 token:
    //'oauth2_token' => '...',
];
//config
$c=array();
//loglevel
$c['loglevel']=10;
//caching
// none only local variable caching
// or sql
$c['cache']['mysql']['server']="127.0.0.1";
$c['cache']['mysql']['user']="cacheuser";
$c['cache']['mysql']['passwd']="cachepwd";
$c['cache']['mysql']['db']="zammad_helper";



require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';
use ZammadAPIClient\Client;
use ZammadAPIClient\ResourceType;
include('includes.php');
$zclient = new Zammadcache($zammad_api_client_config);

// loading configuration (caching, loglevel...)
$zammad->load_config($c);


// Clear Zammad-Helper caching - System
$zammad->clearcache();


// fetching Username  id:26
print_r ($zclient->getuser(26));
