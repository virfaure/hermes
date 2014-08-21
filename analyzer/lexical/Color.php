<?php
class Lexical_Color extends Abstract_String {
   public static $MIN_LENGTH = 1;
   public static $MAX_LENGTH = 100;

    /**
     * Empty field is allowed, otherwise, must be a string between $MIN_LENGTH and $MAX_LENGTH
     * All the values are being normalized to ucfirst in order to prevent repeated values with different letter cases.
     *
     * @param mixed $input
     * @access public
     * @return bool
     */
    public function validate(&$input) {
        $input = ucfirst(strtolower($input));
        return parent::validate($input) || $input === '';
    }
}
