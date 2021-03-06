<?php
/******************************************************************
* Projectname:   PHP JSON-P Class 
* Version:       1.1
* Author:        Radovan Janjic <rade@it-radionica.com>
* Project:       https://github.com/uzi88/PHP_JSONP_Response
* Last modified: 14 08 2014
* Copyright (C): 2013 IT-radionica.com, All Rights Reserved
*
* GNU General Public License (Version 2, June 1991)
*
* This program is free software; you can redistribute
* it and/or modify it under the terms of the GNU
* General Public License as published by the Free
* Software Foundation; either version 2 of the License,
* or (at your option) any later version.
*
* This program is distributed in the hope that it will
* be useful, but WITHOUT ANY WARRANTY; without even the
* implied warranty of MERCHANTABILITY or FITNESS FOR A
* PARTICULAR PURPOSE. See the GNU General Public License
* for more details.
* 
* Description:
* 
* JSONP or "JSON with padding" is a communication technique 
* used in JavaScript programs which run in Web browsers. 
* It provides a method to request data from a server in a different domain, 
* something prohibited by typical web browsers because of the same origin policy.
* Read more on: 
* http://en.wikipedia.org/wiki/JSONP
*
* Example:
* 
* // JSON encode data
* $json_encoded = JSONP::encode(array('foo', 'bar'));
* 
* // JSONP string as returned value
* $jsonp_encoded = JSONP::output(array('foo', 'bar'), FALSE);
* 
* // Print output
* JSONP::output(array('foo', 'bar'));
*
* // Pprint output without headers
* JSONP::output(array('foo', 'bar'), TRUE, FALSE);
*
******************************************************************/

final class JSONP {
	
	/** Ignore errors
	 * @var boolean
	 */
	public static $ignoreErrors = TRUE;
	
	/** Javascript callback function name
	 * @var string
	 */
	public static $paramCallback = 'callback';
	
	/** Javascript assinged variable name
	 * @var string
	 */
	public static $paramAssign = 'assing';
	
	/** Javascript callback REGEX
	 * @var string
	 */
	private static $cbRegex = '/^[\p{L}\p{Nl}$_][\p{L}\p{Nl}$\p{Mn}\p{Mc}\p{Nd}\p{Pc}\.]*(?<!\.)$/i';

	/** JSON encode
	 * @param mixed $data
	 * @return JSON formated string
	 */
	public static function encode($data) {
		// Define json_encode for PHP < 5.2
		if (!function_exists('json_encode')) {
			function json_encode($data) {
				switch ($type = gettype($data)) {
					case 'NULL': 
						return 'null';
					case 'boolean': 
						return ($data ? 'true' : 'false');
					case 'integer':
					case 'double':
					case 'float': 
						return $data;
					case 'string': 
						return '"' . addslashes($data) . '"';
					case 'object': 
						return json_encode(get_object_vars($data));
					case 'array':
						$output_index_count = 0;
						$output_indexed = $output_associative = array();
						foreach ($data as $key => $value) {
							$output_indexed[] = json_encode($value);
							$output_associative[] = json_encode($key) . ':' . json_encode($value);
							if ($output_index_count !== NULL && $output_index_count++ !== $key) {
								$output_index_count = NULL;
							}
						}
						return ($output_index_count !== NULL) ? '[' . implode(',', $output_indexed) . ']' : '{' . implode(',', $output_associative) . '}';
					default:
						return NULL;
				}
			}
		}
		// Return JSON formated string
		return json_encode($data);
	}
	
	/** JSON-P encode and print with headers
	 * @param mixed $data
	 * @param boolean $print
	 * @param boolean $header
	 * @return JSON-P formated string
	 */
	 public static function output($data, $print = TRUE, $headers = TRUE) {
	 	$jsonp = TRUE;
		$return = NULL;
		if(isset($_GET[self::$paramCallback]) && !empty($_GET[self::$paramCallback])) {
			// Callback function
			if (@preg_match(self::$cbRegex, $_GET[self::$paramCallback], $callback)) {
				$return = $callback[0]. '(' . self::encode($data) . ');';
			} else if (!self::$ignoreErrors) {
				throw new Exception('Invalid callback function name.');
			}
		} else {
			if (isset($_GET[self::$paramAssign]) && !empty($_GET[self::$paramAssign])) {
				// Assign to variable
				if (@preg_match(self::$cbRegex, $_GET[self::$paramAssign], $assign)) {
					$return = 'var ' . $assign[0] . ' = ' . self::encode($data) . ';';
				} else if (!self::$ignoreErrors) {
					throw new Exception('Invalid assign variable name.');
				}
			} else {
				// Clean JSON
				$jsonp = FALSE;
				$return = self::encode($data);
			}
		}
		
		if ($print) {
			// Print headers
			if ($headers) {
				header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
				header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
				header("Cache-Control: no-store, no-cache, must-revalidate");
				header("Cache-Control: post-check=0, pre-check=0", FALSE);
				header("Pragma: no-cache");
				header("Content-Type: application/" . ($jsonp ? "javascript" : "json"));
			}
			// Print output
			echo $return;
		} else {
			// Return JSON-P formated string
			return $return;
		}
	 }
}