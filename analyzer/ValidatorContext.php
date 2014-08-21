<?php
/**
 * ValidatorContext In charge of instanciate the proper validator, according to the given profile and analysis type
 *
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class ValidatorContext {

    private $_strategy;

    private static $_CUSTOM_STRATEGIES = array(
        'update' => array('lexical' => array('sku' => 1, 'price' => 1), 'semantic' => array('price' => 1, 'weight' => 1)),
        'import' => array('lexical' => array('sku'=> 1, 'price' => 1), 'semantic' => array('sku' => 1 )),
        'super' => array('lexical' =>
            array('sku' => 1, 'name' => 1, 'attribute_set' => 1, 'category_ids' => 1, 'price' => 1, 'visibility' => 1,
                'status' => 1, 'tax_class_id' => 1, 'type' => 1, 'qty' => 1, 'weight' => 1, 'ean' => 1),
        'semantic' =>
            array('attribute_set' => 1, 'configurable_attributes' => 1, 'image' => 1, 'sku' => 1, 'tax_class_id' => 1, 'price' => 1)
        )
    );

    private $_warnings;

    /*
     * Create the instance of the validator by building the class name with the ucwords format: "Type_StrategyFieldName"
     * Generate a warning if the validator wasn't found, and create a generic validator instance
     *
     * @param $type string type of analysis
     * @param $strategy string validator strategy class name
     * @param $optData array optional data passed to the semantic validators
     * @return void
     */
    function __construct($type, $strategy, $profile, $optData = null) {
        $this->_warnings = '';
        $validator = $this->assignStrategy($type, $strategy, $profile);
        $validator = ucfirst($type) . '_' . str_replace(' ', '', ucwords(str_replace('_', ' ', $validator)));
        if(class_exists($validator)){
            $this->_strategy = new $validator($optData);
        }else {
            $this->_strategy = new Lexical_Generic();
            $this->_warnings .= sprintf('Strategy %s (%s) is not implemented for the %s analizer ' . "\n", $strategy, $validator, $type);
        }
    }

    /**
     * Remove elements from memory
     * @return void
     */
    function __destruct() {
        unset($this->_strategy);
    }

    /**
     * Return the tokens retrieved from the validator
     * @return array
     */
    public function getTokens() {
        return $this->_strategy->getTokens();
    }

    /**
     * Return the errrors found during the validation
     * @return array
     */
    public function getErrors() {
        return $this->_strategy->getErrors();
    }

    /**
     * Return the warnings found during the validation
     * @return array
     */
    public function getWarnings() {
        return $this->_warnings;
    }

    /**
     * Return the errror description to help the user to solve the problem
     * @param $input string|array
     * @return stringg
     */
    public function getErrorMsg($input) {
        return $this->_strategy->getErrorMsg($input);
    }

    /**
     * Assign a custom strategy depending on the profile and the type, if neccessary.
     * If a custom strategy is not specified, the default will be chosen.
     * In order to implement a custom strategy, just add the field to the $_CUSTOM_STRATEGIES array,
     * And create a validator with the format $strategy$profile, ie. skuImport, skuSpecial.
     * If the custom validator is not found, a generic validator strategy will be chosen (return true).
     *
     * @param $type (semantic|lexic)
     * @param $strategy string
     * @param $profile (import|update)
     * @return string
     *
     */
    protected function assignStrategy($type, $strategy, $profile) {
        $ret = $strategy;
        //Uncomment the line below to debug
        //echo "$profile $type $strategy " . var_export(isset(self::$_CUSTOM_STRATEGIES[$profile][$type][$strategy]), true) . "\n";
        if (isset(self::$_CUSTOM_STRATEGIES[$profile][$type][$strategy])) {
            $ret = $strategy . ucfirst($profile);
        }
        return $ret;
    }

    /**
     * @param $input mixed input data of the csv
     * @return bool whether this data is valid or not for current validator
     */
    public function validate(&$input) {
        //Uncomment the line below to debug
        //echo get_class($this->_strategy) . ": " . $input . "\n";

        return $this->_strategy->validate($input);
    }

}

