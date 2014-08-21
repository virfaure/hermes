<?php
/**
 * Lexical_Status
 * Required, allowed values: Number between $MIN_N and $MAX_N, or any value of $statuses
 *
 * @uses Abstract_Number
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Lexical_Status extends Abstract_Number {
    public static $MAX_N = 2;
    public static $MIN_N = 1;

    public function validate(&$input) {
        $input = ucfirst(strtolower($input));
        return parent::validate($input) || ($input == 'Habilitado' || $input == 'Deshabilitado');
    }
    public function getErrorMsg($input) {
        return 'allowed values: (Habilitado|Deshabilitado), ' . parent::getErrorMsg($input);
    }

}

/**
 * Lexical_StatusSuper
 * Allow empty values
 *
 * @uses Lexical_Status
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Lexical_StatusSuper extends Lexical_Status {

    public function validate(&$input) {
        return parent::validate($input) || $input === '';
    }
}
