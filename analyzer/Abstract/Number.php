<?php
abstract class Abstract_Number extends Abstract_Lexical {
    public static $MAX_N;
    public static $MIN_N;

    /**
     * Ensure the number is bewteen the specified bounds
     *
     * @param $input number
     * @return bool
     */
    public function validate(&$input) {
        $input = str_replace(',', '.', $input);
        parent::validate($input);
        return (is_numeric($input) && $input >= static::$MIN_N && $input <= static::$MAX_N);
    }

    /**
     * Return the detailed message explaining the error.
     *
     * @param $input
     * @return bool
     */
    public function getErrorMsg($input) {
        return sprintf('numeric value between %s and %s, ', static::$MIN_N, static::$MAX_N) . parent::getErrorMsg($input);
    }
}
