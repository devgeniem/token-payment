<?php
/**
 * Holds utility methods.
 */

namespace CheckoutFinland\TokenPayment;

/**
 * Class Util
 *
 * @package CheckoutFinland\TokenPayment
 */
class Util {

    /**
     * HMAC calculation
     *
     * @param array  $params Parameters from which the HMAC is calculated
     * @param string $params The merchant secret.
     *
     * @return string HMAC for the message
     */
    public static function calculate_hmac( $params, $secret ) {
        $res_str = '';
        ksort( $params  );

        foreach ( $params as $name => $value  ) {
            $res_str .= ( $res_str == '' ? '' : "\n" ) . "$name:$value";
        }

        return hash_hmac('sha256', $res_str, $secret );
    }

}