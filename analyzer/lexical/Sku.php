<?php
/**
 * Sku
 * Required, string between $MIN_LENGTH and $MAX_LENGTH
 * Allowed values are any alphanumeric string and the special characters: ".", "-", "_" and " "
 * Must be an unique identifier, but this will be checked on the semantic phase,
 * as the unique condition is actually for the combination sku+store.
 *
 * @uses Abstract_String
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Lexical_Sku extends Abstract_String {
    public static $MAX_LENGTH = 64;
    public static $MIN_LENGTH = 1;

    /**
     * validate
     *
     * @param mixed $input
     * @access public
     * @return bool
     */
    public function validate(&$input) {
        $ret = preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\s\.\-\_]*$/', $input);
        return parent::validate($input) && $ret;
    }

    public function getErrorMsg($input) {
        return 'can\'t contain special characters (except ".", "-", "_" and " "), ' . parent::getErrorMsg($input);
    }
}
