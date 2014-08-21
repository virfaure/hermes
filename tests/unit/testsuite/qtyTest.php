<?php
/**
 * Etailers_Qty_Test
 *
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Etailers_Qty_Test extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->validator = new Lexical_Qty();
    }

    protected static $inputs = array(
        array('12.38', false), //shouldn't be allowed non-integer values
        array('1,23', false), //Commas should be replaced by . automatically
        array('0.0123', false),
        array('45', true),
        array('0', true),
        array('+45', true), //delta values are allowed always if possitive (no effect if product doesn't exist)
        array('-4', false), //delta values can't be negative unless the product already exists (see testQtyNegative)
        array('34.,23', false),
        array('asdf', false),
        array('', true), //Empty values are "accepted" until semantic phase
    );

    protected static $semantic_inputs = array(
        //Configurable products qty can be empty
        array(
            array(
                'qty' => array(''), 'type' => array('configurable'), 'sku' => array('mysku')
            )
            , true),
        //Simple products qty cannot by default
        array(
            array(
                'qty' => array(''), 'type' => array('simple'), 'sku' => array('mysku')
            )
            , false),
        array(
            array(
                'qty' => array('43'), 'type' => array('simple'), 'sku' => array('mysku')
            )
            , true),
        //If store is !== admin, qty can be empty
        array(
            array(
                'qty' => array('', ''), 'type' => array('simple', 'simple'), 'store' => array(array('es', 'en'), array('es', 'ca')), 'sku' => array('mysku', 'mysku2')
            )
            , true),
        //If store is admin simple can't be empty by default
        array(
            array(
                'qty' => array('', ''), 'type' => array('simple', 'simple'), 'store' => array(array('admin', 'en'), array('es', 'ca')), 'sku' => array('mysku', 'mysku2')
            )
            , false),
        //If manage_stock is set to 0, simple qty can be empty
        array(
            array(
                'qty' => array('', ''), 'type' => array('simple', 'simple'), 'store' => array(array('admin', 'en'), array('es', 'ca')), 'manage_stock' => array('0', '0'), 'sku' => array('mysku', 'mysku2')
            )
            , true),
        //If manage_stock equals 1, simple qty must be filled (unless the store is not admin)
        array(
            array(
                'qty' => array('', ''), 'type' => array('simple', 'simple'), 'store' => array(array('admin', 'en'), array('es', 'ca')), 'manage_stock' => array('1', '1'), 'sku' => array('mysku', 'mysku2')
            )
            , false),
        array(
            array(
                'qty' => array('', ''), 'type' => array('simple', 'simple'), 'store' => array(array('en'), array('ca')), 'manage_stock' => array('1', '1'), 'sku' => array('mysku', 'mysku2')
            )
            , true),
    );

    public static function providerFormat() {
        return self::$inputs;
    }

    public static function providerSemantic() {
        return self::$semantic_inputs;
    }

    /**
     * testQtyFormat
     * Ensure that the format of the Qty is being properly validated.
     * The provided value must be a positive integer number (commas and . are allowed).
     *
     * @dataProvider providerFormat
     * @param mixed $input
     * @param mixed $result
     * @access public
     * @return void
     */
    public function testQtyFormat($input, $result) {
        $this->assertEquals($result, $this->validator->validate($input));
    }

    /**
     * testQtyBoundaries
     * The provided value must be between the specified boundaries.
     *
     * @access public
     * @return void
     */
    public function testQtyBoundaries() {
        $this->assertFalse($this->validator->validate($input = Lexical_Qty::$MIN_N - 0.01));
        $this->assertFalse($this->validator->validate($input = Lexical_Qty::$MAX_N + 0.01));
    }

    /**
     * testQtyNegative
     * The provided value can be Negative only for the update (or super) profile.
     *
     * @access public
     * @return void
     */
    public function testQtyNegative() {
        $validator = new Lexical_QtyUpdate();
        $this->assertTrue($validator->validate($input = -45));
        $this->assertFalse($this->validator->validate($input = -45));
    }
    /**
     * testQtyComma
     * Check that if the provided value contains a comma, it's automatically replaced by a . (dot)
     *
     * @access public
     * @return void
     */
    public function testQtyComma() {
        $input = '1,23';
        $expected = array('1.23');
        $this->validator->validate($input);
        $this->assertEquals($this->validator->getTokens(), $expected);
    }

    /**
     * testEmptyQtyValues
     * Ensure the validation of the empty values works as expected.
     *
     * @dataProvider providerSemantic
     * @param mixed $input
     * @param mixed $result
     * @access public
     * @return void
     */
    public function testEmptyQtyValues($input, $result) {
        $validator = new Semantic_Qty();
        $this->assertEquals($result, $validator->validate($input));
    }

    /**
     * testEmptyQtyValuesError
     * Ensure the errors are being properly displayed.
     *
     * @access public
     * @return void
     */
    public function testEmptyQtyValuesError() {
            $validator= new Semantic_Qty();
            $invalidInput = array(
                'qty' => array(''), 'type' => array('simple'), 'sku' => array('mysku')
            );
            $this->assertFalse((bool) $validator->validate($invalidInput));
            $this->assertContains('Qty be empty only if product type = "configurable"', $validator->getErrorMsg($invalidInput));
            $this->assertContains('mysku', $validator->getErrorMsg($invalidInput));
    }
}
