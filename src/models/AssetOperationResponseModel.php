<?php
namespace Craft;

/**
 * This model represents an Asset Operation Response.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     1.0
 */
class AssetOperationResponseModel extends BaseModel
{
	// Constants
	// =========================================================================

	const StatusError = 'error';
	const StatusSuccess = 'success';
	const StatusConflict = 'conflict';

	// Properties
	// =========================================================================

	/**
	 * @var array
	 */
	private $_data = array();

	// Public Methods
	// =========================================================================

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
		$this->setAttribute('status', static::StatusError);

		return $this;
	}

	/**
	 * Set status to success.
	 *
	 * @return AssetOperationResponseModel
	 */
	public function setSuccess()
	{
		$this->setAttribute('status', static::StatusSuccess);
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
		$this->setAttribute('status', static::StatusConflict);
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
	 * @return mixed
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
	 * @return null
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
		return $this->getAttribute('status') == static::StatusConflict;
	}

	/**
	 * @return bool
	 */
	public function isSuccess()
	{
		return $this->getAttribute('status') == static::StatusSuccess;
	}

	/**
	 * @return bool
	 */
	public function isError()
	{
		return $this->getAttribute('status') == static::StatusError;
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
			case static::StatusError:
			{
				return array_merge($this->_data, array(static::StatusError => $this->getAttribute('errorMessage')));
			}

			case static::StatusSuccess:
			{
				return array_merge($this->_data, array(static::StatusSuccess => true));
			}

			case static::StatusConflict:
			{
				return $this->_data;
			}

		}

		return array();
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'status'		=> array(AttributeType::Enum, 'values' => array(static::StatusError, static::StatusSuccess, static::StatusConflict)),
			'errorMessage'	=> AttributeType::String
		);
	}
}
