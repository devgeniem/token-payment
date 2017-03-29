# Checkout Finland Token Payment

## Installation

    composer require checkoutfi/token-payment dev-master
    

## Registering a payment method/credit card

    <?php

    use CheckoutFinland\TokenPayment\Client as TokenClient;
    
    $merchant_id        = '';
   	$merchant_secret    = '';		 

    $token_client = new TokenClient($merchant_id, $merchant_secret);
    
    $stamp      = str_replace('.','', microtime(true));
    $return_url = 'https://...';
    
    $response = $token_client->registerPaymentMethod($stamp, $return_url);

The response from registerPaymentMethod() is an xml containing either an error message or a list of services that can be used to register a payment method. Currently credit card is the only option. The xml contains values and names for input fields to be used to construct forms that redirect the user to third party service.

    {foreach register/service as service}
	    <form method="{service/form/method}" action="{service/form/action}">
	    	{foreach service/fields/field as field}
	    		<input type="hidden" name="{field/name}" value="{field/value}" />
	    	{endforeach}
	    <input type="image" src="{service/info/icon}" />
	    {service/info/name}
	    </form>
    {endforeach}

After the user has regisgered their credit card they will be redirected back to the return_url given in registerPaymentMethod().

    use CheckoutFinland\TokenPayment\Client as TokenClient;
    
    $merchant_id    = '';
    $secret         = '';

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

## Creating a charge with previously registered token

[create-charge.php](./example/create-charge.php)

Response for a succesfull payment
    
    <response>
    	<code>0</code>
    	<text>OK</text>
    	<payment>1234</payment><!-- This a unique id for the payment --> 
    </response>
 
In case the payment needs additional processing or there is a delayed response on whether the payment was successfull or not the returned code is 1000. The status of the payment will be updated later via the return url using the normal payment response. Check the documentation for a normal return response for a payment for this. See https://github.com/rkioski/CheckoutAPIClient/blob/master/example/return.php for an example.

    <response>
        <code>1000</code>
        <text>PROCESSING</text>
    </response>
