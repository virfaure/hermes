<?php
/**
 * StockReset_Plugin
 *
 * @uses Magmi_GeneralImportPlugin
 * @copyright 2013 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class StockReset_Plugin extends Magmi_GeneralImportPlugin
{
    const RESET_ALL_PRODUCTS = 'UPDATE cataloginventory_stock_item item_stock LEFT JOIN cataloginventory_stock_status status_stock USING(product_id)
        SET item_stock.qty = 0, item_stock.is_in_stock = 0,
        status_stock.qty = 0, status_stock.stock_status = 0';
    const RESET_SUBSET = ' WHERE item_stock.product_id
        IN(
            SELECT entity_id FROM catalog_product_entity_int WHERE attribute_id=%s AND value IN("%s")
        )';

    const GET_ATTRIBUTE_ID = 'SELECT attribute_id FROM eav_attribute WHERE attribute_code="%s"';
    const GET_ATTRIBUTE_OPTION_ID = 'SELECT option_id FROM eav_attribute_option_value JOIN eav_attribute_option USING(option_id) WHERE attribute_id = %s AND value IN("%s")';

    function __construct()
    {

    }

    public function getPluginInfo()
    {
        return array(
            "name" => "Stock Reset",
            "author" => "Javier",
            "version" => "0.0.1",
        );
    }

    /**
     * getSubset
     *
     * @param mixed $attribute_code
     * @param mixed $attribute_options
     * @access private
     * @return void
     */
    private function getSubset($attribute_code, $attribute_options) {
        $ret = false;
        //Get unique array of the attribute column
        $vals = array_map('strtolower', array_unique(Importer::getColumn($attribute_code)));
        //Get cleaned array of settings
        $options = array_map('trim', explode(',', strtolower($attribute_options)));
        $options = array_filter($options); //Filter empty values
        
        //Get matches
        if(empty($options)){
            $current_value = $vals;
        }else{
            $current_value = array_intersect($options, $vals);
        }
        
        if(!empty($current_value)) {
            //Retrieve the ids
            $attribute_id =  $this->selectone(sprintf(self::GET_ATTRIBUTE_ID, $attribute_code), null, 'attribute_id');
            $attribute_options =  $this->selectAll(sprintf(self::GET_ATTRIBUTE_OPTION_ID, $attribute_id, implode('", "', $current_value)));
            $options = array();
            foreach($attribute_options as $attribute) {
                $options[] = $attribute['option_id'];
            }
            $ret = sprintf(self::RESET_SUBSET, $attribute_id, implode('","', $options));

        }

        //Disable the update if more than one brand was found on the CSV
        if(sizeOf($vals) > 1 && $attribute_code == 'marca') {
            $ret = false;
        }
        return $ret;
    }

    /**
     * startImport
     *
     * @access public
     * @return void
     */
    public function startImport()
    {
        //By the default reset stock of all products.
        $typeMsg = 'all';
        $sql = self::RESET_ALL_PRODUCTS;


        $attribute = $this->getParam("STOCKRESET:attribute",false);
        $value = $this->getParam("STOCKRESET:values",false);

        //If filter parameters ar set, apply filter
        if($attribute) {
            $filter = $this->getSubset($attribute, $value);
            if($filter) {
                $sql .= $filter;
                $typeMsg = 'subset of ' . $attribute . '(' . $value . ')';
            } else {
                //Exit if something went wrong
                return;
            }
        }


        try {
            $this->log(sprintf("Reseting stock for %s products", $typeMsg), "debug");
            $this->log("SQL Query: " . $sql, "debug");
            $this->exec_stmt($sql);
        }
        catch(Exception $e)
        {
            $this->log("Unable to reset stock!","warning");
        }
    }

    public function getPluginParamNames()
    {
        return array("STOCKRESET:attribute", "STOCKRESET:values");
    }
}
