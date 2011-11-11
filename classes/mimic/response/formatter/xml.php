<?php

/**
 * Handles XML responses - see [Mimic_Response_Formatter]
 *
 * @package    Mimic
 * @category   Response Formatters
 * @author     Andrew Coulton
 * @copyright  (c) 2011 Ingenerator
 * @license    http://kohanaframework.org/license
 */
class Mimic_Response_Formatter_XML extends Mimic_Response_Formatter
{
	protected $_extension = '.xml';

	/**
	 * Converts XML to a "pretty" format prior to saving, for ease of editing.
	 * @param string $path
	 * @param string $file_prefix
	 * @param string $content
	 * @return string
	 */
	public function put_contents($path, $file_prefix, $content)
	{
		$xml_doc = new DOMDocument;
		if ($xml_doc->loadXML($content, LIBXML_NOERROR | LIBXML_NOWARNING))
		{
			$xml_doc->formatOutput = TRUE;
			$content = $xml_doc->saveXML();
		}
		return parent::put_contents($path, $file_prefix, $content);
	}

}
