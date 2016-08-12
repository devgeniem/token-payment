<?php

require_once "../src/Client.php";

use CheckoutFinland\TokenPayment\Client as TokenClient;

$merchant_id    = '375917';
$secret         = 'SAIPPUAKAUPPIAS';


$token_client = new TokenClient($merchant_id, $secret);


$stamp      = str_replace('.','', microtime(true));
$return_url = 'http://' .$_SERVER['SERVER_NAME'] .str_replace('index.php', 'return.php', $_SERVER['REQUEST_URI']);
$response   = $token_client->registerPaymentMethod($stamp, $return_url);

$response_xml = simplexml_load_string($response);

?><!doctype html>

<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout Finland Token Payment example</title>
</head>

<body>

<div class="content" style="max-width:800px; margin:auto;">
    <?php foreach($response_xml->service as $service): ?>
    <form method="<?php echo $service->form->method; ?>" action="<?php echo $service->form->action; ?>">
        <fieldset>
            <legend><?php echo $service->info->name; ?></legend>
            <?php foreach($service->fields->field as $field): ?>
                <input type="hidden" name="<?php echo $field->name; ?>" value="<?php echo $field->value; ?>" />
            <?php endforeach; ?>
            <input type="image" src="<?php echo $service->info->icon; ?>" />
        </fieldset>
    </form>
    <?php endforeach; ?>
</div>

</body>
</html>