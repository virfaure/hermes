<?php
/**
 * Semantic_Type
 * If type = configurable, simples_skus field is required
 *
 * @uses Abstract_Lexical
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Semantic_Type extends Abstract_Lexical {

    const ERROR_MESSAGE = 'simples_skus field is required if any configurable product is specified.';

    public function validate(&$input) {
        $configurables = array_keys($input['type'], 'configurable');
        return count($configurables) == 0 || (isset($input['simples_skus']) && isset($input['configurable_attributes']));
    }
    public function getErrors() {
        return null;
    }
    public function getErrorMsg($input) {
        return self::ERROR_MESSAGE;
    }
}

