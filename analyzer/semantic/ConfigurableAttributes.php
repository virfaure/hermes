<?php
/**
 * Semantic_ConfigurableAttributes
 * All configurable attributes must exist and must belong to the attribute set specified.
 * Also, each attribute must have a non-null value, except for the configurable products
 *
 * @uses Abstract_Semantic
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Semantic_ConfigurableAttributes extends Abstract_Semantic {

    const INVALID_ATTRIBUTE_ERROR = 'Configurable attribute(s): "%s" must belong to the Attribute Set specified: %s, at product: %s';
    const EMTPY_ERROR = 'Configurable attribute column can\'t be empty for the following product: %s';
    const NULL_ERROR = 'Configurable attribute "%s" can\'t be empty for the following product: %s';

    protected static $SUB_QUERY = 'SELECT distinct(attribute_code) FROM eav_entity_attribute eea join eav_attribute ea using(attribute_id)
        join eav_attribute_set eas using(attribute_set_id) where attribute_set_name="%s" and attribute_code in("%s")';
    protected static $FIELD = 'attribute_code';

    protected $_errorText;

    /**
     * validateEmpty
     * If any configurable doesn't have a value in the configurable_attributes field, there's an error and we shouldn't continue
     *
     * @param mixed $input
     * @param mixed $attributes
     * @access protected
     * @return bool
     */
    protected function validateEmpty($input, $index, $attributes) {
        $ret = true;
        if($input['type'][$index] == 'configurable' && sizeOf($attributes) == 0) {
            $this->_errorText = sprintf(self::EMTPY_ERROR, $input['sku'][$index]);
            $ret = false;
        }
        return $ret;
    }

    /**
     * validateExistAndBelong
     * If the attribute doesn't exist or doesn't belong to the attribute_set specified, there's an error and we shouldn't continue
     *
     * @param mixed $input
     * @param mixed $index
     * @param mixed $attributes
     * @access protected
     * @return bool
     */
    protected function validateExistAndBelong($input, $index, $attributes) {
        static::$QUERY = sprintf(self::$SUB_QUERY, $input['attribute_set'][$index] ,'%s');
        $ret = parent::validate($attributes);
        if(!$ret) {
            $this->_errorText = sprintf(self::INVALID_ATTRIBUTE_ERROR, '%s', $input['attribute_set'][$index], $input['sku'][$index]);
        }
        return $ret;

    }

    /**
     * validateNotNull
     * Ensure each attribute has a non-null value, unless it's a configurable product
     *
     * @param mixed $input
     * @param mixed $index
     * @param mixed $attributes
     * @access protected
     * @return bool
     */
    protected function validateNotNull($input, $index, $attributes) {
        $ret = true;
        foreach ($attributes as $item) {
            //echo "Checking $item $index\n" . $input[$item][$index] . "\n";
            $ret = ((isset($input[$item][$index]) && trim($input[$item][$index]) != '') || $input['type'][$index] == 'configurable');
            if(!$ret) {
            $this->_errorText = sprintf(self::NULL_ERROR, $item, $input['sku'][$index]);
                break;
            }
        }
        return $ret;
    }

    /**
     * validate
     *
     * @param mixed $input
     * @access public
     * @return bool
     */
    public function validate(&$input) {
        $ret = true;
        //Check that all configurable_attributes exist and belong to the specified attribute_set
        foreach ($input['configurable_attributes'] as $index => $attributes) {
            if(!$this->validateEmpty($input, $index, $attributes)
                || !$this->validateExistAndBelong($input, $index, $attributes)
                || !$this->validateNotNull($input, $index, $attributes)) {

                    $ret = false;
                    break;
                }
        }
        return $ret;
    }

    /**
     * getErrorMsg
     *
     * @param mixed $input
     * @access public
     * @return string
     */
    public function getErrorMsg($input) {
        $ret = sprintf($this->_errorText, implode($input, '", "'));
        return $ret;
    }
}
