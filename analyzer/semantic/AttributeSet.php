<?php
/**
 * Semantic_AttributeSet 
 * Ensure the specified AttributeSet exists in the database
 *
 * @uses Abstract_Semantic
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net> 
 */
class Semantic_AttributeSet extends Abstract_Semantic {

    public static $QUERY = 'SELECT distinct(attribute_set_name) from eav_attribute_set where attribute_set_name in("%s")';
    protected static $FIELD = 'attribute_set_name'; 
}
