<?php

namespace Token\Auth\Test;

require __DIR__.'/vendor/autoload.php';

use GuzzleHttp\Client;

$client = new Client([
    'base_url' => 'http://localhost:9004',
    'defaults' => [
        'exceptions' => false,
    ]
]);

$response = $client->get('/admin/post/', [
    'headers' => [
        'X-Auth-Token' => 'ANNA_ABC'
    ]
]);

echo $response."\n\n";
