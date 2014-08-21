<?php
/**
 * Lexical_Store
 * Required, string between $MIN_LENGTH and $MAX_LENGTH
 *
 * @uses Abstract_String
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Lexical_Store extends Abstract_Lexical {

     /**
     * Find all the valid store, which must be comma separated (and optionally, space after comma is allowed)
     *
     * @param $input string
     * @return bool
     */
    public function validate(&$input) {
        $stores = preg_split('/[\s]*[,][\s]*/', $input);
        $this->_tokens[] = $stores;
        $ret = !empty($stores);
        if($ret) {
            foreach ($stores as $store) {
                $ret &= is_string($store) && !is_numeric($store);
                if(!$ret) {
                    break;
                }
            }
        }
        return (bool) $ret;
    }

    /**
     * Return the detailed message explaining the error
     * @param $input string
     * @return string
     */
    public function getErrorMsg($input) {
        return 'required field, format: Store1, Store2, Store3... (comma separated strings) ' . parent::getErrorMsg($input);
    }


}
