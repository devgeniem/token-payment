<?php

namespace CheckoutFinland\TokenPayment;

/**
 * Class Client
 * @package CheckoutFinland\TokenPayment
 */
class Client
{
    /**
     * @var string Merchant id, 375917 = test account
     */
    private $merchant_id;
    /**
     * @var string Merchant secret, SAIPPUAKAUPPIAS = test account secret
     */
    private $secret;

    /**
     * Client constructor
     *
     * @param $merchant_id
     * @param $secret
     */
    public function __construct($merchant_id, $secret)
    {
        $this->merchant_id  = $merchant_id;
        $this->secret       = $secret;
    }

    /**
     * Initiate a payment method registration, if succesfull returns xml that can used to construct an html form for the customer,
     * the form redirects the customer to an offsite gateway where the credit card is registered and a token is returned in the GET
     * parameters when the customer returns to the return_url
     *
     * @param string $stamp Unique identifier for this transaction, cannot be reused, recommend to use microtime() etc
     * @param string $return_url Return url
     * @param string $language UI language of the registration page, supported languages: DE, EN, ES, FI, FR, RU, SV
     * @return mixed|string
     * @throws \Exception
     */
    public function registerPaymentMethod($stamp, $return_url, $language = 'FI')
    {
        $url = "https://payment.checkout.fi/token/register";

        $params = [
            'VERSION'   => '0001',
            'MERCHANT'  => $this->merchant_id,
            'STAMP'     => $stamp,
            'ALGORITHM' => '3',
            'LANGUAGE'  => $language,
            'RETURN'    => $return_url
        ];

        $params['MAC'] = strtoupper(hash_hmac('sha256', join('+', $params) , $this->secret));

        return $this->postData($url, $params);
    }

    /**
     * Validates the return parameters
     *
     * @param string $version 0001
     * @param string $merchant Merchant id
     * @param string $stamp Unique identifier
     * @param integer $algorithm 3 = hmac sha256
     * @param string $token Payment token, if empty registration failed
     * @param string $key Payment method identifier
     * @param string $service Service code, matches the service code in returned xml in registerPaymentMethod()
     * @param string $mac hmac sha256 mac, if empty the registration failed
     * @return bool
     * @throws \Exception
     */
    public function validateRegisterReturn($version, $merchant, $stamp, $algorithm, $token, $key, $service, $mac)
    {
        if($algorithm == 3)
            $expected_mac = strtoupper(hash_hmac('sha256', "{$version}&{$merchant}&{$stamp}&{$algorithm}&{$token}&{$key}&{$service}", $this->secret));
        else
            throw new \Exception('Unsupported algorithm');

        if($expected_mac == $mac)
            return true;
        else
            return false;
    }

    /**
     * Creates a charge on the previously registered payment method
     *
     * @param string $token The token assigned to this customer/order, fetched by registering a payment method, 00000000-0000-0000-0000-000000000000 = test account token
     * @param string $stamp Unique identifier for this transaction, cannot be reused, recommend to use microtime() etc
     * @param integer $amount Amount of the charge, in cents, 1€ == 100
     * @param string $reference Reference number to the payment, order number etc, can be duplicate with a previous payment
     * @param string $message Description of payment/contents
     * @param string $return_url Return url
     * @param timestamp $delivery_date Unix timestamp, an estimate of delivery date,
     * @param string $first_name First name of customer
     * @param string $last_name Last name of customer
     * @param string $street_address  Address of customer
     * @param string $postcode Postcode
     * @param string $post_office Postoffice
     * @param string $email Email of customer
     * @param string$phone Phonenumber of customer
     * @param integer $content Content type of purchase 2 = adult entertainment, 1 = everything else
     * @return mixed|string
     * @throws \Exception
     */
    public function debit($token, $stamp, $amount, $reference, $message, $return_url, $delivery_date, $first_name, $last_name, $street_address, $postcode, $post_office, $email, $phone, $content = 1)
    {
        $url = "https://payment.checkout.fi/token/debit";

        if(!is_numeric($amount))
            throw new \Exception('Amount must be a numeric value');
        if($amount > 99999999 or $amount < 100)
            throw new \Exception("Amount: $amount  out of range, 100 - 99999999"); // Amount can be lower then 1€ but only if there is an explicit contract allowing it with Checkout Finland Oy


        $params = [
            'VERSION'       => '0001',
            'STAMP'         => mb_substr($stamp, 0, 20),
            'AMOUNT'        => $amount,
            'REFERENCE'     => mb_substr($reference, 0, 20),
            'MESSAGE'       => mb_substr($message, 0, 1000),
            'MERCHANT'      => $this->merchant_id,
            'RETURN'        => mb_substr($return_url, 0 , 200),
            'CURRENCY'      => 'EUR',
            'CONTENT'       => "$content",
            'ALGORITHM'     => '3',
            'DELIVERY_DATE' => date('Ymd', $delivery_date),
            'FIRSTNAME'     => mb_substr($first_name, 0, 40),
            'FAMILYNAME'    => mb_substr($last_name, 0, 40),
            'ADDRESS'       => mb_substr($street_address, 0, 40),
            'POSTCODE'      => mb_substr($postcode, 0, 14),
            'POSTOFFICE'    => mb_substr($post_office, 0, 18),
            'EMAIL'         => mb_substr($email, 0, 200),
            'PHONE'         => mb_substr($phone, 0, 30),
            'TOKEN'         => $token,
        ];
        
        $params['MAC'] = strtoupper(hash_hmac('sha256', join('+', $params) , $this->secret));
        
        return $this->postData($url, $params);
    }



    /**
     * Posts the data, tries to use stream context if allow_url_fopen is on in php.ini or CURL if not. If neither option is available throws exception.
     *
     * @param $url
     * @param $postData
     * @return mixed|string
     * @throws \Exception
     */
    private function postData($url, $postData)
    {
        if(ini_get('allow_url_fopen'))
        {
            $context = stream_context_create(array(
                'http' => array(
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query($postData)
                )
            ));
            return file_get_contents($url, false, $context);
        }
        elseif(in_array('curl', get_loaded_extensions()) )
        {
            $options = array(
                CURLOPT_POST            => 1,
                CURLOPT_HEADER          => 0,
                CURLOPT_URL             => $url,
                CURLOPT_FRESH_CONNECT   => 1,
                CURLOPT_RETURNTRANSFER  => 1,
                CURLOPT_FORBID_REUSE    => 1,
                CURLOPT_TIMEOUT         => 4,
                CURLOPT_POSTFIELDS      => http_build_query($postData)
            );
            $ch = curl_init();
            curl_setopt_array($ch, $options);
            $result = curl_exec($ch);
            curl_close($ch);
            return $result;
        }
        else
        {
            throw new \Exception("No valid method to post data. Set allow_url_fopen setting to On in php.ini file or install curl extension.");
        }
    }


}