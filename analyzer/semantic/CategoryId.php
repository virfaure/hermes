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
class Semantic_CategoryId extends Abstract_Semantic {

    public static $QUERY = 'SELECT entity_id from catalog_category_entity where entity_id in ("%s")';
    protected static $FIELD = 'entity_id';
    protected $_errorText = null;

    public function validate(&$input) {
        $ret = false;

        //Ensure category_id is unique
        $ret = !(count($input['input']['category_id']) === count($input['unique']));
        if($ret) {
            //Get the list of duplicated category_id
            $this->_errorText = 'Category_id must be unique, but "' .
                implode(
                    array_unique(
                        array_diff_assoc($input['input']['category_id'], $input['unique'])
                    ),
                    ', ') . '" is duplicated.';

        }

        return !$ret;
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
 *//*
class Semantic_CategoryIdImport extends Semantic_CategoryId {
    protected static $TYPE = 'validateNotExisting';
}*/
