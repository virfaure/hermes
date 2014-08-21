<?php
/**
 * Lexical_Type
 * Required, allowed values are 'simple' or 'configurable'
 *
 * @uses Abstract
 * @uses _Lexical
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Lexical_Type extends Abstract_Lexical {
    public function validate(&$input) {
        $input = strtolower($input);
        parent::validate($input);
        
        $types = array('simple', 'configurable', 'virtual', 'downloadable', 'bundle', 'grouped');
        return in_array($input, $types);
    }

    public function getErrorMsg($input) {
        return 'allowed values: (simple|configurable), ' . parent::getErrorMsg($input);
    }
}

/**
 * Lexical_TypeSuper
 * Allow empty values
 * 
 * @uses Lexical_Type
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net> 
 */
class Lexical_TypeSuper extends Lexical_Type {
    public function validate(&$input) {
        return parent::validate($input) || $input === '';
    }
}
