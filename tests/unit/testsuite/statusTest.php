<?php
/**
 * Etailers_Status_Test 
 * 
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net> 
 */
class Etailers_Status_Test extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->validator = new Lexical_Status();
    }

    protected static $inputs = array(
        array('1', true),
        array('2', true),
        array('Habilitado', true),
        array('Deshabilitado', true),
        array('Dessdfhado', false),
        array('', false)    //Mandatory field
    );

    public static function providerFormat()
    {
        return self::$inputs;
    }

    /**
     * testStatusFormat 
     * Ensure that the format of the Status is being properly validated.
     *
     * @dataProvider providerFormat
     * @param mixed $input 
     * @param mixed $result 
     * @access public
     * @return void
     */
    public function testStatusFormat($input, $result) {
        $this->assertEquals($result, $this->validator->validate($input));
    }

    /**
     * testSuperEmpty 
     * Ensure that super profile can leave the field empty
     * 
     * @access public
     * @return void
     */
    public function testSuperEmpty() {
        $validator = new Lexical_StatusSuper();
        $this->assertTrue($validator->validate($input = ''));
    }

    /**
     * testStatusError 
     * Ensure that the proper error is being shown.
     * 
     * @access public
     * @return void
     */
    public function testStatusError() {
        $this->assertFalse($this->validator->validate($input = '234'));
        //There must be an error regarding the given input
        $this->assertGreaterThanOrEqual(1, strpos($this->validator->getErrorMsg($input), $input));
    }

}
