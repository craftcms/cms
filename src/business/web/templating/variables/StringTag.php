<?php
namespace Blocks;

/**
 *
 */
class StringTag extends VarTag
{
	/**
	 * @return int
	 */
	public function length()
	{
		return strlen($this->_var);
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
	 * @param $m
	 * @return string
	 */
	protected function replaceNumberWithWord($m)
	{
		return $m[1].NumberHelper::word($m[2]).$m[3];
	}

	/**
	 * Split
	 *
	 * @param string $delimiter
	 * @return array
	 */
	public function split($delimiter = ',')
	{
		return array_map('trim', explode($delimiter, $this->_var));
	}

}
