<?php

global $path_to_root;
$path_to_root = __DIR__ . "/../../../";

//we need to interpret the file and generate a new statement for each day of items

/***************************************************
*
*	**	MANTIS 3043	**
*
*****************************************************/


/**//************************************
* Parse a CSV string accomodating where there is a \n encapsulated in a field
*
* Copied into ksf_file_csv.
*
* @param string the CSV data
* @param char delimieter default comma
* @param bool should we skip empty lines, default true
* @param bool should we trim fields, default true
* @returns array 
************************************************************************************************/
function parse_csv ($csv_string, $delimiter = ",", $skip_empty_lines = true, $trim_fields = true)
{
    $enc = preg_replace('/(?<!")""/', '!!Q!!', $csv_string);
    $enc = preg_replace_callback(
        '/"(.*?)"/s',
        function ($field) {
            return urlencode(utf8_encode($field[1]));
        },
        $enc
    );
    $lines = preg_split($skip_empty_lines ? ($trim_fields ? '/( *\R)+/s' : '/\R+/s') : '/\R/s', $enc);
    return array_map(
        function ($line) use ($delimiter, $trim_fields) {
            $fields = $trim_fields ? array_map('trim', explode($delimiter, $line)) : explode($delimiter, $line);
            return array_map(
                function ($field) {
                    return str_replace('!!Q!!', '"', utf8_decode(urldecode($field)));
                },
                $fields
            );
        },
        $lines
    );
}

/**//***************************************************************
* Base class for parsing a CSV into an array
*
* @since 20240825
*
* Depends on an inheritor class to define source file header fields
* and the assoc array's resulting index (name key) for the array
********************************************************************/
class csv_string_to_assoc_arr {

	protected $search_arr;
	protected $replace_arr;
	protected $sid_field;	//What field in the array to use to make the SID
	protected $linecount;		//!<int how many lines did we process
	protected $statementcount;	//!<int how many statements did we process.
	protected $debg_level;

	/**//*****************************************************
	* Log to the right level
	*
	* @param string message to log
	* @returns bool 
	**********************************************************/
	function logging( $msg, $loglevel = 0 )
	{
		switch( $loglevel )
		{
			case PEAR_LOG_ERROR:
				display_error( $msg );
				break;
			default:
				display_notification( $msg );
				break;
		}
		return true;	
	}
	function display_notification( $msg )
	{
		if( $this->debug_level > 0 )
		{
			$this->logging( $msg );
		}
	}
	/**//**
	 * Convert an array of  CSV lines into assoc array
	 *
	 * Called by parse
	 */
	function _combine_array(&$row, $key, $header) 
	{
  		$row = array_combine($header, $row);
	}

	/**//*********************************************************
	* Import a CSV
	*
	* @param string contents from file_get_contents
	* @param array static_data (currently unused)
	* @param bool debugging turned on or off
	* @return array
	**************************************************************/
	function parse($content, $static_data = array(), $debug = true) 
	{
		//keep statements in an array, hashed by statement-id
		//in some cases (bank statements), statement id is the statement date: yyyy-mm-dd-<number>-<seq>
		//in Square data, it is the transaction_id
		// as each line is processed, adjust statement data and add tranzactions

		$smts = array();

		//Processing the header line separate from other processing incase there are embedded returns in lines.
			$count=0;
			$lines_rows = str_getcsv( $content, "\n", "\"" );	//Lines with descriptions with embedded \n are separating.  Not recognizing the "
			$hdr = strtolower( $lines_rows[0] );
			$header_arr = str_getcsv( $hdr );

		//first line is header 
		if( ! empty( $this->replace_arr ) && ! empty( $this->search_arr ) )
		{
			$header_arr = str_replace( $this->search_arr, $this->replace_arr, $header_arr );
		}

		$items_arr = ( parse_csv( $content ) );

		/**************************************************************************************************/
		/**************************************************************************************************/

		//current transaction
		$trz = null;
		$trz_line = '';
		//last TRF transaction
		$last_trz = null;
		
		//parse lines
	
	
		$this->linecount = 0;
		$this->statementcount = 0;
//$debug=1;
		foreach($items_arr as $line) 
		{
			//if there is a header, parse_csv above includes it as the first array.
		/*
			if( $this->linecount == 0 AND count( $header_arr ) > 1 )
			{
				$this->linecount++;
				continue;
			}
		*/
			
			if (count($line) < 2)
				continue;
			if( $debug )
			{
				echo "----------------------------------------------------\n";
				echo "debug: line: " . print_r( $line ) . "\n";
			}
	
			$linedata = array_combine( $header_arr, $line );
			if( $debug )
			{
				display_notification( __FILE__ . "::" . __LINE__ . "::" . print_r( $linedata, true ) );
				//var_dump( $linedata );
			}
	
			if( ! empty( $linedata[ $this->sid_field ] ) ) 
			{
				if( $debug )
				{
					display_notification( __FILE__ . "::" . __LINE__ . ":: Using SID field $this->sid_field for SID:" . print_r( $linedata[$this->sid_field], true ) );	
				}
				$sid = $linedata[ $this->sid_field ];
			}
			else
			{
				if( $debug )
				{
					display_notification( __FILE__ . "::" . __LINE__ . ":: SID field $this->sid_field is empty.:" . print_r( $linedata[$this->sid_field], true ) . ": Using RAND for SID" );
				}
				$sid = rand();
			}
	
			//if smtid exists in results, add to this statement else create new statement
			if (empty($smts[$sid])) 
			{
				$smts[$sid] = array();
				$this->statementcount++;
				if( $debug )
				{
					display_notification( __FILE__ . "::" . __LINE__ . ":: Adding a statement with sid=$sid"   );
					//echo "debug: adding a statement with sid=$sid\n";
				}
			} else {
				if( $debug )
				{
					display_notification( __FILE__ . "::" . __LINE__ . ":: statement exists for sid=$sid::" . print_r( $stmts[$sid],  true ) );
					//echo "debug: statement exists for sid=$sid\n";
				}
			}
			$smts[$sid][] = $linedata;
			$this->linecount++;
		}
		//parsing ended, cleanup
		if( $debug )
		{
			display_notification( __FILE__ . "::" . __LINE__ . ":: statements::" . print_r( $smts,  true ) );
		}
		return $smts;
	}
}



