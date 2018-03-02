<?php

namespace CheckoutFinland\TokenPayment;

/**
 * Class Client
 * @package CheckoutFinland\TokenPayment
 */
class Client {

    /**
     * @var string Merchant id, 375917 = test account
     */
    private $merchant_id;

    /**
     * @var string Merchant secret, SAIPPUAKAUPPIAS = test account secret
     */
    private $secret;

    /**
     * The Token Payment API url.
     *
     * @var string
     */
    private static $service_url = 'https://payment.checkout.fi';

    /**
     * Base XML for a token payment.
     *
     * @var string
     */
    private static $base_payment_xml = '<?xml version="1.0"?><checkout xmlns="http://checkout.fi/request"><request></request></checkout>';

    /**
     * Client constructor
     *
     * @param $merchant_id
     * @param $secret
     */
    public function __construct( $merchant_id, $secret ) {
        $this->merchant_id  = $merchant_id;
        $this->secret       = $secret;
    }

    /**
     * Initiate a payment method registration. If it is succesful, the request returns the Payment Highway HTML.
     *
     * @param string $stamp Unique identifier for this transaction, cannot be reused, recommend to use microtime() etc
     * @param string $return_url Redirection URL on succeeded, cancelled and failed action.
     * @param string $request_id Unique identificator for the request.
     * @return array|\WP_Error
     * @throws \Exception
     */
    public function registerPaymentMethod( $request_id, $return_url ) {
        $url = self::$service_url . '/token/card/add';

        $params = [
            'merchant'    => $this->merchant_id,
            'success_url' => $return_url,
            'failure_url' => $return_url,
            'cancel_url'  => $return_url,
            'request_id'  => $request_id,
        ];

        $params['hmac'] = Util::calculate_hmac( $params, $this->secret );

        $response = $this->post_data( $url, $params );
        return $response;
    }

    /**
     * Creates a payment or an authorization hold
     *
     * @param $amount Amount in cents
     * @param $stamp Unique stamp for the payment
     * @param $reference Reference for the payment
     * @param $description Description for the payment
     * @param bool $commit Commit payment true = commit, false = create an authorization hold
     *
     * @return string XML response from the server
     */
    public function createPayment( $amount, $stamp, $reference, $description, $commit = false ) {
        $xml = simplexml_load_string(self::base_payment_xml );
        $xml->request->token = $this->token;
        $xml->request->version = '0002';
        $xml->request->stamp = $stamp;
        $xml->request->reference = $reference;
        $xml->request->device = 10;
        $xml->request->content = 1;
        $xml->request->type = 0;
        $xml->request->algorithm = 3;
        $xml->request->currency = 'EUR';
        $xml->request->commit = $commit ? 'true' : 'false';
        $xml->request->description = $description;
        $xml->request->merchant = $this->merchantId;
        $xml->request->amount = $amount;
        $xml->request->delivery->date = '20170619';
        $xml->request->buyer->country = 'FIN';
        $xml->request->buyer->language = 'FI';

        $CHECKOUT_XML = base64_encode( $xml->asXML() );
        $CHECKOUT_MAC = strtoupper(hash_hmac('sha256', $CHECKOUT_XML, $this->secret) );

        $params = array(
            'CHECKOUT_XML' => $CHECKOUT_XML,
            'CHECKOUT_MAC' => $CHECKOUT_MAC
         );

        return $this->createQuery( self::serviceURL, $params );
    }

    /**
     * Generate a query
     *
     * @param $url target URL
     * @param $params query parameters
     * @return WP_Error|array The response or WP_Error on failure.
     */
    private function createQuery( $url, $params ) {
        $params['hmac'] = Util::calculate_hmac( $params, $this->secret );

        $response = $this->post_data( $url, $params );

        return $response;
    }

    /**
     * Posts the data.
     *
     * @param string $url       The request url.
     * @param array  $post_data The post data.
     *
     * @return WP_Error|array The response or WP_Error on failure.
     */
    private function post_data( $url, $post_data ) {
        $env = defined( 'WP_ENV' ) ? WP_ENV : 'development';

        // Do not verify SSl on development environment.
        $ssl_verify = $env === 'development' ? false : true;

        $response = \wp_remote_post( $url, array(
                'method'      => 'POST',
                'timeout'     => 45,
                'blocking'    => true,
                'body'        => $post_data,
                'sslverify'   => $ssl_verify,
                'headers'     => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'charset'      => 'utf-8',
                ],
            )
         );

        return $response;
    }

}