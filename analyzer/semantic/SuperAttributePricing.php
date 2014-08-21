<?php
/**
 * Semantic_SuperAttributePricing
 * Compute the column, validating the price columns of each attribute
 *
 * @uses Abstract_Semantic
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Semantic_SuperAttributePricing extends Abstract_Semantic {

    protected $errors;
    const DEBUG = FALSE;

    public function validate(&$input) {
        //Ensure the price column is set
        if(!isset($input['price'])) {
            return false;
        }
        $ret = true;
        $this->errors = '';
        //For each configurable product
        foreach (array_keys($input['type'], 'configurable') as $con) {
            $special = array();
            //For each simple product associated
            foreach(array_intersect($input['sku'], $input['simples_skus'][$con]) as $sim => $sku) {
                //Ensure the simple's price matches the configurable's or is empty
                if($input['price'][$con] !== $input['price'][$sim] && !empty($input['price'][$sim]) && !empty($input['price'][$con])) {
                    $special[] = $sim;
                }
            }
            //If the special price array is not empty, we have to compute the super_attribue_pricing column
            if(count($special) > 0) {
                if(self::DEBUG) {
                    echo '<pre>The simples\' prices don\'t match the configurable, computing super_attribute_pricing column... ' . "\n";
                }
                $computed = '';
                $ret &= $this->compute($input, array_keys(array_intersect($input['sku'], $input['simples_skus'][$con])), $con, $computed);
                $input['super_attribute_pricing'][$con] = $computed;
                //Exit on error
                if(!$ret) {
                    break;
                }
            }
        }
        return $ret;
    }

    /**
     * groupPrices
     * Group the prices for each attribute value in an associative array
     *
     * @param array $input
     * @param array $special
     * @param mixed $attr
     * @param mixed $attr_price
     * @access protected
     * @return array
     */
    protected function groupPrices(array $input, array $special, $attr, $attr_price) {
        $values = array();

        foreach($special as $simple) {
            //Skip empty values
            if($input[$attr_price][$simple] === '') {
                if(self::DEBUG) echo 'Skipping empty value: ' . $input[$attr_price][$simple] . "\n";
                continue;
            }
            if(isset($values[$input[$attr][$simple]])) {
                $values[$input[$attr][$simple]][$simple] = str_replace(',', '.', $input[$attr_price][$simple]);
            } else {
                $values[$input[$attr][$simple]] = array($simple => str_replace(',', '.', $input[$attr_price][$simple]));
            }
        }

        if(self::DEBUG) {
            echo 'Grouped values per attribute value:' . "\n";
            var_dump($values);
        }
        return $values;
    }

    /**
     * validate
     * Ensure that the price is unique per each attribute value
     *
     * @param mixed $values
     * @param mixed $attr
     * @access protected
     * @return void
     */
    protected function validatePrices($input, $values, $attr, $conf, array &$validation) {
        $ret = true;
        foreach($values as $ind => $value) {
            //Remove empty values, but not 0
            $filtered = array();
            foreach ($value as $i => $val) {
                if($val !== '') {
                    $filtered[$i] = $val;
                }
            }
            //Obtain the delta for this attribute value
            $delta = array_unique($filtered);
            //Store all the deltas in an array, to perform a global validation
            $validation[$attr][$ind] = $delta;
            //There can't be more than one value for the same attribute value (for this configurable product)
            if(count($delta) !== 1) {
                $this->errors .= 'The price of the attribute ' . $attr . '(value: ' .  $ind . ') must be unique for the product '
                    . $input['sku'][$conf] . ', but the following values where provided: ' . implode($delta, ', ') . "\n";
                $ret = false;
                break;
            } else {
                $values[$ind] = array_shift(array_values($delta));
            }
        }
        return $ret;
    }

    /**
     * generatePrices
     * Attempt to generate the $attr_price column if there is only one configurable attribute and the column is missing
     *
     * @param array $input
     * @param array $special
     * @param mixed $conf
     * @param mixed $attr
     * @param mixed $attr_price
     * @access protected
     * @return array
     */
    protected function generatePrices(array &$input, array $special, $conf, $attr, $attr_price) {
        $input[$attr_price] = array();
        foreach($special as $simple) {
            $input[$attr_price][$simple] = $input['price'][$simple] - $input['price'][$conf];
        }
        return $this->groupPrices($input, $special, $attr, $attr_price);
    }

    protected function buildString($values) {
        $ret = '';
        foreach ($values as $key => $value) {
            $ret .= $key . ':' . reset($value) . ';';
        }
        return $ret;
    }
    /**
     * compute
     * Ensure that each attribute value has a unique price
     * Validate that the computed price matches the specified one
     * Compute the string for the super_attribue_pricing field in an understandable magmi format
     *
     * @param array $input all the parsed data
     * @param array $special holds the index of each simple product with a different price
     * @param mixed $conf index of the configurable product, required for validating
     * @param mixed $computed string to be appended on the colum of the current configurable
     * @access protected
     * @return bool
     */
    protected function compute(array $input, array $special, $conf, &$computed) {
        $validation = array();
        $ret = true;
        //For each attribute
        foreach($input['configurable_attributes'][$conf] as $attr) {
            if(self::DEBUG) {
                echo 'Processing attribute: ' . $attr . "\n";
            }
            $attr_price = $attr . '_price';
            //Check that the column $attr and '$attr(_price)' exists, otherwise skip the attribute
            if(!isset($input[$attr]) || !isset($input[$attr_price])) {
                if(self::DEBUG) {
                    echo 'Skiping empty attribute: ' . $attr . "\n";
                }
                continue;
            }

            $values = $this->groupPrices($input, $special, $attr, $attr_price);
            $ret &= $this->validatePrices($input, $values, $attr, $conf, $validation);
            //Generate the string for this attribute
            $computed .= $attr . '::' . $this->buildString($values) . ',';
        }

        //If only one attribute is present, autogenerate the column
        if(empty($values) && count($input['configurable_attributes'][$conf]) == 1) {
            if(self::DEBUG) {
                echo 'Generating column ' . $attr . '_price' . "\n";
            }
            $values = $this->generatePrices($input, $special, $conf, $attr, $attr_price);
            $ret &= $this->validatePrices($input, $values, $attr, $conf, $validation);
            //Generate the string for this attribute
            $computed .= $attr . '::' . $this->buildString($values) . ',';
        }

        //Remove trailing comma
        $computed = substr($computed, 0, -1);

        if($ret) {
            if(self::DEBUG) {
                echo 'Validation passed: Unique price per attribute values (ie. The same model can\'t have two different prices)' . "\n";
            }
            //Verify that the prices match
            $ret &= $this->verify($input, $validation, $conf);
        }
        //The array will be empty only if the columns wheren't present on the CSV
        if(count($validation) == 0) {
            $this->errors .= 'At least one of the following column(s): ' .
                implode($input['configurable_attributes'][$conf], '_price, ') .
                '_price must be present if the simple\'s prices doesn\'t match the configurable (' . $input['sku'][$conf] . ')' . "\n";
            $ret = false;
        }
        return $ret;
    }

    /**
     * verify
     * Verify that for each simple product, the sum of all the attribute values plus the configurable price, matches the price of the simple product.
     *
     * @param array $input
     * @param array $validation
     * @param mixed $conf
     * @access protected
     * @return bool
     */
    protected function verify(array $input, array $validation, $conf) {
        $prices = array();
        //Finally, validate that the price matches
        if(self::DEBUG) {
            echo 'Verification (The sum of the prices of the attribute values + the configurable price, must match the simple product price, result should be 0): ' . "\n";
        }
        //For each attribute
        foreach($validation as $attr => $values) {
            //For each product
            foreach ($values as $val => $product) {
                //For each value
                foreach ($product as $index => $value) {
                    if(isset($prices[$index])) {
                        if(self::DEBUG) {
                            echo 'Price(' . $input['sku'][$index] . '):' . $prices[$index] . ' - ' . $value . ' = ' . (number_format($prices[$index], 4) - number_format($value, 4)) . "\n";
                        }
                        $prices[$index]-= number_format($value, 4);
                    } else {
                        $prices[$index] = number_format(floatval($input['price'][$index]), 4) - number_format(floatval($input['price'][$conf]) + floatval($value), 4);
                        if(self::DEBUG) {
                            echo 'Price(' . $input['sku'][$index] . '):' . $input['price'][$index] . ' - (' . $input['price'][$conf] . ' + ' . $value . ') = ' . $prices[$index] . "\n";
                        }
                    }
                }
            }
        }

        //After iterating over all the attribute values of each simple product, all the prices should be equal to zero
        $res = array_filter($prices);
        if(count($res) != 0) {
            $simple = array_shift(array_keys($res));
            $this->errors .= 'Line ' . ($simple + 2) . ', the sum of the attribute_price(s) plus the configurable\'s price (' . $input['sku'][$conf] .
                ') must match the simple product\'s price (' . $input['sku'][$simple]. ")\n";
        }
        return count($res) == 0;
    }

    /**
     * getErrorMsg
     * Retrieve an explanation of the error found
     *
     * @param array $input
     * @access public
     * @return string
     */
    public function getErrorMsg($input) {
        return $this->errors;
    }
}
