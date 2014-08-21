<?php
/**
 * Etailers_Analyzer_Test
 *
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Etailers_Analyzer_Test extends PHPUnit_Framework_TestCase {

    private static $mandatory = array('sku', 'qty', 'type');
    private $dbFile;
    private $method;
    private $analyzer;

    const ACTION = 'import';

    public function setUp() {
        $this->dbFile = dirname(__FILE__) . '/../local.xml';

        $reflection_class = new ReflectionClass("Analyzer");
        $this->method = $reflection_class->getMethod("array_merge_multi");
        $this->method->setAccessible(true);
        $this->analyzer = new Analyzer(self::$mandatory, self::ACTION, false, $this->dbFile);
    }

    protected static $inputs = array(
        //Should merge two dimensional arrays into a sigle one
        array(
            array(
                array('1', '2'),
                array('3', '4')
            ),
            array('1', '2', '3', '4')
        ),
        //Repeated elements should be kept
        array(
            array(
                array('1', '2'),
                array('2', '3')
            ),
            array('1', '2', '2', '3')
        ),
        //Do not merge recursively
        array(
            array(
                array('1', array('2')),
                array('2', '3')
            ),
            array('1', array('2'), '2', '3')
        ),
        //Single array shouldn't be changed
        array(
            array('2', '3'),
            array('2', '3')
        ),
    );


    public static function providerFormat()
    {
        return self::$inputs;
    }

    /**
     * testArrayMergeMulti
     *
     * @dataProvider providerFormat
     * @access public
     * @return void
     */
    public function testArrayMergeMulti($input, $merged) {

        $ret = $this->method->invoke($this->analyzer, $input);
        $this->assertEquals($merged, $ret);
    }

}
