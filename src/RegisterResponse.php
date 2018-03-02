<?php
/**
 * This file contains the Tokenization API response handling.
 */

namespace CheckoutFinland\TokenPayment;

class RegisterResponse {

    /**
     * @var string Merchant id, 375917 = test account
     */
    private $merchant_id;

    /**
     * @var string Merchant secret, SAIPPUAKAUPPIAS = test account secret
     */
    private $secret;

    /**
     * Holds the response hmac.
     *
     * @var string
     */
    private $hmac;

    /**
     * Response status code.
     *
     * @see https://checkoutfinland.github.io/#add-and-tokenize-a-new-card
     *
     * @var int
     */
    private $code;

    /**
     * Unified request identifier.
     *
     * @see https://checkoutfinland.github.io/#add-and-tokenize-a-new-card
     *
     * @var string
     */
    private $request_id;

    /**
     * Response status text.
     *
     * @see https://checkoutfinland.github.io/#add-and-tokenize-a-new-card
     *
     * @var string
     */
    private $status;

    /**
     * Checkout token.
     *
     * @see https://checkoutfinland.github.io/#add-and-tokenize-a-new-card
     *
     * @var string
     */
    private $token;

    /**
     * The getter for the response hmac.
     *
     * @return string
     */
    public function get_hmac(): string {

        return $this->hmac;
    }

    /**
     * The getter for the response status code number.
     *
     * @return int
     */
    public function get_code(): int {

        return $this->code;
    }

    /**
     * The getter for the response request id (reference).
     *
     * @return string
     */
    public function get_request_id(): string {

        return $this->request_id;
    }

    /**
     * The getter for the response status text.
     *
     * @return string
     */
    public function get_status(): string {

        return $this->status;
    }

    /**
     * The getter for the response token.
     *
     * @return string
     */
    public function get_token(): string {

        return $this->token;
    }

    /**
     * RegisterResponse constructor.
     *
     * @param string $merchant_id The merchant id.
     * @param string $secret      The merchant secret.
     */
    public function __construct( $merchant_id, $secret ) {
        $this->merchant_id  = $merchant_id;
        $this->secret       = $secret;
    }

    /**
     * Checkout response is identified with a reference string.
     * Tokenization uses request_id; therefore it is returned.
     *
     * @return string
     */
    public function getReference() {
        return $this->request_id;
    }

    /**
     * Sets the token response data from parameters.
     */
    public function set_token_response_data() {
        $this->code       = filter_input( INPUT_GET, 'co_code', FILTER_SANITIZE_STRING );
        $this->request_id = filter_input( INPUT_GET, 'co_request_id', FILTER_SANITIZE_STRING );
        $this->status     = filter_input( INPUT_GET, 'co_status', FILTER_SANITIZE_STRING );
        $this->token      = filter_input( INPUT_GET, 'co_token', FILTER_SANITIZE_STRING );
        $this->hmac       = filter_input( INPUT_GET, 'hmac', FILTER_SANITIZE_STRING );
    }

    /**
     * Validates the return parameters.
     *
     * @return bool
     */
    public function validate() {

        $params = [
            'co_code'       => $this->code,
            'co_request_id' => $this->request_id,
            'co_status'     => $this->status,
            'co_token'      => $this->token,
        ];

        $expected_mac = Util::calculate_hmac( $params, $this->secret );

        $valid = $expected_mac === $this->hmac;

        return $valid;
    }
}