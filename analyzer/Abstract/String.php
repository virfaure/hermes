<?php
abstract class Abstract_String extends Abstract_Lexical {
    public static $MAX_LENGTH;
    public static $MIN_LENGTH;

    /**
     * Ensure the string length is bewteen the specified bounds
     *
     * @param $input string
     * @return bool 
     */
    public function validate(&$input) {
        parent::validate($input);
        $len = strlen($input);
        return ($len <= static::$MAX_LENGTH && $len >= static::$MIN_LENGTH);
    }

    /**
     * Return the detailed message explaining the error.
     *
     * @param $input
     * @return bool
     */
    public function getErrorMsg($input) {
        return sprintf('alphanumeric string between %s and %s characters, ', static::$MIN_LENGTH, static::$MAX_LENGTH) . parent::getErrorMsg($input);
    }
}
