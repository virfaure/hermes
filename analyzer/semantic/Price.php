<?php
/**
 * Semantic_Price
 * Ensure the Price field is filled for all products, except for the configurables if the $emptyConfigurables flag is enabled
 *
 * @uses Lexical_Price
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Semantic_Price extends Lexical_Price {

    /**
     * Only empty for stores different from admin if it's an update.
     */
    protected static $isUpdate = false;

    function __construct($emptyConfigurables = false) {
        $this->emptyConfigurables = $emptyConfigurables;
    }
    public function validate(&$input) {
        $ret = true;
        $this->errors = array();
        foreach($input['price'] as $product => $price) {
            $valid = parent::validate($price) ||
                   (($price === '' && $input['type'][$product] === 'configurable' && $this->emptyConfigurables) ||
                   (static::$isUpdate && isset($input['store']) && !in_array('admin', $input['store'][$product], true)));
            if(!$valid) {
                $this->errors[$input['sku'][$product]] = $price;
            }
            $ret &= $valid;
        }
        return $ret;
    }
    public function getErrors() {
        return $this->errors;
    }
    public function getErrorMsg($input) {
        $errors = $this::getErrors();
        return sprintf('Skus(s) %s, numeric value between %s and %s, but "%s" provided.', implode(array_keys($errors), ', '), static::$MIN_N, static::$MAX_N, implode($errors, '", "'));
    }
}
class Semantic_PriceUpdate extends Semantic_Price {
    protected static $isUpdate = true;
}
