<?php
/**
 * Etailers_SpecialPrice_Test
 *
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Etailers_SpecialPrice_Test extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->validator = new Lexical_SpecialPrice();
    }

    protected static $inputs = array(
        array('12.38', true),
        array('1,23', true), //Commas should be replaced by . automatically
        array('0.0123', true),
        array('-4', false),
        array('34.,23', false),
        array('asdf', false),
        array('', true),
        array('0', false)
    );

    public static function providerFormat()
    {
        return self::$inputs;
    }

    /**
     * testPriceFormat
     * Ensure that the format of the Price is being properly validated.
     * The provided value must be a positive decimal number (commas and . are allowed).
     *
     * @dataProvider providerFormat
     * @param mixed $input
     * @param mixed $result
     * @access public
     * @return void
     */
    public function testPriceFormat($input, $result) {
        $this->assertEquals($result, $this->validator->validate($input));
    }

    /**
     * testPriceBoundaries
     * The provided value must be between the specified boundaries.
     *
     * @access public
     * @return void
     */
    public function testPriceBoundaries() {
        $this->assertfalse($this->validator->validate($input = Lexical_SpecialPrice::$MIN_N - 0.01));
        $this->assertfalse($this->validator->validate($input = Lexical_SpecialPrice::$MAX_N + 0.01));
    }

    /**
     * testPriceComma
     * Check that if the provided value contains a comma, it's automatically replaced by a . (dot)
     *
     * @access public
     * @return void
     */
    public function testPriceComma() {
        $input = '1,23';
        $expected = array('1.23');
        $this->validator->validate($input);
        $this->assertEquals($this->validator->getTokens(), $expected);
    }

    /**
     * testMsgError
     * Ensure that the proper error is being shown.
     *
     * @access public
     * @return void
     */
    public function testMsgError() {
        $this->assertFalse($this->validator->validate($input = '0'));
        //There must be an error regarding the given input
        $this->assertContains($input, $this->validator->getErrorMsg($input));
    }
}
