<?php
/**
 * Lexical_Image
 * Required field, valid image filenames or urls, comma separated.
 *
 * @uses Abstract_Lexical
 * @package
 * @version $id$
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net> 
 */
class Lexical_Image extends Abstract_Lexical {

    /**
     * Ensure that provided data doesn't contain any of the forbidden characters, split the data by the commas.
     *
     * @param $input string
     * @return bool 
     */
    public function validate(&$input) {
        //Look for forbidden characters
        $ret = (bool) ! preg_match('/[\%*|"<>^€$&{}@#~]/', $input);
        //Try to match filenames
        if($ret) {
            //$ret = (bool) preg_match_all('/((?:\w)+\.?(?:jpg|gif|png)?)/i', $input, $found);
            $ret = (bool) preg_match_all('/[^,\s-+]([^\,]*)[^,\s+-]*/', $input, $found);
            $input = implode(",", $found[0]);
            $this->_tokens[] = $found[0];
        }
        return $ret || $input === '';
    }

    public function getErrorMsg($input) {
        return 'required field, format: comma separated Image URLs or Filenames (valid extensions: jpg, png, gif, jpeg), ' . parent::getErrorMsg($input);
    }
}
