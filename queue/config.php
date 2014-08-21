<?php
require_once dirname(__FILE__) . "/init.php";

class ReindexJob {
    public function __construct($name, $indexes, $pathMagento, $nprods) {
        $this->name = $name;
        $this->indexes = $indexes;
        $this->pathMagento = $pathMagento;
        $this->nprods = $nprods;
    }
    public function perform($job_id) {
        $tstart=microtime(true);

        $status = HermesHelper::getCurrentStatus($this->pathMagento);
        echo "Reindexing {$this->name}!\n";
        $total = sizeOf($this->indexes);
        $cl = 'php ' . $this->pathMagento . '/shell/indexer.php';



        $i = 0;
        while($idx = array_pop($this->indexes)) {
            echo "Reindexing $idx....\n";
            $out = shell_exec($cl . " --reindex $idx");
            DJJob::updateProgress($job_id, $this, (++$i / $total * 100));
            echo $out;
        }

        $tend=microtime(true);
        $time_taken = round($tend-$tstart, 2);
        echo "done in " . $time_taken . " secs\n";
        DJJob::updateStats($this->name, 'reindex', $time_taken);
        $job_data = array($this->nprods, $time_taken, get_class($this));
        HermesHelper::saveImportData($this->name, $status, $job_data);
    }
}

class SaveProductJob {
    public function __construct($name, $pathSkus, $pathMagento) {
        $this->name = $name;
        //Path of the file that contains all the products skus
        $this->pathSkus = $pathSkus;
        $this->pathMagento = $pathMagento;
    }

    public function perform($job_id) {
        printf('[%s] Calling exec productsave with parameters: %s, %s, %s, %s' . "\n", date('c'), $this->name, $this->pathSkus, $this->pathMagento, $job_id);
        $save = exec("php productsave.php " . $this->name . ' ' . $this->pathSkus . ' ' . $this->pathMagento . ' ' . $job_id, $results);
        echo implode("\n", $results) . "\n";
    }
}

