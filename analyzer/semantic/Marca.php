<?php
/**
 * Semantic_Marca
 * Ensure that field is set for all products if it's a custom site
 *
 * @uses Abstract_Lexical
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Semantic_Marca extends Abstract_Semantic {
    function __construct($customSite = false) {
        $this->customSite = $customSite;
    }
    public function validate(&$input) {
        $ret = true;
        if($this->customSite) {
            foreach ($input['marca'] as $i => $marca) {
                if($marca === '') {
                    $this->_errors[] = $input['sku'][$i];
                    $ret = false;
                }
            }
        }
        return $ret;
    }
    public function getErrorMsg($input) {
        return sprintf('Marca field is mandatory for all rows, but it is empty for the following products: %s ', implode(',', $input));
    }
}

