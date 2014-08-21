<?php
/**
 * Lexical_ConfigurableAttributes
 * All the attributes must be specified in lowercase
 *
 * @uses Abstract_Lexical
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Lexical_ConfigurableAttributes extends Abstract_Lexical {
    public function validate(&$input) {
        $ret = preg_match_all("/[a-zA-Z0-9_-]+/", $input, $found);
        $input = strtolower(implode($found[0], ','));
        if($ret) {
            $this->_tokens[] = $found[0];
        } else {
            $this->_tokens[] = array();
        }
        return $ret || $input === '';
    }
}
