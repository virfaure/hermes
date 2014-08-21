<?php
/**
 * Etailers_Color_Test
 *
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Etailers_Color_Test extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->validator = new Lexical_Color();
    }

    protected static $inputs = array(
        array('RED', 'Red', true),
        array('blue', 'Blue', true),
        array('gOld', 'Gold', true),
        array('34567890123456789123456789012345678901234567890123456789123456789012345678912345678901234567891234567890123456789123456789012345678912345678901234567891234567890123456789123456789012345678912345678901234567890', '34567890123456789123456789012345678901234567890123456789123456789012345678912345678901234567891234567890123456789123456789012345678912345678901234567891234567890123456789123456789012345678912345678901234567890', false),
        array('', '', true)    //Optional field
    );

    public static function providerFormat()
    {
        return self::$inputs;
    }

    /**
     * testColorFormat
     * Ensure that the color is being properly validated and formatted.
     *
     * @dataProvider providerFormat
     * @param mixed $input
     * @param mixed $output
     * @param mixed $result
     * @access public
     * @return void
     */
    public function testColorFormat($input, $output, $result) {
        $this->assertEquals($result, $this->validator->validate($input));
        $this->assertEquals($output, $input);
    }

    /**
     * testMsgError
     * Ensure that the proper error is being shown.
     *
     * @access public
     * @return void
     */
    public function testMsgError() {
        $this->assertFalse($this->validator->validate($input = '1234567890123456789123456789012345678912345678901234567891234567890123456789123456789012345678912345678901234567891234567890123456789123456789012345678912345678901234567890234234234234'));
        //There must be an error regarding the given input
        $this->assertContains($input, $this->validator->getErrorMsg($input));
    }
}
