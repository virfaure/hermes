<?php
/**
 * Etailers_SKU_Test
 *
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Etailers_SKU_Test extends PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->validator = new Lexical_Sku();
    }

    protected static $inputs = array(
        array('ASD123', true),
        array('01ASDF', true),
        array('ASD-XL', true),
        array('34.10',true),
        array('BRAND_3410',true),
        array('EVIL ALLOWED SKU', true),
        array('Un4llow3d% $KU $pecí&l %ars', false),
        array('', false),
        array('TOO LONG SKU 0123456789012345678901234567890123456789012345678901234567890', false)
    );

    public static function providerFormat()
    {
        return self::$inputs;
    }

    /**
     * testSkuFormat
     * Ensure that the format of the SKU's is being properly validated.
     * Only the specified special characters are allowed, and the lenght must be between 1 and 64 characters.
     *
     * @dataProvider providerFormat
     * @param mixed $input
     * @param mixed $result
     * @access public
     * @return void
     */
    public function testSkuFormat($input, $result) {
        $this->assertEquals($result, $this->validator->validate($input));
    }

    public function testTokensStored() {
        $input1 = 'MySku-1';
        $input2 = 'MySku-2';

        $this->validator->validate($input1);
        $this->validator->validate($input2);
        $this->assertTrue(count($this->validator->getTokens()) == 2);
        $this->assertTrue(in_array($input1, $this->validator->getTokens()));
    }

    /**
     * testSKUError
     * Ensure that the proper error is being shown.
     *
     * @access public
     * @return void
     */
    public function testSKUError() {
        //First time is valid SKU
        $this->assertTrue($this->validator->validate($input = '1234543534'));
        //Second time is invalid (special characters)
        $this->assertFalse($this->validator->validate($input= '"·$$%%/(&divide;T'));
        //There must be an error regarding the given input
        $this->assertGreaterThanOrEqual(1, strpos($this->validator->getErrorMsg($input), 'special characters'));
    }
}
