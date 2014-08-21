<?php
/**
 * Lexical_Categories
 *
 * @uses Abstract_Lexical
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Lexical_Categories extends Abstract_Lexical {
    public function validate(&$input) {
        $tree_sep = Magmi_Config::getInstance()->get("GLOBAL","tree_sep");
        $categories = explode(';;', $input);
        $input = '';
        $corrected_input = array();
        //Trim all the spaces
        foreach($categories as $category) {
            $subcategories = preg_replace("#([^\\\]){$tree_sep}#", '\1->', $category);
            $subcategories = explode('->', $subcategories);
            $path = array_values(array_filter(array_map('trim', $subcategories)));
            $corrected_input[] = implode($tree_sep, $path);

        }
        //Rebuild the path
        $input = implode(';;', $corrected_input);
        $this->_tokens[] = $corrected_input;
        return true; //Also allow empty values
    }

    /**
     * Return the detailed message explaining the error
     * @param $input string
     * @return string
     */
    public function getErrorMsg($input) {
        return 'required field, format: category1/subcategory1;;categoryN/subcategoryN, ' . parent::getErrorMsg($input);
    }
}
