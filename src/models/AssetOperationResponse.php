<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;
use craft\app\base\Model;
use craft\app\enums\AttributeType;

/**
 * This model represents an AssetOperationResponse.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetOperationResponse extends Model
{
	// Constants
	// =========================================================================

	const StatusError = 'error';
	const StatusSuccess = 'success';
	const StatusConflict = 'conflict';

	// Properties
	// =========================================================================

	/**
	 * @var string Status
	 */
	public $status;

	/**
	 * @var string Error message
	 */
	public $errorMessage;


	/**
	 * @var array
	 */
	private $_data = [];

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['status'], 'in', 'range' => ['error', 'success', 'conflict']],
			[['status', 'errorMessage'], 'safe', 'on' => 'search'],
		];
	}

	/**
	 * Set an error message.
	 *
	 * @param $message
	 *
	 * @return AssetOperationResponse
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
	 * @return AssetOperationResponse
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
	 * @return AssetOperationResponse
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
	 * @return AssetOperationResponse
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
				return array_merge($this->_data, [static::StatusError => $this->getAttribute('errorMessage')]);
			}

			case static::StatusSuccess:
			{
				return array_merge($this->_data, [static::StatusSuccess => true]);
			}

			case static::StatusConflict:
			{
				return $this->_data;
			}

		}

		return [];
	}
}
