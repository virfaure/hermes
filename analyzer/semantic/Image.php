<?php
/**
 * Semantic_Image
 * For each valid URL, ensure that it's online and corresponds to a valid image.
 *
 * @uses Abstract_Semantic
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Semantic_Image extends Abstract_Semantic {
    const ERROR_LEXIC = 'Image field can\'t be empty at product(s) %s';
    const ERROR_SEMANTIC = 'Image(s) "%s" must exist.';
    const CUSTOM_SITES_PATH = '%s/photos/';

    private static $ALLOWED_EXTENSIONS = array('png', 'jpg', 'jpeg', 'gif');

    function __construct($simplesConfigurables = false) {
        $this->simplesConfigurables = $simplesConfigurables;
    }

    /**
     * Retrieve headers of a given URL
     * @param $url string
     * @return array
     */
    protected function getHeaders($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE); //Follow Location (ex: code 301)
        curl_setopt($ch, CURLOPT_NOBODY, TRUE); // remove body
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        curl_close($ch);
        return $head;
    }

    /*
     * Check wether all the provided images exist or not
     * Ensure that only certain products can have empty values:
     * - Configurable products just if the module simples-configurables is installed (flag simplesConfigurables)
     * - Simple products just if the module simples-configurables is NOT installed.
     *
     * @param $input array where each item is a valid url or a filename
     * @return bool
     */
    public function validate(&$input) {
        $ret = true;
        //Check if it's a custom site
        if(isset($input['customSite'])) {
            $brand = isset($input['input']['brand']) ? 'brand' : 'marca';
        }
        $this->errorText = self::ERROR_LEXIC;
        foreach ($input['input']['image'] as $index => $image) {
            if(empty($image) && (
                ($this->simplesConfigurables  && $input['input']['type'][$index] === 'simple') ||
                (!$this->simplesConfigurables && $input['input']['type'][$index] === 'configurable')
            )) {
                $this->_errors[] = $input['input']['sku'][$index];
                $ret &= false;
            }

            //Prepend the brand subfolder to the image filenames, only for the custom sites
            if(isset($input['customSite']) && $input['customSite']) {
                foreach($image as $i => $filename) {
                    if(strpos(strtolower($filename), 'http') !== 0) {
                        $input['input']['image'][$index][$i] =
                            sprintf(self::CUSTOM_SITES_PATH, strtolower(Analyzer::skuableBrand(str_replace(' ', '_', $input['input'][$brand][$index])))) .
                            $input['input']['image'][$index][$i];
                    }
                }
            }
        }
        //Check that the image URL's actually correspond to images, and are online
        /*if($ret) {
            $this->errorText = self::ERROR_SEMANTIC;
            foreach ($input['unique'] as $image) {
                if(strpos(strtolower($image), 'http') === 0) {
                    //Ensure that the img extension is present, and allowed
                    $name = explode('.', strtolower($image));
                    if(!in_array(array_pop($name), self::$ALLOWED_EXTENSIONS)) {
                        $this->errorText = 'The image URL must have any of the following extensions: "' .
                            implode(self::$ALLOWED_EXTENSIONS,'", "') . '", but "%s" provided.';
                        $ret = false;
                    }
                    //Retrieve url headers
                    $headers = $this->getHeaders($image);
                    //Ensure headers contains 200 OK and Content-Type: image
                    $ret = $ret && (bool) preg_match('/HTTP\/1\.\d 200 OK(?:.|\n)*Content-Type\: image/', $headers);
                    if(!$ret) {
                        $this->_errors[] = $image;
                        break;
                    }
                }
            }
        }*/
        return $ret;
    }

    public function getErrorMsg($input) {
        return sprintf($this->errorText, implode($input, ', '));
    }
}
