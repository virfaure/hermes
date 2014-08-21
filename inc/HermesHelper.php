<?php
class HermesHelper {
    const PROJECT_DATA = 'select (select count(*) from catalog_product_entity) products,
        (select count(*) from catalog_category_entity) categories,
        (select count(*) from core_url_rewrite) rewrite,
        (select count(*) from eav_entity_attribute) attributes,
        (select count(*) from core_website) websites,
        (select count(*) from catalogsearch_fulltext) search';
    const LOG_HEADER = 'project,products,categories,rewrite,attributes,websites,search,load1,load5,load15,worktime,num_products,time,action';
    const LOG_FILE = '/../statistics.csv';

    const HERMES_FOLDER = '/hermes/';
    /**
     * Default path of magento db config file
     */
    const CONFIG_FILE = '/app/etc/local.xml';

    /**
     * Default path of Simple-ConfigurableProducts module
     */
    const SC_MODULE_FILE = '/app/etc/modules/OrganicInternet_SimpleConfigurableProducts.xml';

    /**
     * Default path of AccessControl module
     */
    const AC_MODULE_FILE = '/app/etc/modules/ZetaPrints_AccessControl.xml';
    
    /**
     * Default path of Atioc Stock module
     */
    const AITOCSTOCK_MODULE_FILE = '/app/etc/modules/Aitoc_Aitquantitymanager.xml';
    
    /**
     * getEnvironment
     *
     * @static
     * @access public
     * @return string
     */
    public static function getEnvironment() {
        $env = 'DEV';
        $hostname = gethostname();
        if (in_array($hostname, array('etailers1', 'etailers2', 'etailers3', 'etailers-www01', 'etailers-www02', 'etailers-www03'))) {
            $env = 'PROD';
        } elseif (in_array($hostname, array('OsShop-pre', 'etailers-pre', 'pre-etailers'))) {
            $env = 'PRE';
        }
        return $env;
    }

    /**
     * getMagentoIsActive
     * Return wether the given module is active or not
     *
     * @param mixed $module
     * @access private
     * @return bool
     */
    public static function getMagentoIsActive($module) {
        $active = false;
        $file = (self::getMagentoRootPath() ?: '') . $module;

        if(file_exists($file)) {
            $conf_file = simplexml_load_file($file);
            $module_name = basename($module, '.xml');
            $active = ((string) $conf_file->modules->$module_name->active === 'true');
            unset($conf_file);
        }
        return $active;
    }

    /**
     * getMagentoRootPath
     * Try to retrieve the absolute path of magento root directory
     *
     * @static
     * @access public
     * @return void
     */
    public static function getMagentoRootPath() {
        $path = false;

        //Try to get the path from from the symlink installation
        $script = explode(self::HERMES_FOLDER, $_SERVER['SCRIPT_FILENAME']);
        if(file_exists($script[0] . self::CONFIG_FILE)) {
            $path = $script[0];
        }

        //Accesing from phpcli
        if(!$path) {
            //Running script from root directory
            if(file_exists($_SERVER['PWD'] . self::CONFIG_FILE)) {
                $path = $_SERVER['PWD'];
            }
            //Running from shell directory
            if(file_exists(dirname($_SERVER['PWD']) . self::CONFIG_FILE)) {
                $path = dirname($_SERVER['PWD']);
            }
        }

        //Try to load the file from the parent directories (old installations)
        //Keept for retrocompatibiliy
        if(!$path && file_exists(dirname(__FILE__) . '/../..' . self::CONFIG_FILE)) {
            $path = dirname(__FILE__) . '/../../';
        }
        return $path;
    }

    /**
     * getMagentoDBConfig Load db parameters from magento xml.
     *
     * @param mixed $file
     * @static
     * @access public
     * @return mixed simplexml_file
     */
    public static function getMagentoDBConfig($file = false) {
        $found = false;

        //If $file was specified, try to load it
        if(!$file) {
            $magento_path = self::getMagentoRootPath();
            if(!$magento_path) {
                die('Unable to load magento db config file.');
            } else {
                $file = $magento_path . self::CONFIG_FILE;
            }
        }
        if(file_exists($file)) {
            $dbconfig = simplexml_load_file($file);
            $found = $dbconfig->global->resources->default_setup->connection;
        }
        return $found;
    }

    public static function getProject() {

        //Accessing from web
        if(isset($_SERVER['SERVER_NAME'])) {
            $hostname = $_SERVER['SERVER_NAME'];
        } else {
            //Accesing from phpcli, try to retrieve the site url
            $host = explode('/', $_SERVER['PWD']);
            $hostname = array_filter($host, function($url) {
                return preg_match('/^(.*)\.(net|com|es|pt|it)$/', $url);
            });
            if(empty($hostname)) {
                $hostname = $host[count($host) -2];
            } else {
                $hostname = reset($hostname);
            }
        }
        return $hostname;
    }
    public static function encodeUTF8($filename) {
        //Check File Encoding => Must Be in UTF-8
        $ret = false;
        $return = exec("file -bi '{$filename}'");

        if(preg_match('/charset=([a-z0-9-_]+)/i', $return, $charset)) {
            if($charset[1] != "utf-8") {
                //Conversion
                exec("uconv -f {$charset[1]} -t utf-8 '{$filename}' > '{$filename}'.utf8 2>&1", $out, $err);

                if($err) {
                    exec("rm -f '{$filename}'.utf8");
                    exit("No se puede convertir el archivo a UTF-8. Por favor, revise su codificación y intente subirlo otra vez.");
                }else{
                    $ret = true;
                    exec("mv -f '{$filename}.utf8' '{$filename}'");
                }
            }
        }
        return $ret;
    }

    public static function getCurrentStatus($magento_path) {
        $dbConfig = self::getMagentoDBConfig($magento_path . self::CONFIG_FILE);
        $mysqli = new mysqli($dbConfig->host, $dbConfig->username, $dbConfig->password, $dbConfig->dbname);
        $mysqli->set_charset("utf8");
        $data = $mysqli->query(self::PROJECT_DATA);
        $project_data = $data->fetch_row();

        return array_merge(
            $project_data,
            sys_getloadavg(),
            array(var_export(date('H') < 17 && date('H') > 9, true))
        );
    }
    public static function saveImportData($project, $status, $job_data) {
        if(!file_exists(dirname(__FILE__) . self::LOG_FILE)) {
            file_put_contents(dirname(__FILE__) . self::LOG_FILE, self::LOG_HEADER . "\n");
        }

        file_put_contents(
            dirname(__FILE__) . self::LOG_FILE,
            implode(',',
            array_merge(
                array($project),
                $status,
                $job_data
            )) . "\n"
            ,
            FILE_APPEND
        );

    }
}
