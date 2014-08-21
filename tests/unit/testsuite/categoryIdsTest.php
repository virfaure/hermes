<?php
/**
 * Etailers_CategoryIds_Test
 *
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Etailers_CategoryIds_Test extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->validator = new Lexical_CategoryIds();
    }

    protected static $inputs = array(
        array('1,2,3,4,5', true),
        array('1,2.45,3.234,4,5', false),
        array('1, asdf, 435', false),
        array('asdf', false),
        array('', false) //Required field
    );

    public static function providerFormat()
    {
        return self::$inputs;
    }

    /**
     * testCategoryIdsFormat
     * Ensure that the format of the CategoryIds is being properly validated.
     * Only comma-separated integers should be allowed
     *
     * @dataProvider providerFormat
     * @param mixed $input
     * @param mixed $result
     * @access public
     * @return void
     */
    public function testCategoryIdsFormat($input, $result) {
        $this->assertEquals($result, $this->validator->validate($input));
    }

    /**
     * testCategoryIdsError Proper error reporting
     *
     * @access public
     * @return void
     */
    public function testCategoryIdsError() {
        $this->assertFalse($this->validator->validate($input= '1, asdf'));
        $this->assertContains($input, $this->validator->getErrorMsg($input));

    }

    public function testSuperAllowedEmpty() {
        $validator = new Lexical_CategoryIdsSuper();
        $this->assertTrue($validator->validate($input= ''));
    }
}
