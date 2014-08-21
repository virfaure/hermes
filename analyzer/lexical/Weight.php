<?php
/**
 * Lexical_Weight
 * Number between $MIN_N and $MAX_N
 *
 * @uses Abstract_Number
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Lexical_Weight extends Abstract_Number {
    public static $MAX_N = 9999;
    public static $MIN_N = 0.000;
}

/**
 * Lexical_WeightSuper
 * Allow empty values
 * 
 * @uses Lexical_Weight
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net> 
 */
 
class Lexical_WeightSuper extends Lexical_Weight {
    public function validate(&$input) {
        return true;
    }
}
