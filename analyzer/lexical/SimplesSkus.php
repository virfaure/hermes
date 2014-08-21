<?php
/**
 * Lexical_SimplesSkus
 * Optional, comma separated strings (that can contain spaces)
 *
 * @uses Abstract_Lexical
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Lexical_SimplesSkus extends Abstract_Lexical {

    /**
     * validate
     * Retrieve all the valid skus, if any, always return true
     * Correct the input if empty values are provided, or trailing commas were found
     *
     * @param $input string
     * @return bool
     */
    public function validate(&$input) {
        if($input === '') {
            $this->_tokens[] = array();
        } else {
            $found = preg_split('/[\\s]*[,][\\s]*/', $input);
            //Filter out empty values and fix indexes
            $tokens = array_values(array_filter($found));
            $this->_tokens[] = $tokens;
            //Correct the imput if needed
            if(count($found) !== count($tokens)) {
                $input = implode(', ', $tokens);
            }
        }
        return true;
    }
}
