<?php
/**
 * Sku
 * Required, string between $MIN_LENGTH and $MAX_LENGTH
 * Allowed values are any alphanumeric string and the special characters: ".", "-", "_" and " "
 * Must be an unique identifier, but this will be checked on the semantic phase,
 * as the unique condition is actually for the combination sku+store.
 *
 * @uses Abstract_String
 * @copyright 2012 The Etailers S.L.
 * @author Javier Carrascal <javier.carrascal@theetailers.net>
 */
class Lexical_CategoryId extends Abstract_Number {
    public static $MAX_N = 999999;
    public static $MIN_N = 1;
}
