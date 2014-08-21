<?php
/**
 * Etailers_Image_Test
 *
 * @uses PHPUnit_Framework_TestCase
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Etailers_Image_Test extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->validator = new Lexical_Image();
    }

    protected static $inputs = array(
        array('http://www.team-bhp.com/forum/attachments/shifting-gears/1033178d1356977973-official-non-auto-image-thread-_mg_0143.jpg', true),
        array('http://upload.wikimedia.org/wikipedia/commons/3/35/Siganus_corallinus_Brest.jpg', true),
        array('My Image File', true),
        array('image124.jpg', true),
        array('http://images.4ever.eu/data/674xX/drole/dessins/[images.4ever.eu]%20frimousse,%20univers,%20lever%20du%20soleil%20162350.jpg', false),
        array('im*age124.jpg', false),
        array('~½¬~½¬hpña.png', false),
        array('', true) //Optional field
    );

    public static function providerFormat()
    {
        return self::$inputs;
    }

    /**
     * testImageFormat
     * Ensure that the format of the Image field is being properly validated.
     * The provided values must be a comma-separated list of:
     * - URLs
     * - Filenames (that might include, or not, the extension)
     * The field can be empty
     *
     * @dataProvider providerFormat
     * @param mixed $input
     * @param mixed $result
     * @access public
     * @return void
     */
    public function testImageFormat($input, $result) {
        $this->assertEquals($result, $this->validator->validate($input));
    }

    public function testTokensStored() {
        $input1 = 'MyImage1.png';
        $input2 = 'http://upload.wikimedia.org/wikipedia/commons/thumb/7/7b/Earth_Western_Hemisphere.jpg/1024px-Earth_Western_Hemisphere.jpg';

        $this->validator->validate($input1);
        $this->validator->validate($input2);

        $tokens = $this->validator->getTokens();
        $this->assertTrue(count($tokens) == 2);
        $this->assertTrue($input1 === $tokens[0][0]);
        $this->assertTrue($input2 === $tokens[1][0]);
    }

    /**
     * testImageError
     * Ensure that the proper error is being shown.
     *
     * @access public
     * @return void
     */
    public function testImageError() {
        $this->assertFalse($this->validator->validate($input = '123i%"·$&%$&divide;(4543534'));
        $this->assertGreaterThanOrEqual(1, strpos($this->validator->getErrorMsg($input), $input));
    }

    /**
     * testSemanticImage Ensure that the semantic Image validation works as expected
     *
     * @access public
     * @return void
     */
    public function testValidSemanticImage() {
        //Simples-Configurables not installed: configurables must be set, simples can be empty
        $imgValidator = new Semantic_Image(false);

        $data = array('sku' => array('sku1', 'sku2', 'sku3', 'sku4'),
                    'image' => array(array('1234.jpg'), array(), array(), array()),
                    'type' => array('simple', 'configurable', 'simple', 'configurable'),
                    'brand' => array('Brand 1', 'Brand2', 'Brand3', 'Brand4')
                );
        $input = array(
            'input' => $data,
            'customSite' => false,
            'unique' => array('1234.jpg', '1235.jpg')
        );
        $res = $imgValidator->validate($input);
        $this->assertFalse((bool) $res);
        $this->assertContains('sku2', $imgValidator->getErrorMsg($imgValidator->getErrors()));
        $this->assertContains('sku4', $imgValidator->getErrorMsg($imgValidator->getErrors()));
        $this->assertNotContains('sku3', $imgValidator->getErrorMsg($imgValidator->getErrors()));

        //Simples-Configurables installed: configurables can be empty, simples must be filled
        $imgValidator = new Semantic_Image(true);
        $res = $imgValidator->validate($input);

        $this->assertFalse((bool) $res);
        $this->assertContains('sku3', $imgValidator->getErrorMsg($imgValidator->getErrors()));
        $this->assertNotContains('sku4', $imgValidator->getErrorMsg($imgValidator->getErrors()));

        //Allow empty image for configurables if flag is enabled
        $input['input']['image'][2][] = '1235.jpg';
        $res = $imgValidator->validate($input);
        $this->assertTrue($res);

        //Ensure that the brand is being prepended to the image name if it's a custom site
        $input['customSite'] = true;
        $res = $imgValidator->validate($input);
        $this->assertTrue($res);
        //The path should match and the brand should have been parsed to replace spaces with "_" and strip other special characters
        $this->assertContains(sprintf(Semantic_Image::CUSTOM_SITES_PATH, strtolower(Analyzer::skuableBrand('Brand_1'))) . '1234.jpg', $input['input']['image'][0]);

        //Url Validation for an existing url
        $input['unique'][0] = 'http://www.google.com/logos/2013/volodymyr_dakhnos_81st_birthday-1061005-hp.jpg';
        $res = $imgValidator->validate($input);
        $this->assertTrue($res);

        //Url Validation for a non-existing url
        $input['unique'][0] = 'http://www.google.com/asdf.jpg';
        $res = $imgValidator->validate($input);
        $this->assertFalse($res);

        //Url Validation for an invalid url
        $input['unique'][0] = 'http://www.google.com/asdf';
        $res = $imgValidator->validate($input);
        $this->assertFalse($res);
    }
}
