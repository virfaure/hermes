<?php

/**
 * Class Magmi_ProductSavePlugin
 * @author virge
 *
 * This class is a sample for item processing
 */
class Magmi_ProductSavePlugin extends Magmi_GeneralImportPlugin
{

    protected static $_arrProductID = array();

    public function getPluginInfo()
    {
        return array(
            "name" => "Product Save from Magento",
            "author" => "Virge",
            "version" => "0.0.1",
        );
    }

    public function afterImport()
    {
        $this->log("Saving Products From Magento","info");
        $magdir=Magmi_Config::getInstance()->getMagentoDir();
        $project = HermesHelper::getProject();
        $tmpFile = $magdir . '/hermes/upload/save.' . microtime(true) . '.txt';
        file_put_contents($tmpFile, implode(',', Importer::getProductSku()));
        chmod($tmpFile, 0777);
        DJJob::enqueue(new SaveProductJob($project, $tmpFile, $magdir), $project);
        return true;
    }

}
