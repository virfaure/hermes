<?php
/**
 * Lexical_SpecialPrice
 * Optional, number greater than 0
 *
 * @uses Abstract_Number
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Lexical_SpecialPrice extends Abstract_Number {
    public static $MAX_N = 9999999;
    public static $MIN_N = 0.01;

    public function validate(&$input) {
        return $input === '' || parent::validate($input);
    }
}
