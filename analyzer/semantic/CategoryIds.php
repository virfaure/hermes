<?php
class Semantic_CategoryIds extends Abstract_Semantic {

    public static $QUERY = 'SELECT distinct entity_id from catalog_category_entity where entity_id in ("%s")';
    protected static $FIELD = 'entity_id'; 
}
