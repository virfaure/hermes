<?php
/**
 * Lexical_Visibility
 * Required, allowed values are any input that matches in $visibilities array, or any number between $MIN_N and $MAX_N
 *
 * @uses Abstract_Number
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Lexical_Visibility extends Abstract_Number {
    public static $MAX_N = 4;
    public static $MIN_N = 1;
    public static $visibilities = array('Not Visible Individually' => 1,
        'Catalogo' => 2, 'Catálogo' => 2,
        'Busqueda' => 3, 'Búsqueda' => 3,
        'Catalogo, Busqueda' => 4, 'Catálogo, Búsqueda' => 4, 'Catálogo Y Búsqueda' => 4);

    public function validate(&$input) {
        $input = ucwords(strtolower($input));
        $ret = isset(static::$visibilities[$input]);
        if($ret) {
            $input = static::$visibilities[$input];
        }
        return parent::validate($input) || $ret;
    }
    public function getErrorMsg($input) {
        return sprintf('allowed values: (%s), or ', implode(array_keys(static::$visibilities), ' | ')) . parent::getErrorMsg($input);
    }
}


/**
 * Lexical_VisibilitySuper
 * Allow empty values
 * 
 * @uses Lexical_Visibility
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net> 
 */
class Lexical_VisibilitySuper extends Lexical_Visibility {
    public function validate(&$input) {
        return parent::validate($input) || $input === '';
    }
}
