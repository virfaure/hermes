<?php
/**
 * Etailers_Weight_Test 
 * 
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net> 
 */
class Etailers_Weight_Test extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->validator = new Lexical_Weight();
    }

    protected static $inputs = array(
        array('12.38', true),
        array('1,23', true), //Commas should be replaced by . automatically
        array('0.0123', true),
        array('-4', false),
        array('34.,23', false),
        array('asdf', false)
    );

    protected static $semantic_inputs = array(
        //Configurable products weight can be empty
        array(
            array(
                'weight' => array(''), 'type' => array('configurable'), 'sku' => array('mysku')
            )
            , true),
        array(
            array(
                'weight' => array('123'), 'type' => array('configurable'), 'sku' => array('mysku')
            )
            , true),
        //Simple products weight cannot be empty by default
        array(
            array(
                'weight' => array(''), 'type' => array('simple'), 'sku' => array('mysku')
            )
            , false),
        array(
            array(
                'weight' => array('43'), 'type' => array('simple'), 'sku' => array('mysku')
            )
            , true),
        //If store is !== admin, weight can be empty, but only for update profile, so this shouldn't pass validation
        array(
            array(
                'weight' => array('', ''), 'type' => array('simple', 'simple'), 'store' => array(array('es', 'en'), array('es', 'ca')), 'sku' => array('mysku', 'mysku2')
            )
            , false),
        //If store is admin weight can't be empty by default
        array(
            array(
                'weight' => array('', ''), 'type' => array('simple', 'simple'), 'store' => array(array('admin', 'en'), array('admin', 'ca')), 'sku' => array('mysku', 'mysku2')
            )
            , false),
        //Except for configurables
        array(
            array(
                'weight' => array('', ''), 'type' => array('configurable', 'configurable'), 'store' => array(array('admin', 'en'), array('admin', 'ca')), 'sku' => array('mysku', 'mysku2')
            )
            , true)
        );

    public static function providerFormat()
    {
        return self::$inputs;
    }

    public static function providerSemantic() {
        return self::$semantic_inputs;
    }

    /**
     * testWeightFormat 
     * Ensure that the format of the Weight is being properly validated.
     * The provided value must be a positive decimal number (commas and . are allowed).
     *
     * @dataProvider providerFormat
     * @param mixed $input 
     * @param mixed $result 
     * @access public
     * @return void
     */
    public function testWeightFormat($input, $result) {
        $this->assertEquals($result, $this->validator->validate($input));
    }

    /**
     * testWeightBoundaries 
     * The provided value must be between the specified boundaries.
     *
     * @access public
     * @return void
     */
    public function testWeightBoundaries() {
        $this->assertfalse($this->validator->validate($input = lexical_weight::$MIN_N - 0.01));
        $this->assertfalse($this->validator->validate($input = lexical_weight::$MAX_N + 0.01));
    }

    /**
     * testWeightComma 
     * Check that if the provided value contains a comma, it's automatically replaced by a . (dot)
     * 
     * @access public
     * @return void
     */
    public function testWeightComma() {
        $input = '1,23';
        $expected = array('1.23');
        $this->validator->validate($input);
        $this->assertEquals($this->validator->getTokens(), $expected);
    }

    /**
     * testSuperEmpty 
     * Ensure that super profile can leave the field empty
     * 
     * @access public
     * @return void
     */
    public function testSuperEmpty() {
        $validator = new Lexical_WeightSuper();
        $this->assertTrue($validator->validate($input = ''));
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
        $validator = new Semantic_Weight();
        $this->assertEquals($result, (bool) $validator->validate($input));

    }

    /**
     * testUpdateEmpty 
     * If it's an update, weight can be empty, but only if the store != admin
     * 
     * @access public
     * @return void
     */
    public function testUpdateEmpty() {
        $validator = new Semantic_WeightUpdate();
        $input = array(
            'weight' => array('', ''), 'type' => array('simple', 'simple'), 'store' => array(array('es', 'en'), array('es', 'ca')), 'sku' => array('mysku', 'mysku2')
        );
        $this->assertTrue((bool) $validator->validate($input));
        $input['store'][0][] = 'admin';
        $this->assertFalse((bool) $validator->validate($input));
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

        $validator = new Semantic_Weight();
        $input = array(
            'weight' => array('', ''), 'type' => array('simple', 'simple'), 'store' => array(array('es', 'en'), array('es', 'ca')), 'sku' => array('mysku', 'mysku2')
        );
        $this->assertFalse((bool) $validator->validate($input));
        //There must be an error regarding the given input
        $this->assertContains("mysku", $validator->getErrorMsg($validator->getErrors()));
        $this->assertContains("mysku2", $validator->getErrorMsg($validator->getErrors()));
    }
}
