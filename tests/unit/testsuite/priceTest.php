<?php
/**
 * Etailers_Price_Test
 *
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Etailers_Price_Test extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->validator = new Lexical_Price();
    }

    protected static $inputs = array(
        array('12.38', true),
        array('1,23', true), //Commas should be replaced by . automatically
        array('0.0123', true),
        array('-4', false),
        array('34.,23', false),
        array('asdf', false),
        array('', false),
        array('0', false),
        array('0.0', false),
        array('0,0', false)
    );

    protected static $semantic_inputs = array(
        //Configurable products price cannot be empty (unless the flag Simples-Configurables is enabled)
        array(
            array(
                'price' => array(''), 'type' => array('configurable'), 'sku' => array('mysku')
            )
            , false),
        array(
            array(
                'price' => array('123'), 'type' => array('configurable'), 'sku' => array('mysku')
            )
            , true),
        //Simple products price cannot be empty by default
        array(
            array(
                'price' => array(''), 'type' => array('simple'), 'sku' => array('mysku')
            )
            , false),
        array(
            array(
                'price' => array('43'), 'type' => array('simple'), 'sku' => array('mysku')
            )
            , true),
        //If store is !== admin, price can be empty, only for update profile, so this shouldn't pass validation
        array(
            array(
                'price' => array('', ''), 'type' => array('simple', 'configurable'), 'store' => array(array('es', 'en'), array('es', 'ca')), 'sku' => array('mysku', 'mysku2')
            )
            , false),
        //If store is admin price can't be empty by default
        array(
            array(
                'price' => array('', ''), 'type' => array('simple', 'simple'), 'store' => array(array('admin', 'en'), array('admin', 'ca')), 'sku' => array('mysku', 'mysku2')
            )
            , false),
        array(
            array(
                'price' => array('', ''), 'type' => array('configurable', 'configurable'), 'store' => array(array('admin', 'en'), array('admin', 'ca')), 'sku' => array('mysku', 'mysku2')
            )
            , false)
    );

    public static function providerFormat() {
        return self::$inputs;
    }

    public static function providerSemantic() {
        return self::$semantic_inputs;
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
        $this->assertfalse($this->validator->validate($input = Lexical_Price::$MIN_N - 0.01));
        $this->assertfalse($this->validator->validate($input = Lexical_Price::$MAX_N + 0.01));
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
     * testImportEmpty
     * Ensure that Import profile can leave the field empty
     *
     * @access public
     * @return void
     */
    public function testImportEmpty() {
        $validator = new Lexical_PriceImport();
        $this->assertTrue($validator->validate($input = ''));
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

    /**
     * testSemantic
     * Ensure the validation of the empty values works as expected.
     *
     * @dataProvider providerSemantic
     * @param mixed $input
     * @param mixed $result
     * @access public
     * @return void
     */
    public function testSemantic($input, $result) {
        $validator = new Semantic_Price();
        $this->assertEquals($result, (bool) $validator->validate($input));

    }

    public function testUpdateEmpty() {
        $validator = new Semantic_PriceUpdate();
        $input = array(
            'price' => array('', ''), 'type' => array('simple', 'configurable'), 'store' => array(array('es', 'en'), array('es', 'ca')), 'sku' => array('mysku', 'mysku2')
        );
        $this->assertTrue((bool) $validator->validate($input));
    }

    /**
     * testSemanticSCFlag
     * Ensure that the validation works fine if the simple-configurables flag is set.
     *
     * @access public
     * @return void
     */
    public function testSemanticSCFlag() {
        $validator = new Semantic_Price(true);
        //Configurable products price can be empty
        $input = array(
            'price' => array(''), 'type' => array('configurable'), 'sku' => array('mysku')
        );
        $this->assertTrue((bool) $validator->validate($input));

        $inputvalid = array(
            'price' => array('123'), 'type' => array('configurable'), 'sku' => array('mysku')
        );
        $this->assertTrue((bool) $validator->validate($inputvalid));

        //Simple products price cannot be empty by default
        $inputInvalid = array(
            'price' => array(''), 'type' => array('simple'), 'sku' => array('myskuinv')
        );
        $this->assertFalse((bool) $validator->validate($inputInvalid));
        $this->assertContains('myskuinv', $validator->getErrorMsg($inputInvalid));

        $inputvalid = array(
            'price' => array('43'), 'type' => array('simple'), 'sku' => array('mysku')
        );
        $this->assertTrue((bool) $validator->validate($inputvalid));


    }
}
