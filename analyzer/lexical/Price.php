<?php
/**
 * Lexical_Price
 * Required field, any number between greater than 0.
 *
 * @uses Abstract
 * @uses _Number
 * @package
 * @version $id$
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Lexical_Price extends Abstract_Number {
    public static $MAX_N = 9999999;
    public static $MIN_N = 0.01;

    /**
     * Return the detailed message explaining the error.
     *
     * @param $input
     * @return bool
     */
    public function getErrorMsg($input) {
        return 'required field, ' . parent::getErrorMsg($input);
    }

}

class Lexical_PriceImport extends Lexical_Price {

    public function validate(&$input) {
        return parent::validate($input) || $input === '';
    }
}
//class Lexical_PriceSuper extends Lexical_PriceImport {}
class Lexical_PriceUpdate extends Lexical_PriceImport {}
