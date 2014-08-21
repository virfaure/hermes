<?php
/**
 * Etailers_Store_Test
 *
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Etailers_Store_Test extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->validator = new Lexical_Store();
    }

    protected static $inputs = array(
        array('1,2.45,3.234,4,5', false),
        array('1, asdf, 435', false),
        array('asdf', true),
        array('es, en, eng', true),
        array('', true) //Required field
    );

    public static function providerFormat()
    {
        return self::$inputs;
    }

    /**
     * testStoreFormat
     * Ensure that the format of the Store is being properly validated.
     * Only comma-separated integers should be allowed
     *
     * @dataProvider providerFormat
     * @param mixed $input
     * @param mixed $result
     * @access public
     * @return void
     */
    public function testStoreFormat($input, $result) {
        $this->assertEquals($result, $this->validator->validate($input));
    }

    /**
     * testStoreError Proper error reporting
     *
     * @access public
     * @return void
     */
    public function testStoreError() {
        $this->assertFalse($this->validator->validate($input= '1, asdf'));
        $this->assertContains($input, $this->validator->getErrorMsg($input));

    }
}
