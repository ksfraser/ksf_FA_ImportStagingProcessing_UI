<?php

/**//*************************************************
* Abstract class for Parsers to start with
*
*	**	MANTIS 3043 - CSV Parser	**
*
*****************************************************/
abstract class parser {

    /**
     * actual parsing of the data
     * @return array
     */
    abstract function parse($string, $static_data = array(), $debug=false);
    
}
