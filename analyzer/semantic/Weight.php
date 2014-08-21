<?php
/**
 * Semantic_Weight
 * Ensure the Weight field is filled at least for all simple products
 *
 * @uses Lexical_Weight
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net> 
 */
class Semantic_Weight extends Lexical_Weight {

    /**
     * Only empty for stores different from admin if it's an update.
     */
    protected static $isUpdate = false;

    public function validate(&$input) {
        $ret = true;
        $this->errors = array();
        foreach($input['weight'] as $product => $weight) {
            $valid = parent::validate($weight) || 
                (
                    $weight === '' && $input['type'][$product] === 'configurable' || 
                    (static::$isUpdate && isset($input['store']) && 
                    !in_array('admin', $input['store'][$product], true)
                )
            );

            if(!$valid) {
                $this->errors[$input['sku'][$product]] = $weight;
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

/**
 * Semantic_WeightUpdate
 * If It's an update, the field might be empty if the store !== admin
 * 
 * @uses Semantic
 * @uses _Weight
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net> 
 */
class Semantic_WeightUpdate extends Semantic_Weight {
    protected static $isUpdate = true;
}
