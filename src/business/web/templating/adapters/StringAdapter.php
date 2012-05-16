<?php
namespace Blocks;

/**
 *
 */
class StringAdapter extends Adapter
{
	/**
	 * @return int
	 */
	public function length()
	{
		return strlen($this->_var);
	}

	/**
	 * @param $index
	 * @return string
	 */
	public function charAt($index)
	{
		if ($this->_var)
			return $this->_var[$index];
		else
			return '';
	}

	/**
	 * @return string
	 */
	public function uppercase()
	{
		return strtoupper($this->_var);
	}

	/**
	 * @return string
	 */
	public function lowercase()
	{
		return strtolower($this->_var);
	}

	/**
	 * @return string
	 */
	public function uppercaseFirst()
	{
		return ucfirst($this->_var);
	}

	/**
	 * @return string
	 */
	public function nl2br()
	{
		return nl2br($this->_var);
	}

	/**
	 * @return mixed
	 */
	public function encode()
	{
		return HtmlHelper::encode($this->_var);
	}

	/**
	 * @return mixed
	 */
	public function decode()
	{
		return HtmlHelper::decode($this->_var);
	}

	/**
	 * @return array
	 */
	public function chars()
	{
		if (strlen($this->_var))
		{
			return str_split($this->_var);
		}

		return array();
	}

	/**
	 * @return mixed
	 */
	public function wordNumbers()
	{
		return preg_replace_callback('/(^|\s)(\d+)($|\s)/', array(&$this, 'replaceNumberWithWord'), $this->_var);
	}

	/**
	 * Adds an 's to the end of a string
	 * @return string
	 */
	public function possessive()
	{
		if (substr($this->_var, -1) == 's')
			return $this->_var.'&rsquo;';
		else
			return $this->_var.'&rsquo;s';
	}

	/**
	 * @param $m
	 * @return string
	 */
	protected function replaceNumberWithWord($m)
	{
		return $m[1].NumberHelper::word($m[2]).$m[3];
	}

	/**
	 * Split
	 * @param string $delimiter
	 * @return array
	 */
	public function split($delimiter = ',')
	{
		return array_map('trim', explode($delimiter, $this->_var));
	}

	/**
	 * @param $search
	 * @return bool
	 */
	public function contains($search)
	{
		return strpos($this->_var, $search) !== false;
	}

	/**
	 * @param $search
	 * @return bool
	 */
	public function doesnotcontain($search)
	{
		return !$this->contains($search);
	}
}
