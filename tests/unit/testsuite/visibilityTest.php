<?php
/**
 * Etailers_Visibility_Test
 *
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Etailers_Visibility_Test extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->validator = new Lexical_Visibility();
    }
    protected static $inputs = array(
        array('Catálogo', true),
        array('Catalogo', true),
        array('Not Visible Individually', true),
        array('Busqueda', true),
        array('Búsqueda', true),
        array(1, true),
        array(2, true),
        array(3, true),
        array(4, true),
        array(0, false),
        array('asdf', false),
        array('', false)
    );

    protected static $inputsSemantic = array(
        //Happy case
        array(array('visibility' => array('1')), array('1'), true),
        //Configurable products are always visible
        array(
            array(
                'visibility' => array(''),
                'type' => array('configurable')
            ),
            array('4'), true),
        //Simple products aren't visible if not default attribute set
        array(
            array(
                'visibility' => array(''),
                'type' => array('simple'),
                'attribute_set' => array('Producto con Talla'),
            ),
            array('1'), true),
        //If empty attribute_set and admin/missing store, should fail
        array(
            array(
                'visibility' => array(''),
                'type' => array('simple'),
                'attribute_set' => array(''),
            ),
            array(''), false),
        array(
            array(
                'visibility' => array(''),
                'type' => array('simple'),
                'attribute_set' => array(''),
                'store' => array(array('admin', 'es')),
            ),
            array(''), false),
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
     * testVisibilityFormat
     * Ensure that the format of the type is being properly validated.
     * The provided code must be 13 characters long, and the last digit (control digit) must match the type computation.
     *
     * @dataProvider providerFormat
     * @param mixed $input
     * @param mixed $result
     * @access public
     * @return void
     */
    public function testVisibilityFormat($input, $result) {
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
        $validator = new Lexical_VisibilitySuper();
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
     * @param mixed $input
     * @param mixed $output
     * @param mixed $result
     * @access public
     * @return void
     */
    public function testSemantic($input, $output, $result) {
        $validator = new Semantic_Visibility();
        $this->assertEquals($result, $validator->validate($input));
        $this->assertEquals($output, $input['visibility']);
    }

    public function testSemanticError() {
        $validator = new Semantic_Visibility();
        $this->assertNull($validator->getErrors());
        $this->assertEquals(Semantic_Visibility::ERROR_MESSAGE, $validator->getErrorMsg($input = null));
    }
}
