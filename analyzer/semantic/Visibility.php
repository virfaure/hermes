<?php
/**
 * Semantic_Visibility
 * simple default/configurable => 4
 * simple with attributes => 1
 *
 * @uses Abstract_Lexical
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Semantic_Visibility extends Abstract_Lexical {
    const ERROR_MESSAGE = 'unable to assign default visibility.';

    public function validate(&$input) {
        //Get all empty values
        $emptys = array_intersect($input['visibility'], array(''));
        $ret = true;

        foreach ($emptys as $i => $visibility) {
            //Try to assign a default visibility
            if($input['type'][$i] === 'configurable' || ($input['type'][$i] === 'simple' && $input['attribute_set'][$i] === 'default')) {
                $input['visibility'][$i] = 4;
            } else if($input['type'][$i] === 'simple' && strtolower($input['attribute_set'][$i]) !== 'default' && $input['attribute_set'][$i]) {
                $input['visibility'][$i] = 1;
                //If it's not the admin store, we allow to leave this field empty, as it will take the parent's value
            } else if (!isset($input['store']) || in_array('admin', $input['store'][$i])) {
                $ret = false;
            }
        }
        return $ret;
    }
    public function getErrors() {
        return null;
    }
    public function getErrorMsg($input) {
        return self::ERROR_MESSAGE;
    }
}

