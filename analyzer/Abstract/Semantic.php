<?php
abstract class Abstract_Semantic extends Abstract_Validator {
    protected $_dbLink;
    protected $_errors;
    protected $_result;
    protected static $FIELD;
    public static $QUERY;
    protected static $TYPE = 'validateExisting';
    const DEBUG = FALSE;

    function __construct($dbLink = null) {
        $this->_errors = array();
        if($dbLink) {
            $this->_dbLink = $dbLink;
        }
    }

    /*
     * Default validator
     * Ensure the extended class has defined the required values
     * Perform the query and pass the result to the proper method
     *
     * @param $input simple array with data
     * @return bool
     */
    public function validate(&$input) {

        if (!$this->_dbLink || !static::$QUERY || !static::$FIELD || !static::$TYPE) {
            $this->_errors[] = 'Error validating ' . get_class($this) . ', incomplete data was provided.';
            return false;
        }
        $field = static::$FIELD;
        $type = static::$TYPE;
        if(self::DEBUG) {
            printf(static::$QUERY . "\n", implode(array_map(array($this->_dbLink, "real_escape_string"), $input), '","'));
        }
        $list = $this->_dbLink->query(sprintf(static::$QUERY, implode(array_map(array($this->_dbLink, "real_escape_string"), $input), '","')));
        //Save wether the query result is empty or not
        $this->_result = (bool) $list;
        return $list && $this->$type($list, $field, $input);
    }

    /*
     * Ensure all requested items where present in the database
     * Otherwise, log all not found items
     *
     * @param list mysqli_result
     * @param $field field to check
     * @param $input array with data
     * @return bool
     */
    protected function validateExisting($list, $field, $input) {
        if(self::DEBUG) {
            print count($input) . " <-> " . $list->num_rows . "\n";
        }
        $ret = $list->num_rows == count($input);
        if(!$ret) {
            $found = array();
            while($obj = $list->fetch_object()){
                $found[] = $obj->$field;
            }
            $this->_errors = array_diff($input, $found);
        }
        return $ret;
    }

    /*
     * Return the errors found during the validation
     * @return array
     */
    public function getErrors() {
        return $this->_errors;
    }

    /**
     * Return the description message for the error.
     *
     * @param $input array
     * @return string
     */

    public function getErrorMsg($input) {
        $ret = false;
        //If the validation wasn't performed
        if(!$this->_result) {
            //Maybe the provided data was incomplete, or the query failed.
            $ret = $this->_errors ? reset($this->_errors) : 'Unexpected query result';
        } else if (static::$TYPE == 'validateExisting') {
            $exist = 'must exist';
        } else {
            $exist = 'cannot exist';
        }

        return $ret ?: sprintf('%s(s) "%s" %s on the database.', str_replace(array('Semantic_', 'Import'), '', get_class($this)), implode($input, ', '), $exist);
    }
}
