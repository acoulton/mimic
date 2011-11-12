<?php

/**
 * Handles JSON responses - see [Mimic_Response_Formatter]
 *
 * @package    Mimic
 * @category   Response Formatters
 * @author     Andrew Coulton
 * @copyright  (c) 2011 Ingenerator
 * @license    http://kohanaframework.org/license
 */
class Mimic_Response_Formatter_JSON extends Mimic_Response_Formatter
{

	protected $_extension = '.json';

	/**
	 * Converts JSON to a "pretty" format prior to saving, for ease of editing.
	 * @param string $path
	 * @param string $file_prefix
	 * @param string $content
	 * @return string
	 */
	public function put_contents($path, $file_prefix, $content)
	{		
		if ($json_content = json_decode($content))
		{
			$content = $this->json_readable_encode($json_content);
		}
		return parent::put_contents($path, $file_prefix, $content);
	}

	/**
	 * Replacement for json_encode to implement pretty formatting of data prior to
	 * PHP 5.4 adoption
	 * 
	 * Adapted from a solution on php.net as escaping of values is more reliable with
	 * native json_encode
	 * 
	 * @author bohwaz
	 * @link http://www.php.net/manual/en/function.json-encode.php#102091
	 * @param mixed $in Data to encode
	 * @param int $indent Depth of indenting to use
	 * @param boolean $from_array Whether or not from an array
	 * @return string
	 */
	public function json_readable_encode($in, $indent = 0, $from_array = FALSE)
	{		
		$out = '';

		foreach ($in as $key => $value)
		{
			$out .= str_repeat("\t", $indent + 1);
			$out .= json_encode((string) $key).": ";

			if (is_object($value) OR is_array($value))
			{
				$out .= "\n";
				$out .= $this->json_readable_encode($value, $indent + 1);
			}
			else
			{
				// Don't escape forward slashes
				$out .= str_replace('\/', '/', json_encode($value));
			}

			$out .= ",\n";
		}

		if ( ! empty($out))
		{
			$out = substr($out, 0, -2);
		}

		$out = str_repeat("\t", $indent)."{\n".$out;
		$out .= "\n".str_repeat("\t", $indent)."}";		
		return $out;
	}

}
