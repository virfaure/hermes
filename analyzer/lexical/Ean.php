<?php
/**
 * Lexical_Ean
 * Optional field, but if filled, a valid EAN code (length 13) is required
 *
 * @uses Abstract_Lexical
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Lexical_Ean extends Abstract_Lexical {
    public static function checkEAN($fullcode) {
        $code = substr($fullcode, 0, -1);
        $checksum = 0;
        foreach (str_split(strrev($code)) as $pos => $val) {
            $checksum += $val * (3 - 2 * ($pos % 2));
        }
        return (10 - ($checksum % 10)) % 10 == substr($fullcode,-1);
    }

    /**
     * validate 
     * At least check for the lenght and ensure that it's numeric.
     * 
     * @param mixed $input 
     * @access public
     * @return bool 
     */
    public function validate(&$input) {
        parent::validate($input);
        return $input === '' || is_numeric($input);
    }

    public function getErrorMsg($input) {
        return 'allowed values: valid EAN code (number), ' . parent::getErrorMsg($input);
    }
}

/**
 * Lexical_EanSuper
 * Allow empty values
 * 
 * @uses Lexical_Ean
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net> 
 */
 
class Lexical_EanSuper extends Lexical_Ean {
    public function validate(&$input) {
        return true;
    }
}
