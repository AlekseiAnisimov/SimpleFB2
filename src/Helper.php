<?php
namespace SimpleFB2;

/**
* Class Helper
*
*
*/
class Helper
{
    /**
    * Generate random string
    * With string length 20 symbols
    * 
    * @return string
    */
    public static function generateRandomString()
    {   
        $result_str = '';
        $string = 'abcdefghijklmnopqrstuvwxyz';
        $alphabet_length = strlen($string);
        $string_length = 20;
        for ($i=0; $i<$string_length; $i++) {
            $char_num = random_int(0,$alphabet_length - 1);
            $symbol = $string[$char_num];
            $result_str .= $symbol;
        }
        return $result_str;
    }
}