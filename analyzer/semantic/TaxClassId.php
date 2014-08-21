<?php
/**
 * Semantic_TaxClassId
 * Ensure that all the TaxClassId already exist on the database
 *
 * @uses Abstract_Semantic
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Semantic_TaxClassId extends Abstract_Semantic {

    public static $QUERY = 'SELECT distinct(class_name) class_name from tax_class WHERE class_type = "PRODUCT" AND class_name in("%s")';
    protected static $FIELD = 'class_name';
}
