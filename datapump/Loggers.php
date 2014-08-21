<?php
/**
 * Logger
 *
 * @abstract
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
abstract class Logger {

    protected $fh = false;

    function __construct($debug = false, $fname = false) {
        $this->debug = $debug;
        if($fname) {
            if(file_exists($fname)) {
                unlink($fname);
            }
            $this->fh = fopen($fname,"w");
            if($this->fh == false)
            {
                throw new Exception("CANNOT WRITE PROGRESS FILE ");
            }
        }
    }
    function __destruct() {
        if($this->fh) fclose($this->fh);
    }

    public function log($data, $type = 'DEBUG')
    {
        if($this->fh)
        {
        $data=preg_replace ("/(\r|\n|\r\n)/", "<br>", $data);
        fwrite($this->fh,"$type:$data\n");
        }
    }
}
/**
 * ConsoleLogger
 * Output the data in a console-friendly format
 *
 * @uses Logger
 * @copyright 2012 The Etailers S.L.
 */
class ConsoleLogger extends Logger {
    public function log($data, $type = 'DEBUG') {
        if ($type == 'ERROR' || $this->debug) {
            echo "[$type] $data\n";
        }
        parent::log($data, $type);
    }
}

/**
 * HTMLLogger
 * Output the data in a HTML format
 *
 * @uses Logger
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class HTMLLogger extends Logger
{
    const SUCCESS = '<div class="success"><pre>%s</div>';
    const WARNING = '<div class="warning"><pre>%s</div>';
    const DEBUG = '<div class="warning"><pre>%s</div>';
    const ERROR = '<div class="error"><pre>%s</div>';

    function __construct($debug = false, $fp) {

        $this->output = array('ERROR' => '', 'SUCCESS' => '', 'WARNING' => '', 'DEBUG' => '');
        parent::__construct($debug, $fp);
    }
    function __destruct() {
        foreach ($this->output as $type => $data) {
            if($data) {
                printf(constant('self::' . $type), $data);
            }
            unset($data);
        }
    }
    public function log($data, $type = 'DEBUG') {

        $type = strtoupper($type);

        switch ($type) {
        case 'ERROR':
        case 'WARNING':
        case 'SUCCESS':
            $this->output[$type] .= $data . "\n";
            break;
        default:
            if($this->debug) {
                $this->output['DEBUG'] .= $data . "\n";
            }
        }
        parent::log($data, $type);
    }
}
