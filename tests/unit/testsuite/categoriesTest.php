<?php
/**
 * Etailers_Categories_Test
 *
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Etailers_Categories_Test extends PHPUnit_Framework_TestCase {
    private static $callBackResult;

    public function setUp() {
        $this->validator = new Lexical_Categories();
    }

    protected static $inputs = array(
        array('Hombres ;; Mujeres', 'Hombres;;Mujeres', true),
        array('Hombres / Camisas / Azules', 'Hombres/Camisas/Azules', true),
        array(' Marcas ;; Primavera de 2013', 'Marcas;;Primavera de 2013', true),
        array('', '', true)    //Optional field
    );


    public static function providerFormat()
    {
        return self::$inputs;
    }

    /**
     * testCategoriesFormat
     * Ensure that the categories are being properly formated, triming the trailing spaces
     *
     * @dataProvider providerFormat
     * @param mixed $input
     * @param mixed $output
     * @param mixed $result
     * @access public
     * @return void
     */
    public function testCategoriesFormat($input, $output, $result) {
        $this->assertEquals($result, $this->validator->validate($input));
        $this->assertEquals($output, $input);
    }

    /**
     * testCategoriesError Proper error reporting
     *
     * @access public
     * @return void
     */
    public function testCategoriesError() {
        $input = 'anything';
        $this->assertContains($input, $this->validator->getErrorMsg($input));
    }

    public static function callBack() {
        return array_pop(self::$callBackResult);

    }

    /**
     * generateMysqlMock
     *
     * @param mixed $attribute_id
     * @access protected
     * @return void
     */
    protected function generateMysqlMock($attribute_id, $paths) {
        self::$callBackResult = $paths;
        //Mock result object
        $query_result = $this->getMockBuilder('Result')
            ->disableOriginalConstructor()
            ->setMethods(array('fetch_object'))
            ->getMock();
        $query_result->expects($this->any())
            ->method('fetch_object')
            ->will($this->returnCallback(array('Etailers_Categories_Test', 'callBack')));

        //Mock Mysqli object
        $dblink = $this->getMock('Mysqli', array('query', 'real_escape_string'));
        $dblink->expects($this->any())
            ->method('query')
            ->will($this->returnValue($query_result));
        return $dblink;
    }

    /**
     * testCategoriesSemantic
     * TODO: Create mocks to test this validator better
     *
     * @access public
     * @return void
     */
    public function testCategoriesSemanticInit() {
        //Example data
        $attribute_id = 123;
        $dataCats = array(
            (object) array('path' => '/456/33', 'name' => 'Mujer', 'entity_id' => 6, ),
            (object) array('path' => '/123/12', 'name' => 'Niño', 'entity_id' => 5, ),
            (object) array('path' => '/112/12', 'name' => 'Hombre', 'entity_id' => 4, 'attribute_id' => $attribute_id),
        );

        //Enable access to protected methods/properties
        $reflection_class = new ReflectionClass("Semantic_Categories");
        $method = $reflection_class->getMethod("initData");
        $method->setAccessible(true);
        $property1 = $reflection_class->getProperty('attribute_id');
        $property1->setAccessible(true);
        $property2 = $reflection_class->getProperty('root_cats');
        $property2->setAccessible(true);

        $validator = new Semantic_Categories($this->generateMysqlMock($attribute_id, $dataCats));

        //Call the method and check the expected results
        $method->invoke($validator);
        $this->assertEquals($attribute_id, $property1->getValue($validator));
        $this->assertEquals(array('/123/12', '/456/33'), $property2->getValue($validator));

        return $validator;
    }

    /**
     * testCategoriesSemanticCheckPath
     * The test testCategoriesSemanticInit must be executed previously in order to initialize the categories data
     *
     * @depends testCategoriesSemanticInit
     * @param mixed $validator
     * @access public
     * @return void
     */
    public function testCategoriesSemanticCheckPath($validator) {
        $dataPaths = array(
            (object) array('path' => '/456/33/5', 'name' => 'Niño', 'entity_id' => 5, ),
            (object) array('path' => '/456/33/5/6', 'name' => 'Camisas', 'entity_id' => 6, ),
        );
        //Enable access to protected methods/properties
        $reflection_class = new ReflectionClass("Semantic_Categories");
        $method = $reflection_class->getMethod("checkPath");
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($validator, 'Niño/Camisas', $this->generateMysqlMock(123, $dataPaths)->query('foo')));
        $this->assertFalse($method->invoke($validator, 'Niño/Pijamas', $this->generateMysqlMock(123, $dataPaths)->query('foo')));

    }
}
