<?php
/**
 * Lexical_Qty
 * Any integer number equal/greater than 0 is valid, empty values will be handled on semantic analyzer
 *
 * @uses Abstract_Number
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Lexical_Qty extends Abstract_Number {
    public static $MAX_N = 999999;
    public static $MIN_N = 0.000;

    public function validate(&$input) {
        return parent::validate($input) || $input === '';
    }
}

/**
 * Lexical_QtyUpdate
 * Allow negative values for updates
 *
 * @uses Lexical_Qty
 * @copyright 2013 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Lexical_QtyUpdate extends Lexical_Qty {
    public static $MIN_N = -999999;
}
//class Lexical_QtySuper extends Lexical_QtyUpdate {}
