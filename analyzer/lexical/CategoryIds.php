<?php
/**
 * Lexical_CategoryIds
 * The field must contain only comma-separated integers
 *
 * @uses Abstract_Lexical
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Lexical_CategoryIds extends Abstract_Lexical {

    /**
     * Find all the valid category_ids, which must be comma separated (space after comma is allowed)
     * Optionally, '::' separator can be present for product position setting
     *
     * @param $input string
     * @return bool
     */
    public function validate(&$input) {
        $categories = preg_split('/[\s]*[,][\s]*/', $input);
        $this->_tokens[] = $categories;
        $ret = !empty($categories);
        if($ret) {
            foreach ($categories as $cat) {
                list($cat, $pos) = explode('::', $cat);
                $ret &= is_numeric($cat) && is_int($cat * 1);
                if(!$ret) {
                    break;
                }
            }
        }
        return (bool) $ret;
    }

    /**
     * Return the detailed message explaining the error
     * @param $input string
     * @return string
     */
    public function getErrorMsg($input) {
        return 'required field, format: CategoryId1, CategoryId2, CategoryId3... (comma separated integer numbers) ' . parent::getErrorMsg($input);
    }
}

/**
 * Lexical_CategoryIdsSuper
 * Allow empty field
 *
 * @uses Abstract_Lexical
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
 
class Lexical_CategoryIdsSuper extends Lexical_CategoryIds {
    public function validate(&$input) {
        $categories = preg_split('/[\s]*[,][\s]*/', $input);
        $this->_tokens[] = $categories;
        
        return true;
    }
}
