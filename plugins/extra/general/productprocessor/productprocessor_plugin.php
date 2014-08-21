<?php
/**
 * Magmi_ProductAutomaticProcessorPlugin
 *
 * @uses Magmi_GeneralImportPlugin
 * @copyright 2013 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class ProductProcessor_Plugin extends Magmi_GeneralImportPlugin
{

    protected $magdir;
    protected $project;

    function __construct() {
        $this->magdir = Magmi_Config::getInstance()->getMagentoDir();
        $this->project = HermesHelper::getProject();
    }

    public function getPluginInfo()
    {
        return array(
            "name" => "Automatic Product Processor",
            "author" => "Javier",
            "version" => "0.0.1",
        );
    }

    protected function enqueueReindex($nprods) {
        //Magmi_ReindexingPlugin();
        $config=new Magmi_PluginConfig('Magmi_ReindexingPlugin', $this->getProfile());
        $config->load();
        $params = $config->getConfig();
        $idxlstr = $params["REINDEX:indexes"];
        $idxlist = explode(",", $idxlstr);
        if(count($idxlist)==0)
        {
            $this->log("No indexes selected , skipping reindexing...", "warning");
            return true;
        }
        DJJob::enqueue(new ReindexJob($this->project, $idxlist, $this->magdir, $nprods), $this->project);
    }
    protected function enqueueProductSave() {
        $tmpFile = $this->magdir . '/hermes/upload/save.' . microtime(true) . '.txt';
        file_put_contents($tmpFile, implode(',', Importer::getProductSku()));
        chmod($tmpFile, 0777);
        DJJob::enqueue(new SaveProductJob($this->project, $tmpFile, $this->magdir), $this->project);
    }
    protected function chooseBest($stats) {
        $num_products = count(Importer::getProductSku());
        if($num_products * $stats['product_save'] > $stats['reindex']) {
            $this->enqueueReindex($num_products);
        } else {
            $this->enqueueProductSave();
        }
    }

    public function afterImport()
    {
        $this->log("Automatic Product Processor","info");

        $stats = DJJob::getStats($this->project);

        if($stats && $stats['product_save'] && $stats['reindex']) {
            $this->chooseBest($stats);
        } else if($stats && $stats['product_save']) {
            $this->enqueueReindex(count(Importer::getProductSku()));
        } else {
            $this->enqueueProductSave();
        }
        return true;
    }
}
