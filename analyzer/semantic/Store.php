<?php
/**
 * Semantic_Store
 * Ensure that all the stores already exist on the database.
 *
 * @uses Abstract_Semantic
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Semantic_Store extends Abstract_Semantic {

    public static $QUERY = 'SELECT code from core_store where code in ("%s")';
    protected static $FIELD = 'code'; 
}
