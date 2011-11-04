<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Mimic_Response_Formatter classes store response bodies to disk according
 * to the content-type of the response.
 * 
 * By default, Mimic_Response_Formatter_Generic simply outputs the response
 * as received. Custom response formatters can be used to convert a minified
 * response (JSON, for example) to something more easily human readable and
 * therefore easier to edit.
 * 
 * The output of the response formatter must still be valid for the content type 
 * 
 * @package    Mimic 
 * @category   Response Formatters
 * @author     Andrew Coulton
 * @copyright  (c) 2011 Ingenerator
 * @license    http://kohanaframework.org/license 
 */
class Mimic_Response_Formatter
{
	protected $_extension = '.body';
	
	/**
	 * Outputs a response body to disk in the given file.
	 * 
	 * The formatter is responsible for choosing a suitable file extension.
	 * 
	 * @param string $path
	 * @param string $file_prefix
	 * @param string $content 
	 */
	public function put_contents($path, $file_prefix ,$content)
	{
		$file = $file_prefix.$this->_extension;
		return $file;
	}
	
}
