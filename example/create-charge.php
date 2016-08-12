<?php

require_once "../src/Client.php";

use CheckoutFinland\TokenPayment\Client as TokenClient;

$merchant_id    = '375917';
$secret         = 'SAIPPUAKAUPPIAS';
$token          = '00000000-0000-0000-0000-000000000000'; // demo token


$token_client = new TokenClient($merchant_id, $secret);

$params = [
    'token'             => $token,
    'stamp'             => time(),
    'amount'            => 100,
    'reference'         => '12344',
    'message'           => 'Nuts and bolts',
    'return_url'        => 'http://' .$_SERVER['SERVER_NAME'] .str_replace('index.php', 'return.php', $_SERVER['REQUEST_URI']),
    'delivery_date'     => date('Ymd', strtotime('+2 weeks')),
    'first_name'        => 'John',
    'last_name'         => 'Smith',
    'street_address'    => 'Somestreet 123',
    'postcode'          => '33100',
    'post_office'       => 'Some city',
    'email'             => 'email@email',
    'phone'             => '123456789',
];

$response = $token_client->debit(...array_values($params));


// when using test account with test token the charge fails randomly (simulates cancelled card etc)
var_dump($response);