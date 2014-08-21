<?php
/**
 * Etailers_Type_Test
 *
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Etailers_Type_Test extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->validator = new Lexical_Type();
    }

    protected static $inputs = array(
        array('simple', true),
        array('configurable', true),
        array('123', false),
        array('', false)    //Mandatory field
    );

    protected static $inputsSemantic = array(
        array(
            array(
                'type' => array('simple')
            )
            , true),
        //If any configurable, the simples_skus column must be set
        array(
            array(
                'type' => array('configurable'),
            ), false),
        //If any configurable, simples_skus must exist and configurable_attributes ashould be set as well
        array(
            array(
                'type' => array('configurable'),
                'simples_skus' => array()
            ), false),
        array(
            array(
                'type' => array('configurable'),
                'configurable_attributes' => array(),
            ), false),
        array(
            array(
                'type' => array('configurable'),
                'configurable_attributes' => array(),
                'simples_skus' => array(),
            ), true),
    );

    public static function providerFormat()
    {
        return self::$inputs;
    }

    public static function providerSemantic()
    {
        return self::$inputsSemantic;
    }

    /**
     * testTypeFormat
     * Ensure that the format of the type is being properly validated.
     *
     * @dataProvider providerFormat
     * @param mixed $input
     * @param mixed $result
     * @access public
     * @return void
     */
    public function testTypeFormat($input, $result) {
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
        $validator = new Lexical_TypeSuper();
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
        $this->assertFalse($this->validator->validate($input = '234'));
        //There must be an error regarding the given input
        $this->assertGreaterThanOrEqual(1, strpos($this->validator->getErrorMsg($input), $input));
    }

    /**
     * testSemantic
     *
     * @dataProvider providerSemantic
     * @access public
     * @return void
     */
    public function testSemantic($input, $result) {
        $validator = new Semantic_Type();
        $this->assertEquals($result, $validator->validate($input));
    }

    /**
     * testSemanticError
     *
     * @access public
     * @return void
     */
    public function testSemanticError() {
        $validator = new Semantic_Type();
        $this->assertNull($validator->getErrors());
        $this->assertEquals(Semantic_Type::ERROR_MESSAGE, $validator->getErrorMsg($input = null));
    }
}
