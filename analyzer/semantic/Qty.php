<?php
/**
 * Semantic_Qty
 * Ensure the QTY field is filled at least for all simple products, unless the store !== 'admin' or manage_stock == 0
 *
 * @uses Lexical_Qty
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Semantic_Qty extends Abstract_Semantic {

    public function validate(&$input) {
        $ret = true;
        $this->errors = array();
        foreach($input['qty'] as $product => $qty) {
            $valid = $qty !== '' ||
                $input['type'][$product] === 'configurable' ||
                ((isset($input['store']) && !in_array('admin', $input['store'][$product], true)) ||
                (isset($input['manage_stock']) && $input['manage_stock'][$product] === '0'));

            if(!$valid) {
                $this->errors[$product] = $input['sku'][$product];
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
        return sprintf('Qty be empty only if product type = "configurable", manage_stock = 0, or store != "admin". The following products have qty missing:  %s.', implode(array_values($errors), ', '));
    }
}
