<?php
/**
 * Basic token payment API usage class.
 */

namespace CheckoutFinland\TokenPayment;

/**
 * Class Payment
 *
 * Implements basic token payment API usage
 *
 */
class Payment {
    const basePaymentXML = '<?xml version="1.0"?><checkout xmlns="http://checkout.fi/request"><request></request></checkout>';
    const serviceURL = 'https://payment.checkout.fi';

    private $merchantId;
    private $merchantSecret;
    private $token;

    function __construct($merchantId, $merchantSecret, $token)
    {
        $this->merchantId = $merchantId;
        $this->merchantSecret = $merchantSecret;
        $this->token = $token;
    }

    /**
     * HMAC calculation
     *
     * @param $params Parameter from which the HMAC is calculated
     * @return string HMAC for the message
     */
    private function calculateHmac($params)
    {
        $resStr = '';
        ksort($params);
        foreach ($params as $key => $val) {
            $resStr .= ($resStr == '' ? '' : "\n") . "$key:$val";
        }

        return hash_hmac('sha256', $resStr, $this->merchantSecret);
    }

    /**
     * Generate a query
     *
     * @param $url target URL
     * @param $params query parameters
     * @return string XML response from the server
     */
    private function createQuery($url, $params)
    {
        $params['hmac'] = $this->calculateHmac($params);

        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($params)
            ),
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false
            )
        ));

        $response = file_get_contents($url, false, $context);

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
    public function createPayment($amount, $stamp, $reference, $description, $commit = false)
    {
        $xml = simplexml_load_string(self::basePaymentXML);
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

        $CHECKOUT_XML = base64_encode($xml->asXML());
        $CHECKOUT_MAC = strtoupper(hash_hmac('sha256', $CHECKOUT_XML, $this->merchantSecret));

        $params = array(
            'CHECKOUT_XML' => $CHECKOUT_XML,
            'CHECKOUT_MAC' => $CHECKOUT_MAC
        );

        return $this->createQuery(self::serviceURL, $params);
    }

    /**
     * Commit an authorization hold
     *
     * @param $amount Commit amount can be same or smaller than the authorization hold
     * @param $stamp Stamp of the payment to be committed
     *
     * @return string XML response from the server
     */
    public function commitPayment($amount, $stamp)
    {
        $params = array(
            'merchant' => $this->merchantId,
            'stamp' => $stamp,
            'amount' => $amount
        );

        return $this->createQuery(self::serviceURL . '/token/payment/commit', $params);
    }

    /**
     * Cancel an authorization hold
     *
     * @param $stamp Stamp of the payment to be cancelled
     *
     * @return string XML response from the server
     */
    public function cancelPayment($stamp)
    {
        $params = array(
            'merchant' => $this->merchantId,
            'stamp' => $stamp,
        );

        return $this->createQuery(self::serviceURL . '/token/payment/retract', $params);
    }

    /**
     * Fetch current payment status
     *
     * @param $stamp Stamp of the payment of which information is fetched
     *
     * @return string XML response from the server
     */
    public function paymentStatus($stamp)
    {
        $params = array(
            'merchant' => $this->merchantId,
            'stamp' => $stamp
        );

        return $this->createQuery(self::serviceURL . '/token/payment/info', $params);
    }

    /**
     * Fetches information about tokenized credit card
     *
     * @return string XML response from the server
     */
    public function cardInfo()
    {
        $params = array(
            'merchant' => $this->merchantId,
            'token' => $this->token
        );

        return $this->createQuery(self::serviceURL . '/token/migrate/info', $params);
    }

}