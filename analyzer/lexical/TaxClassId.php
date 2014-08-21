<?php
/**
 * Lexical_TaxClassId
 * Required, string with the format: IVA|VAT [digit][digit]%
 *
 * @uses Abstract_Lexical
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Lexical_TaxClassId extends Abstract_Lexical {
    public function validate(&$input) {
       /* $ret = preg_match('/((?:[IVAT]{3}) [0-9][0-9]?(?:,[0-9])?%)/', $input, $found);
        if($ret) {
            $this->_tokens[] = $found[1];
        }
        return (bool) $ret;*/
        
        /* 
         *  Update Virginie 18/02/2013, Validate by Xose
         *  We don't validate Tax format because it's different in each store
         *  So, we let the Semantical validate if it exists in DB
        */
        parent::validate($input);
        return $input != ''; //At least validate that the field is not empty
    }
    
    public function getErrorMsg($input) {
        return 'required field, format: "IVA|VAT (any number between 1 and 99)%", ' . parent::getErrorMsg($input);
    }
}

/**
 * Lexical_TaxClassIdSuper
 * Allow empty values
 *
 * @uses Lexical_TaxClassId
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net> 
 */
class Lexical_TaxClassIdSuper extends Lexical_TaxClassId {
    public function validate(&$input) {
        return parent::validate($input) || $input === '';
    }
}
