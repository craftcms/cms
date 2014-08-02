<?php
namespace Craft;

/**
 * This model represents an Asset Operation Response.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.models
 * @since     1.0
 */
class AssetOperationResponseModel extends BaseModel
{
	const StatusError = 'error';
	const StatusSuccess = 'success';
	const StatusConflict = 'conflict';

	/**
	 * @var array
	 */
	private $_data = array();

	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'status'		=> array(AttributeType::Enum, 'values' => array(self::StatusError, self::StatusSuccess, self::StatusConflict)),
			'errorMessage'	=> AttributeType::String
		);
	}

	/**
	 * Set an error message.
	 *
	 * @param $message
	 *
	 * @return AssetOperationResponseModel
	 */
	public function setError($message)
	{
		$this->setAttribute('errorMessage', $message);
		$this->setAttribute('status', self::StatusError);
		return $this;
	}

	/**
	 * Set status to success.
	 *
	 * @return AssetOperationResponseModel
	 */
	public function setSuccess()
	{
		$this->setAttribute('status', self::StatusSuccess);
		return $this;
	}

	/**
	 * Set prompt data array.
	 *
	 * @param $promptData
	 *
	 * @return AssetOperationResponseModel
	 */
	public function setPrompt($promptData)
	{
		$this->setAttribute('status', self::StatusConflict);
		$this->setDataItem('prompt', $promptData);
		return $this;
	}

	/**
	 * Set a data item.
	 *
	 * @param $name
	 * @param $value
	 *
	 * @return AssetOperationResponseModel
	 */
	public function setDataItem($name, $value)
	{
		$this->_data[$name] = $value;
		return $this;
	}

	/**
	 * Get a data item.
	 *
	 * @param $name
	 *
	 * @return void
	 */
	public function getDataItem($name)
	{
		if (isset($this->_data[$name]))
		{
			return $this->_data[$name];
		}

		return null;
	}

	/**
	 * Delete a data item.
	 *
	 * @param $name
	 *
	 * @return void
	 */
	public function deleteDataItem($name)
	{
		if (isset($this->_data[$name]))
		{
			unset($this->_data[$name]);
		}
	}

	/**
	 * @return bool
	 */
	public function isConflict()
	{
		return $this->getAttribute('status') == self::StatusConflict;
	}

	/**
	 * @return bool
	 */
	public function isSuccess()
	{
		return $this->getAttribute('status') == self::StatusSuccess;
	}

	/**
	 * @return bool
	 */
	public function isError()
	{
		return $this->getAttribute('status') == self::StatusError;
	}

	/**
	 * Return a response array ready to be transported.
	 *
	 * @return array
	 */
	public function getResponseData()
	{
		switch ($this->getAttribute('status'))
		{
			case self::StatusError:
			{
				return array_merge($this->_data, array(self::StatusError => $this->getAttribute('errorMessage')));
			}

			case self::StatusSuccess:
			{
				return array_merge($this->_data, array(self::StatusSuccess => true));
			}

			case self::StatusConflict:
			{
				return $this->_data;
			}

		}

		return array();
	}
}
