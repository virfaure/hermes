<?php
/**
 * Etailers_Talla_Test
 *
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Etailers_Talla_Test extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->validator = new Lexical_Talla();
    }

    protected static $inputs = array(
        array('XL', true),
        array('', true)    //Optional field
    );

    public static function providerFormat()
    {
        return self::$inputs;
    }

    /**
     * testTallaFormat
     * Ensure that the talla attribute is being properly validated.
     *
     * @dataProvider providerFormat
     * @param mixed $input
     * @param mixed $result
     * @access public
     * @return void
     */
    public function testTallaFormat($input, $result) {
        $this->assertEquals($result, $this->validator->validate($input));
    }

    /**
     * testMsgError
     * Ensure that the proper error is being shown.
     *
     * @access public
     * @return void
     */
    public function testMsgError() {
        $input = 'invalid talla';
        //There must be an error regarding the given input
        $this->assertContains($input, $this->validator->getErrorMsg($input));
    }
}
