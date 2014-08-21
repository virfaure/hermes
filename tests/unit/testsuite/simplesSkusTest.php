<?php
/**
 * Etailers_SimplesSkus_Test
 *
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Etailers_SimplesSkus_Test extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->validator = new Lexical_SimplesSkus();
    }

    protected static $inputs = array(
        //Happy case
        array('sku1,sku2,sku3', 'sku1,sku2,sku3', array('sku1', 'sku2', 'sku3')),
        //Empty values between commas
        array('sku1,,sku2,sku4', 'sku1, sku2, sku4', array('sku1', 'sku2', 'sku4')),
        //Trailing commas
        array('sku1,sku2,sku4,,,,,,', 'sku1, sku2, sku4', array('sku1', 'sku2', 'sku4')),
        //Optional field
        array('', '', array())
    );

    protected static $inputsSemantic = array(
        //Happy case, skus not repeated
        array(
            array(
                'merged' => array('sku1'),
                'input' => array('sku' => array('sku1'), 'type' => array())
            ),
            true,
            null
        ),
        //simples_skus repeated error
        array(
            array(
                'merged' => array('sku1', 'sku1'),
                'input' => array(
                    'sku' => array('sku1'),
                    'type' => array()
                )
            ),
            false,
            Semantic_SimplesSkus::UNIQUE_ERROR,
        ),
        //configurable product with simples skus column missing on the csv
        array(
            array(
                'merged' => array('sku1'),
                'input' => array(
                    'sku' => array('sku1'),
                    'type' => array('configurable')
                )
            ),
            false,
            Semantic_SimplesSkus::SIMPLES_SKUS_ERROR,
        ),
        //configurable product with simples skus present in csv
        array(
            array(
                'merged' => array('sku1'),
                'input' => array(
                    'sku' => array('sku1', 'sku2'),
                    'type' => array('configurable', 'simple'),
                    'simples_skus' => array(array('sku2'))
                )
            ),
            true,
            null,
        ),
        //sku not found on the csv
        array(
            array(
                'merged' => array('sku1'),
                'input' => array(
                    'sku' => array('sku1'),
                    'type' => array('configurable', 'simple'),
                    'simples_skus' => array(array('sku2')),
                )
            ),
            false,
            //Should match Semantic_SimplesSkus::SKU_NOT_FOUND_ERROR format
            'SimplesSkus(s) "sku1" must exist either in the CSV or in the database.',
        ),
        //sku not found on the csv, but previously imported
        array(
            array(
                'merged' => array('sku1'),
                'input' => array(
                    'sku' => array('sku1'),
                    'type' => array('configurable', 'simple'),
                    'simples_skus' => array(array('sku2')),
                ),
                'imported' => array('sku2'),
            ),
            true,
            null
        ),
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
     * testSimplesSkusFormat
     * Ensure that the format of the SimplesSkus is being properly validated.
     *
     * @dataProvider providerFormat
     * @param mixed $input
     * @param mixed $correctedInput wrong imputs should be corrected
     * @param mixed $result empty values should be filtered from token list
     * @access public
     * @return void
     */
    public function testSimplesSkusFormat($input, $correctedInput, $result) {
        $this->assertTrue($this->validator->validate($input));
        $this->assertEquals($correctedInput, $input);
        $this->assertEquals($result, reset($this->validator->getTokens()));
    }

    /**
     * testSimplesSkusSemantic
     *
     * @dataProvider providerSemantic
     * @param mixed $input
     * @param mixed $result
     * @param mixed $error_msg
     * @access public
     * @return void
     */
    public function testSimplesSkusSemantic($input, $result, $error_msg) {
        $validator = new Semantic_SimplesSkus();
        $this->assertEquals($result, $validator->validate($input));
        if(!$result) {
            $this->assertEquals(sprintf($error_msg, $input['input']['sku'][0]), $validator->getErrorMsg($input['input']['sku']));
        }
    }
}
