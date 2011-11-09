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
	 * Escapes a JSON value
	 * @author bohwaz
	 * @see http://www.php.net/manual/en/function.json-encode.php#102091
	 * @param string $str The value to escape
	 * @return string
	 */
	protected function _json_escape($str)
	{
		return preg_replace("!([\b\t\n\r\f\"\\'])!", "\\\\\\1", $str);
	}

	/**
	 * Replacement for json_encode to implement pretty formatting of data prior to
	 * PHP 5.4 adoption
	 * @author bohwaz
	 * @see http://www.php.net/manual/en/function.json-encode.php#102091
	 * @param mixed $in Data to encode
	 * @param int $indent Depth of indenting to use
	 * @param boolean $from_array Whether or not from an array
	 * @return string
	 */
	public function json_readable_encode($in, $indent = 0, $from_array = false)
	{
		$out = '';

		foreach ($in as $key => $value)
		{
			$out .= str_repeat("\t", $indent + 1);
			$out .= "\"".$this->_json_escape((string) $key)."\": ";

			if (is_object($value) || is_array($value))
			{
				$out .= "\n";
				$out .= $this->json_readable_encode($value, $indent + 1);
			}
			elseif (is_bool($value))
			{
				$out .= $value ? 'true' : 'false';
			}
			elseif (is_null($value))
			{
				$out .= 'null';
			}
			elseif (is_string($value))
			{
				$out .= "\"".$this->_json_escape($value)."\"";
			}
			else
			{
				$out .= $value;
			}

			$out .= ",\n";
		}

		if (!empty($out))
		{
			$out = substr($out, 0, -2);
		}

		$out = str_repeat("\t", $indent)."{\n".$out;
		$out .= "\n".str_repeat("\t", $indent)."}";

		return $out;
	}

}
