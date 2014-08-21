<?php
abstract class Abstract_Lexical extends Abstract_Validator {

    protected $_tokens;

    function __construct() {
        $this->_tokens = array();
    }

    public function getTokens() {
        return $this->_tokens;
    }
    public function validate(&$input) {
        $this->_tokens[] = $input;
    }
    /*
     * Return the last part of the error message
     * Can be called from a child class to complete the returned message.
     *
     * @param $input string
     * @return string
     */
    public function getErrorMsg($input) {
        return sprintf('but "%s" provided.', $input);
    }
}
?>
