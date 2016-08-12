<?php

require_once "../src/Client.php";

use CheckoutFinland\TokenPayment\Client as TokenClient;

$merchant_id    = '375917';
$secret         = 'SAIPPUAKAUPPIAS';

$version    = $_GET['VERSION'];
$merchant   = $_GET['MERCHANT'];
$stamp      = $_GET['STAMP'];
$algorithm  = $_GET['ALGORITHM'];
$token      = $_GET['TOKEN']; // if empty == failed
$key        = $_GET['KEY'];
$service    = $_GET['SERVICE'];
$mac        = $_GET['MAC']; // if empty == failed

$token_client = new TokenClient($merchant_id, $secret);

if($token_client->validateRegisterReturn($version, $merchant, $stamp, $algorithm, $token, $key, $service, $mac)) {
    // success, do things, save received token to use on creating a debit charge later
} else {
    // failed
}