<?php
require_once dirname(__FILE__) . "/init.php";

class SaveProductJob {
    public function __construct($name, $pathSkus, $pathMagento) {
        $this->name = $name;
        if(!file_exists($pathSkus)) {
            die(sprintf('[%s] The file %s was not found for the project: %s, aborting.', date('c'), $pathSkus, $name));
        }
        //Contains all productSku to Save
        $this->pathSkus = $pathSkus;
        $this->pathMagento = $pathMagento;
    }
    /**
     * initMagento
     * We need to call reset() to re-initialize the db connection in case of failure
     *
     * @access private
     * @return void
     */
    private function initMagento() {
        Mage::reset();
        Mage::app();

        //FIX FOR OLDER MAGENTO VERSIONS
        if(defined('Mage_Core_Model_App_Area::AREA_ADMINHTML')) {
            // Alan Storm suggestion to load specific area in Magento (frontend)
            Mage::app()->loadArea(Mage_Core_Model_App_Area::AREA_ADMINHTML);
        } else {
            Mage::app()->loadAreaPart(Mage_Core_Model_App_Area::AREA_GLOBAL);
        }
        echo 'Mage has been successfully initialized' . "\n";
    }

    public function perform($job_id) {

        $tstart=microtime(true);

        $status = HermesHelper::getCurrentStatus($this->pathMagento);
        echo "Save Product Skus!\n";
        require_once $this->pathMagento.'app/Mage.php';
        $this->initMagento();

        $arrProductSku = explode(',', file_get_contents($this->pathSkus));
        $total = sizeOf($arrProductSku);
        $i = 0;
        while($sku = array_pop($arrProductSku)) {
            try {

                $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
                if($product && $product->getId()){
                    // Save Product with little hack
                    $product = Mage::getModel('catalog/product')->load($product->getId());
                    $product
                        ->setForceReindexRequired(true)
                        ->setIsChangedCategories(true);

                    //Product save, to call observers
                    $product->save();

                    // Update Stock
                    $product->getStockItem()->save();

                    //Reindex stock
                    $stockItem = Mage::getModel('cataloginventory/stock_item')
                        ->loadByProduct($product->getId());
                    $stockItem->setForceReindexRequired(true);
                    Mage::getSingleton('index/indexer')->processEntityAction(
                        $stockItem,
                        Mage_CatalogInventory_Model_Stock_Item::ENTITY,
                        Mage_Index_Model_Event::TYPE_SAVE
                    );

                    Mage::getSingleton('index/indexer')->processEntityAction(
                        $product,
                        Mage_Catalog_Model_Product::ENTITY,
                        Mage_Index_Model_Event::TYPE_SAVE
                    );

                }
                //Update progress each X products
                if(++$i % 20 == 0) {
                    printf("[%s] Updating progress with %s products left.\n", date('c'), count($arrProductSku));
                    DJJob::updateProgress($job_id, $this, $i / $total * 100);
                    //Remove the already saved products from file.
                    file_put_contents($this->pathSkus, implode(',', $arrProductSku));
                }
            } catch(Exception $e) {
                printf("[%s] Error processing product %s: %s\n", date('c'), $sku, $e->getMessage());
                //Typically, a timeout mysql error, reconnect after a while
                sleep(5);
                $this->initMagento();
            }
        }

        //Remove the file
        @unlink($this->pathSkus);
        $tend=microtime(true);
        $time_taken = round($tend-$tstart, 2);
        echo "done in " . $time_taken . " secs\n";
        DJJob::updateStats($this->name, 'product_save', $time_taken / $total);
        $job_data = array($total, $time_taken, get_class($this));
        HermesHelper::saveImportData($this->name, $status, $job_data);
    }
}
$save_product = new SaveProductJob($argv[1], $argv[2], $argv[3]);
$save_product->perform($argv[4]);
