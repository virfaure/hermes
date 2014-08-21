<?php
class Lexical_Marca extends Abstract_String {
    public static $MAX_LENGTH = 64;
    public static $MIN_LENGTH = 0;
}
//Brand and marca are the same field
class Lexical_Brand extends Lexical_Marca {}
