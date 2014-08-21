<?php
/**
 * Etailers_SuperAttributePricing_Test
 *
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Etailers_SuperAttributePricing_Test extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->validator = new Semantic_SuperAttributePricing();
    }

    protected static $semantic_inputs = array(
        //Price column is mandatory
        array(
            array(
                'sku' => array('mysku')
            )
            , false, ''),
        //Happy case empty price
        array(
            array(
                'price' => array('', '', ''),
                'type' => array('configurable', 'simple', 'simple'),
                'sku' => array('mysku', 'mysku2', 'mysku3'),
                'simples_skus' => array(
                    array('mysku2', 'mysku3'), null, null
                )
            )
            , true, 'null'),
        //Happy case same price
        array(
            array(
                'price' => array('11', '11', '11'),
                'type' => array('configurable', 'simple', 'simple'),
                'sku' => array('mysku', 'mysku2', 'mysku3'),
                'simples_skus' => array(
                    array('mysku2', 'mysku3'), null, null
                )
            )
            , true, 'null'),
        //One configurable attribute, autocompute the column
        array(
            array(
                'price' => array('10', '15', '20'),
                'type' => array('configurable', 'simple', 'simple'),
                'sku' => array('mysku', 'mysku2', 'mysku3'),
                'simples_skus' => array(
                    array('mysku2', 'mysku3'), null, null
                ),
                'configurable_attributes' => array(
                    array('color'),
                    array('color'),
                    array('color'),
                ),
                'color' => array('', 'rojo', 'verde'),
            )
            , true, 'null'),
        //Two configurable attributes and missing price columns
        array(
            array(
                'price' => array('10', '', '20'),
                'type' => array('configurable', 'simple', 'simple'),
                'sku' => array('mysku', 'mysku2', 'mysku3'),
                'simples_skus' => array(
                    array('mysku2', 'mysku3'), null, null
                ),
                'configurable_attributes' => array(
                    array('color', 'talla'),
                    array('color', 'talla'),
                    array('color', 'talla'),
                ),
                'color' => array('', 'rojo', 'verde'),
            )
            , false,
            "At least one of the following column(s): color_price, talla_price must be present if the simple's prices doesn't match the configurable (mysku)"
        ),
        //Two configurable attributes and price columns present
        array(
            array(
                'price' => array('10', '', '20'),
                'type' => array('configurable', 'simple', 'simple'),
                'sku' => array('mysku', 'mysku2', 'mysku3'),
                'simples_skus' => array(
                    array('mysku2', 'mysku3'), null, null
                ),
                'configurable_attributes' => array(
                    array('color', 'talla'),
                    array('color', 'talla'),
                    array('color', 'talla'),
                ),
                'color' => array('', 'rojo', 'verde'),
                'color_price' => array('', '', '10')
            )
            , true, null
        ),
    );

    public static function providerSemantic() {
        return self::$semantic_inputs;
    }

    /**
     * testSuperAttributePricingFormat
     * Ensure that the format of the SuperAttributePricing is being properly validated.
     * The provided value must be a positive decimal number (commas and . are allowed).
     *
     * @dataProvider providerSemantic
     * @param mixed $input
     * @param mixed $result
     * @param mixed $error
     * @access public
     * @return void
     */
    public function testSuperAttributePricingFormat($input, $result, $error) {
        $this->assertEquals($result, $this->validator->validate($input));
        if(!$result) {
            $this->assertEquals($error, trim($this->validator->getErrorMsg($input)));
        }
    }
}
