<?php
abstract class Abstract_Validator {
        /*
         * Tell whether the data is correct or not
         * Might change (correct) the $input value
         *
         * @return bool
         */
        abstract function validate(&$input);
        /*
         * Return a string with the details of the error and/or the allowed values for the current field.
         *
         * @param $input string|array
         * @return string
         */
        abstract function getErrorMsg($input);

}
?>
