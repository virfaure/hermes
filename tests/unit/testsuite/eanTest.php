<?php
/**
 * Etailers_EAN_Test
 *
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Etailers_EAN_Test extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->validator = new Lexical_Ean();
    }

    protected static $inputs = array(
        array('1234567890418', true),
        array('1234567890411', false),
        array('12323', false),
        array('234223', false)
    );

    public static function providerFormat()
    {
        return self::$inputs;
    }

    /**
     * testEANFormat
     * Ensure that the format of the EAN is being properly validated.
     * The provided code must be 13 characters long, and the last digit (control digit) must match the EAN computation.
     *
     * @dataProvider providerFormat
     * @param mixed $input
     * @param mixed $result
     * @access public
     * @return void
     */
    public function testEANFormat($input, $result) {
        $this->assertEquals($result, Lexical_Ean::checkEAN($input));
    }

    /**
     * testEANValidator
     * Ensure the field is optional and the input must be a number
     *
     * @access public
     * @return void
     */
    public function testEANValidator() {
        //String not allowed
        $this->assertFalse($this->validator->validate($input = 'invalid'));
        //Optional field
        $this->assertTrue($this->validator->validate($input = ''));

    }

    /**
     * testEANError
     * Ensure that the proper error is being shown.
     *
     * @access public
     * @return void
     */
    public function testEANError() {
        $this->assertFalse($this->validator->validate($input = 'asdf'));
        $this->assertContains($input, $this->validator->getErrorMsg($input));
    }

}
