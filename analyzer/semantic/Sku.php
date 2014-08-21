<?php
/**
 * Semantic_Sku
 * The default validation is:
 * - The concatenation of the columns  Sku + Store must be an unique identifier
 *   (if store doesn't exist, the Sku column must be unique)
 * - All the specified skus must already exist on the database (except for the import mode).
 *
 * @uses Abstract_Semantic
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Semantic_Sku extends Abstract_Semantic {

    public static $QUERY = 'SELECT sku from catalog_product_entity where sku in ("%s")';
    protected static $FIELD = 'sku';
    protected $_errorText = null;

    public function validate(&$input) {
        $ret = false;
        //If store field is set, ensure sku+store is unique
        if(isset($input['input']['store'])) {
            $tokens = array();
            foreach($input['input']['sku'] as $ind => $sku) {

                foreach($input['input']['store'][$ind] as $ind2 => $store) {
                    $identifier = $sku . '+' . $store;

                    $ret = isset($tokens[$identifier]);
                    $tokens[$identifier] = 1;

                    if($ret) {
                        $this->_errorText = 'Sku + Store must be unique, but "' . $identifier . '" is duplicated.';
                        break;
                    }
                }
            }
        } else {
            //Ensure Sku field is  unique
            $ret = !(count($input['input']['sku']) === count($input['unique']));
            if($ret) {
                //Get the list of duplicated skus
                $this->_errorText = 'Sku must be unique, but "' .
                    implode(
                        array_unique(
                            array_diff_assoc($input['input']['sku'], $input['unique'])
                        ),
                        ', ') . '" is duplicated.';

            }

        }

        return !$ret && parent::validate($input['unique']);
    }
    /*
     * validateNotExisting
     * Method that allows to verify that the query did not return any row
     * Otherwise, log all rows
     *
     * @param $list mysqli_result
     * @param $field field to check
     * @param $input array with data
     * @return bool
     */
    protected function validateNotExisting($list, $field, $input) {
        $ret = $list->num_rows == 0;
        if(!$ret) {
            while($obj = $list->fetch_object()) {
                $this->_errors[] = $obj->$field;
            }
        }
        return $ret;
    }

    public function getErrorMsg($input) {
        if($this->_errorText) {
            $ret = $this->_errorText;
        } else {
            $ret = parent::getErrorMsg($input);
        }
        return $ret;
    }
}

/**
 * Semantic_SkuImport
 * For the importations, ensure that the specified skus doesn't exist on the database.
 *
 * @uses Semantic_Sku
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Semantic_SkuImport extends Semantic_Sku {
    protected static $TYPE = 'validateNotExisting';
}
