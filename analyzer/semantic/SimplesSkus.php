<?php
/**
 * Semantic_SimpleSkus
 * All skus specified on this field must exist on the CSV or on the Database
 * This field will be ignored for all simple products.
 *
 * @uses Abstract_Semantic
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Semantic_SimplesSkus extends Abstract_Semantic {

    const UNIQUE_ERROR = 'SimplesSkus must be unique, but the following skus are repeated: %s.';
    const SIMPLES_SKUS_ERROR = 'SimplesSkus must be set and can\'t be empty for the following configurable products: %s.';
    const SKU_NOT_FOUND_ERROR = '%s(s) "%s" must exist either in the CSV or in the database.';

    public static $QUERY = 'SELECT sku from catalog_product_entity where sku in ("%s")';
    protected static $FIELD = 'sku';



    /**
     * validate
     * Check all the simples_skus fields for each configurable product, ensuring that
     * all the specified skus exist, wether in the DB or in the CSV files.
     *
     * @param mixed $input
     * @access public
     * @return bool
     */
    public function validate(&$input) {
        $ret = true;
        $query = array();
        //Check that there are no repeated skus on the simples-skus column
        $repeated = array_unique(array_diff_assoc($input['merged'], array_unique($input['merged'])));
        if(count($repeated) > 0) {
            $this->_errorText = sprintf(self::UNIQUE_ERROR, implode($repeated, ', '));
            $ret = false;
        }
        foreach (array_keys($input['input']['type'], 'configurable') as $index) {
            if(!isset($input['input']['simples_skus']) || sizeOf($input['input']['simples_skus'][$index]) == 0) {
                $this->_errorText = sprintf(self::SIMPLES_SKUS_ERROR, $input['input']['sku'][$index]);
                $ret = false;
                break;
            }
            //Check if all skus are present in the CSV
            $exist = array_diff($input['input']['simples_skus'][$index], $input['input']['sku']);
            $query = array_merge($query, $exist);
        }
        if(count($query) > 0) {
            //If it's an allinone operation the skus might be on the imported array
            $pendingExist = array_diff($query, isset($input['imported']) ? $input['imported'] : array());
            //If they aren't on this CSV nor in the imported array, check the DB
            if(!empty($pendingExist)) {
                $ret = $ret && parent::validate($pendingExist);
            }
        }
        return $ret;
    }

    /**
     * getErrorMsg
     * Retrieve a proper error explaining that the given skus must be exist (db or csv)
     *
     * @param array $input
     * @access public
     * @return string
     */
    public function getErrorMsg($input) {
        if(isset($this->_errorText)) {
            $ret = $this->_errorText;
        } else {
            $ret = sprintf(self::SKU_NOT_FOUND_ERROR, str_replace(array('Semantic_', 'Import'), '', get_class($this)), implode($input, ', '));
        }
        return $ret;
    }
}
