<?php
/*
Name: DRK PHP utils
Description: Various useful PHP utilities:
	- drk_merge_arrays
	- drk_implode
	- drk_print_r
	- drk_implode_recursive
	- drk_sluggify
	- drk_proper_case
	- drk_find_multiplier
	- drk_subval_sort
	- drk_excel_column
Version: 0.2
Author: David Kane
Author URI: http://drkane.co.uk/
*/

/**
 * Merges two arrays. If a value is set it the second array, it is returned, otherwise the default is returned
 */	
if ( ! function_exists ( 'drk_merge_arrays' ) ) {
function drk_merge_arrays ( $defaults, $options = array () , $recursive=true ) {
	if ( is_array ( $defaults ) && is_array ( $options )  ) {
		foreach ( $defaults as $key=>&$value ) {
			if ( isset ( $options[$key] )  ) {
				if ( is_array ( $value ) && is_array ( $options[$key] ) &&$recursive ) {
					$value = drk_merge_arrays ( $value, $options[$key] );
				} else {
					$value = $options[$key];
				}
			}
		}
		return $defaults;
	} else {
		return false;
	}
}
}

/**
 * Extends the implode function. If the implode returns something that isn't a blank string, it adds $before and $after. Otherwise, it returns the $default
 */	
if ( ! function_exists ( 'drk_implode' ) ) {
function drk_implode ( $glue, $pieces, $before="", $after="", $default="" ) {
	$return = '';
	$return .= implode ( $glue, $pieces );
	if ( $return!='' ) {
		$return = $before . $return . $after;
	} else {
		$return = $default;
	}
	return $return;
}
}

/**
 * Extends print_r. By default, surrounds it with pre tags for better readability
 */	
if ( ! function_exists ( 'drk_print_r' ) ) {
function drk_print_r ( $var, $return=false, $before='<pre>', $after='</pre>' ) {
	$pretty = $before . print_r ( $var, true )  . $after;
	if ( $return ) {
		return $pretty;
	} else {
		echo $pretty;
		return true;
	}
}
}

/**
 * Recursive implode function - also implodes child arrays with the same $glue
 */	
if ( ! function_exists ( 'drk_implode_recursive' ) ) {
function drk_implode_recursive ( $glue, $pieces )  {
	$return = "";
	if  (  is_array  (  $pieces  )   )  {
		foreach  ( $pieces as &$piece ) {
			if  (  is_array  (  $piece  )   )  {
				$piece = drk_implode_recursive (  $glue, $piece  ); 
			}
		}
		$return = implode (  $glue, $pieces  );
	} else if  (  is_string  (  $pieces  )   )  {
		$return = $pieces;
	}
	return $return;
}
}

/**
 * Returns as slugged version of a text string - lowercase, spaces replaced with underscores, all non alphanumeric characters removed
 */	
if ( ! function_exists ( 'drk_sluggify' ) ) {
function drk_sluggify($name=''){
	if(is_array($name)){
		foreach($name as &$n){
			drk_sluggify($n);
		}
	} else if(is_string($name)){
		$name = str_replace(" ","_",$name);
		$name = preg_replace("/[^a-zA-Z0-9_]/"," ",$name);
		$name = strtolower($name);
	}
	return $name ;
}
}

/**
 * Converts uppercase or lowercase text to proper case
 */	
if(!function_exists("drk_proper_case")){
function drk_proper_case($str,$type=0) {
/* 
** Function to convert an organisation name from upper 
** or lower case letters into Proper Case (aka Title Case).
** If $type is 1 then it will only capitalise the first
** letter of each sentence. If $type is 2 the word is treated
** as a name.
** @str - the string to be converted
** @type - the type of string:
**		0 = capitalise every word (with exceptions)
**		1 = capitalise the first letter of every sentence
**		2 = treat as a name (capitalise every word (with exceptions))
*/

	// A list of exempt words. 
	// The left side is the original (in lower case),
	// the right side is the proper case version.
	// You can add more words to the end of the array.
	$words = array (
		"and"=>"and",
		"to"=>"to",
		"the"=>"the",
		"of"=>"of",
		"for"=>"for",
		"uk"=>"UK",
		"uk)"=>"UK)",
		"u.k."=>"U.K.",
		"uk."=>"UK.",
		"u.k"=>"U.K",
		"gb"=>"GB",
		"usa"=>"USA",
		"a"=>"a",
		"as"=>"as",
		"i"=>"I",
		"st"=>"St",
		"at"=>"at",
		"in"=>"in",
		"u3a"=>"U3A",
		"ict"=>"ICT",
		"ncvo"=>"NCVO",
		"st."=>"St.",
		"on"=>"on",
		"ltd"=>"Ltd",
		"ltd."=>"Ltd.",
		"raf"=>"RAF",
		"wos"=>"WOS",
		"snco"=>"SNCO",
		"icw"=>"ICW",
		"with"=>"with",
		"ymca"=>"YMCA",
		"ywca"=>"YWCA",
		"pta"=>"PTA",
		"ptfa"=>"PTFA",
		"or"=>"or",
		"vi"=>"VI",
		"vii"=>"VII",
		"ii"=>"II",
		"iii"=>"III",
		"iv"=>"IV",
		"yha"=>"YHA"
		);
		
	if($type==2){
		$words["ms"]="Ms";
		$words["obe"]="OBE";
		$words["cbe"]="CBE";
		$words["kbe"]="KBE";
		$words["mbe"]="MBE";
		$words["mrs"]="Mrs";
		$words["mr"]="Mr";
		$words["dr"]="Dr";
		$words["lt"]="Lt";
		$words["a"]="A";
		$words["snr"]="Snr";
		$words["cllr"]="Cllr";
		$words["fca"]="FCA";
		$words["flt"]="Flt";
	}
		
	// convert the whole string to lower case and trim it
	$str = strtolower($str);
	$str = trim($str);
	
	// regex expression for consonants
	$preg_vowel = "/^[^aeiouyAEIOUY0-9]{1,5}?$/";
	// regex expression for non-alphabetic characters
	$preg_bracket = "/[^a-zA-Z0-9]/";
	// regex expression for word delimiters
	$preg_word = "/(\ |\-|\t|\/)/";
	// regex expression for sentence delimiters
	$preg_sentence = "/(\.|\?|\!|\n)/";
	
	// convert to an array of words
	$proper = preg_split($preg_word,$str,-1,PREG_SPLIT_DELIM_CAPTURE);
	
	// check if the length of the string equals the number of words
	// (so if each word is one letter long) and if so just capitalise the whole thing.
	if(count($proper)==strlen(implode($proper))){
		$str = strtoupper($str);
		
	} else {
		// otherwise iterate through each word in the string
		foreach ($proper as &$p) {
			$prefix = "";
			
			// check if the first letter of a word is non-alpha or numeric,
			// then separate the first letter from the rest, iterate until you find a letter or number
			while(preg_match($preg_bracket,substr($p,0,1))>0){
				$prefix .= substr($p,0,1);
				$p = substr($p,1);
			}
			
			// if the word is found in the list of exemptions then
			// use the proper case version
			if(isset($words[$p])) {
				$p = $words[$p];
			} else {
			
				// if the word is nothing but consonants then put all in uppercase
				if(preg_match($preg_vowel,$p)>0){
					$p = strtoupper($p);
				} else {
					// otherwise put first letter in uppercase and the rest in lowercase
					// unless you're looking for sentence case, when it doesn't capitalise first letter
					if($type!=1){
						$p = ucfirst($p);
						
						// for names, correctly uppercase O'X and McX 
						if($type==2){
							if(substr($p,0,2)=="O'"||substr($p,0,2)=="Mc"){
								$p = substr($p,0,2) . strtoupper(substr($p,2,1)) . substr($p,3);
							}
						}
					}
				}
			}
			
			// add back in the non-alpha characters from the start of the string
			$p = $prefix . $p;
		}
		
		// put the whole string back together again
		$str = implode($proper);
		
		// if Proper Case needed then make a new array with just one entry,
		// otherwise split the text into sentences
		if($type!=1){
			$str2[] = $str;
		} else {
			$str2 = preg_split($preg_sentence,$str,-1,PREG_SPLIT_DELIM_CAPTURE);
		}
		
		// iterate through the new array, making the first letter of each string uppercase
		foreach($str2 as &$s){
		
			// make sure you capitalise the first alpha character
			$prefix = "";
			while(preg_match($preg_bracket,substr($s,0,1))>0){
				$prefix .= substr($s,0,1);
				$s = substr($s,1);
			}
			$s = $s = ucfirst($s);
			$s = $prefix . $s;
		}
		
		// bring the whole thing back together
		$str = implode($str2);
	}
	
	
	// return the resulting string
	return $str;
}
}

/**
 * Finds the most appropriate multiplier to use on a number
 */	
if ( ! function_exists ( 'drk_find_multiplier' ) ) {
function drk_find_multiplier($number){
	$return = false;
	if(is_numeric($number)){
		if($number>1000000000000){
			$return = array("number"=>1000000000000,
				"suffix"=>"tn",
				"name"=>"trillions"); 
		} else if($number>1000000000){
			$return = array("number"=>1000000000,
				"suffix"=>"bn",
				"name"=>"billions"); 
		} else if($number>1000000){
			$return = array("number"=>1000000,
				"suffix"=>"m",
				"name"=>"millions"); 
		} else if($number>1000){
			$return = array("number"=>1000,
				"suffix"=>"000s",
				"name"=>"thousands"); 
		} else {
			$return = array("number"=>1,
				"suffix"=>"",
				"name"=>""); 
		}
	}
	return $return;
}
}

/**
 * Sorts a multidimensional array
 */	
if ( ! function_exists ( 'drk_subval_sort' ) ) {
function drk_subval_sort($a,$subkey,$direction="reverse") {
/* 
** a function for sorting a multidimensional array 
** stolen from http://www.firsttube.com/read/sorting-a-multidimensional-array-with-php/
** 		$a = array to sort
**		$subkey = the key of the contained array to sort by
** returns a new array sorted by the subkey
*/
	if(is_array($a)){
		foreach($a as $k=>$v) {
			$b[$k] = strtolower($v[$subkey]); // make an array of the subkeys
		}
		if(isset($b)){
			if($direction=="reverse"){
				arsort($b); // sort the array of subkeys in reverse
			} else {
				asort($b); // sort the array of subkeys
			}
			foreach($b as $key=>$val) {
				$c[$key] = $a[$key]; // remake original array based on the order of the subkeys
			}
			return $c;
		} else {
			return $a; // if no array made then return the original
		}
	} else {
		return $a; // if no array made then return the original
	}
}
}

/**
 * Given a column number, finds the excel column letter associated with it
 */	
if ( ! function_exists ( 'drk_excel_column' ) ) {
function drk_excel_column ( $col_number = 1 ) {
	if ( $col_number > 26 ) {
		$remainder = $col_number % 26;
		$col_number = floor ( $col_number / 26 );
		$col = drk_excel_column ( $col_number ) . drk_excel_column ( $remainder );
	} else {
		$col = chr( $col_number + 64 );
	}
	return $col;
}
}