<?php
class Etailers_HermesHelper_Test extends PHPUnit_Framework_TestCase {

    private $_dbFile;
    private static $message;
    const MODULE_CONFIG_FILE ='<?xml version="1.0"?>
        <config>
        <modules>
        <Fooman_Common>
        <active>%s</active>
        <codePool>community</codePool>
        </Fooman_Common>
        </modules>
        </config>';
    const ENCODING_CONTENT = 'á55úiíúúéásaÄ+A+E+eçèÊèêáóí';

    public function setUp()
    {
        $this->_dbFile = dirname(__FILE__) . '/../local.xml';
        $this->destination_module_file = '/tmp/Fooman_Common.xml';
        $this->destination_encoding_file = '/tmp/unencoded_file.txt';
    }

    public function tearDown() {
        @unlink($this->destination_module_file);
        @unlink($this->destination_encoding_file);
    }
    /**
     * setMessage
     * Helper method to capture exit/die messages
     *
     * @param mixed $message
     * @access public
     * @return void
     */
    public function setMessage($message) {
        self::$message = $message;
    }

    /**
     * testGetEnvironment
     *
     * @access public
     * @return void
     */
    public function testGetEnvironment()
    {
        $this->assertEquals('DEV', HermesHelper::getEnvironment());
    }

    public function testGetProject()
    {
        $_SERVER['PWD'] = '/var/www/aita.etailers.uk/web';
        $this->assertEquals('aita.etailers.uk', HermesHelper::getProject());
        $_SERVER['PWD'] = '/var/www/dev.aita.com/web';
        $this->assertEquals('dev.aita.com', HermesHelper::getProject());
        $_SERVER['SERVER_NAME'] = 'dev.aita.com';
        $this->assertEquals('dev.aita.com', HermesHelper::getProject());
    }

    /**
     * testCustomDBPath
     * Ensure that the db config is properly loaded
     *
     * @access public
     * @return void
     */
    public function testCustomDBPath() {
        $dbdata = HermesHelper::getMagentoDBConfig($this->_dbFile);
        $this->assertNotNull((string)$dbdata->host);
        $this->assertNotNull((string)$dbdata->username);
        $this->assertNotNull((string)$dbdata->password);
        $this->assertNotNull((string)$dbdata->dbname);

        //From phpunit path, the config file shouldn't be found.
        set_exit_overload(function($message) { Etailers_HermesHelper_Test::setMessage($message); return FALSE; });
        $dbdata = HermesHelper::getMagentoDBConfig();
        unset_exit_overload();
        $this->assertEquals('Unable to load magento db config file.', self::$message);
    }

    public function testEncoding() {
        //Convert an un-encoded file
        $content = utf8_decode(self::ENCODING_CONTENT);
        file_put_contents($this->destination_encoding_file, $content);
        //The first time, the file should be converted, as its content is not utf8 encoded.
        $this->assertTrue(HermesHelper::encodeUTF8($this->destination_encoding_file));
        //The next time, the file should be already converted, and nothing should be done.
        $this->assertFalse(HermesHelper::encodeUTF8($this->destination_encoding_file));
    }

    public function testMagentoIsActive() {
        file_put_contents($this->destination_module_file, sprintf(self::MODULE_CONFIG_FILE, 'true'));
        $this->assertTrue(HermesHelper::getMagentoIsActive($this->destination_module_file));
        file_put_contents($this->destination_module_file, sprintf(self::MODULE_CONFIG_FILE, 'false'));
        $this->assertFalse(HermesHelper::getMagentoIsActive($this->destination_module_file));
    }

}
