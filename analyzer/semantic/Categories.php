<?php

class Semantic_Categories extends Abstract_Semantic {

    const DEBUG = false;

    const GET_ROOT_CATEGORIES = 'SELECT distinct(cce.path) as path
        FROM core_store as cs
        JOIN core_store_group as csg on csg.group_id=cs.group_id
        JOIN catalog_category_entity as cce ON cce.entity_id=csg.root_category_id
        JOIN eav_attribute as ea ON ea.attribute_code="name" AND ea.entity_type_id=cce.entity_type_id
        JOIN catalog_category_entity_varchar as ccev ON ccev.attribute_id=ea.attribute_id AND ccev.entity_id=cce.entity_id';

    const GET_ATTRIBUTE_ID = 'SELECT attribute_id FROM eav_attribute
        WHERE entity_type_id=(select distinct(entity_type_id) from catalog_category_entity) AND attribute_code="name"';

    const GET_CATEGORIES_DATA = 'SELECT cce.entity_id, LOWER(value) name, parent_id, path, store_id FROM catalog_category_entity cce JOIN catalog_category_entity_varchar ccev
        USING(entity_id) WHERE attribute_id = %s AND value in("%s") and store_id in (select store_id from core_store where code in("%s"))';

    protected $root_cats;
    protected $map;
    protected $map_cats;
    protected $storeCode;
    protected $attribute_id;
    protected $tree_sep;

    function __construct($dbLink = null) {
        $this->map = array();
        $this->map_cats = array();
        $this->root_cats = array();
        //Default store is admin by default
        $this->storeCode = array('admin');
        parent::__construct($dbLink);
        $this->tree_sep = Magmi_Config::getInstance()->get("GLOBAL","tree_sep");
    }

    private function debug($msg) {
        if(self::DEBUG) {
            echo $msg . "<br>";
        }
    }

    /**
     * checkPath
     * Given the path and the database results for its categories, check if it's valid.
     * TODO: Maybe we might cache the already found patchs to improve speed and avoid querying the database for already stored categories data.
     *
     * @param mixed $path
     * @param mixed $path_cats
     * @access protected
     * @return bool
     */
    protected function checkPath($path, $path_cats) {
        $res = false;
        //Create map of categories for the given path
        while($cat = $path_cats->fetch_object()) {
            $this->map[$cat->path] = $cat->name;
            $this->map_cats[$cat->entity_id] = $cat->name;
        }
        $this->debug('<pre>' . var_export($this->map, true));
        $this->debug('<pre>' . var_export($this->map_cats, true));

        //Initialize all the possible paths (usually only one, but the root might change per store/website)
        $possibilities = array();
        foreach ($this->root_cats as $root) {
            $possibilities[] = $root;
        }

        $path = str_replace('\\/', '/', preg_replace("#([^\\\]){$this->tree_sep}#", '\1->', $path));
        $this->debug('Checking path: ' . $path);
        foreach (explode('->', $path) as $cat) {
            $this->debug("Current category: " . $cat);
            foreach($possibilities as $i => &$possibility) {
                $valid = false;
                $this->debug("Checking possibility:" . $possibility);
                //Iterate over the ids of the current category
                foreach (array_keys($this->map_cats, $cat) as $cat_id) {
                    $try = $possibility . '/' . $cat_id;
                    //If a valid path exists, we append the cat to the path
                    if(isset($this->map[$try])) {
                        $this->debug("Valid path found: " . $try);
                        $possibility = $try;
                        $valid = true;
                        break;
                    }
                }

                //if no matches, we can discard this possibility
                if(!$valid) {
                    $this->debug("Discarding path: " . $possibility);
                    unset($possibilities[$i]);
                }
            }
        }
        $found = sizeOf($possibilities);
        /**
         * There should be at least one valid path.
         * TODO: What if valid path found, but the starting path doesn't match this store/website?
         */
        if($found >= 1) {
            $name = $this->map[reset($possibilities)];
            $basename = array_pop(explode('->', $path));
            $res = $basename === $name;
        }
        $this->debug('Final path: ' . implode(',', $possibilities) . " -> " . ($res ? $name : 'Not found'));
        return $res;
    }

    protected function initData() {
        //Category Name Attribute ID
        $name = $this->_dbLink->query(self::GET_ATTRIBUTE_ID);
        $this->attribute_id = $name->fetch_object()->attribute_id;

        //Starting root paths, usually only one, but some sites have several pathes, if they change per website (ie. runnering)
        $root_cats = $this->_dbLink->query(self::GET_ROOT_CATEGORIES);
        while($cat = $root_cats->fetch_object()) {
            $this->root_cats[] = $cat->path;
        }
    }

    /**
     * validate
     * Iterate over the unique array of paths and ensure that all of them exist,
     * loging the error otherwhise.
     *
     * @param mixed $input
     * @access public
     * @return bool
     */
    public function validate(&$input) {
        $this->initData();
        $valid = true;
        //TODO: Think of a smart way of validating if there are several stores on the same csv.
        if(count($input['stores']) == 1) {
            $this->storeCode[] = reset($input['stores']);
        }
        foreach ($input['unique'] as $path) {
            $path = mb_strtolower($path, 'UTF-8');
            $this->debug(sprintf(
                self::GET_CATEGORIES_DATA, 
                $this->attribute_id, 
                str_replace('\\'.$this->tree_sep, $this->tree_sep, preg_replace("#([^\\\]){$this->tree_sep}#", '\1", "', $path)), 
                implode('", "', $this->storeCode)
            ));
            $path_cats = $this->_dbLink->query(sprintf(self::GET_CATEGORIES_DATA, $this->attribute_id, str_replace('\\'.$this->tree_sep, $this->tree_sep, preg_replace("#([^\\\]){$this->tree_sep}#", '\1", "', $path)), implode('", "', $this->storeCode)));
            if(!$this->checkPath($path, $path_cats)) {
                $this->_errors[] = $path;
                $valid = false;
            }
        }
        return $valid;
    }

    public function getErrorMsg($input) {
        return sprintf('Every category path must exist, but the following invalid paths were provided: "%s".', implode('","', $input));
    }
}

/**
 * Semantic_CategoriesSuper
 * Allow empty values
 *
 * @uses Semantic_Categories
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Semantic_CategoriesSuper extends Semantic_Categories {

    public function validate(&$input) {
        return parent::validate($input) || $input === '';
    }
}
