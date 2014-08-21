<?php
/**
 * Lexical_Talla
 * Optional, number between $MIN_N and $MAX_N, or any value that matches in $tallas array.
 * This validator has been disabled due to the lack of normalization of its values.
 *
 * @uses Abstract_Number
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Lexical_Talla extends Abstract_Number {
    public static $MAX_N = 120;
    public static $MIN_N = 1;
    public static $tallas = array ('XXS', 'XS', 'S', 'SG','SL', 'SM', 'M', 'ML', 'ML+', 'L', 'XL', '2XL', 'XXL', '3XL', 'XXXL', '4XL', 'S/36', 'M/40', 'L/44', 'XL/48', 'XXL/52');
    public function validate(&$input) {
        return parent::validate($input) || true;
    }
}
