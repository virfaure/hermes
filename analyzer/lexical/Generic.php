<?php
/**
 * Lexical_Generic
 * Default strategy fallback once the field is unknown
 *
 * @uses Abstract_Lexical
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Lexical_Generic extends Abstract_Lexical {

    /**
     * If there is no strategy defined for this field, just save the input and bypass validation
     *
     * @param $input string
     * @return bool
     */
    public function validate(&$input) {
        $this->_tokens[] = $input;
        return true;
    }
}
