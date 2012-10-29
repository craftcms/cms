<?php
namespace Blocks;

/**
 * TODO: this file might be removed - it's here for now as a scaffolding in case we decide to go this way
 */
class AssetOperationResponseModel extends BaseModel
{

	const StatusError = 'error';
	const StatusSuccess = 'success';
	const StatusConflict = 'conflict';


	/**
	 * Returns an \StdClass object of response information only, ready to be sent over JSON
	 * @return \StdClass
	 */
	public function getResponseForTransport()
	{
		$output = (object) array(
			'status' 		=> $this->getStatus(),
			'errorMessage' 	=> $this->getErrorMessage(),
			'data' 			=> JsonHelper::decode($this->getResponseData)
		);

		return $output;
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'status'		=> array(AttributeType::Enum, 'values' => array(self::StatusError, self::StatusSuccess, self::StatusConflict)),
			'errorMessage'	=> AttributeType::String,
			'responseData'	=> AttributeType::String
		);
	}

	/**
	 * @return mixed
	 */
	public function getStatus()
	{
		return $this->getAttribute('status');
	}

	/**
	 * String containing a human readable message for status
	 * @return mixed
	 */
	public function getErrorMessage()
	{
		return $this->getAttribute('errorMessage');
	}

	/**
	 * Response data. An StdClass object
	 * @return \StdClass
	 */
	public function getResponseData()
	{
		return JsonHelper::decode($this->getAttribute('responseData'));
	}

	/**
	 * Set an error
	 * @param $message
	 */
	public function setError($message)
	{
		$this->setAttribute('status', self::StatusError);
		$this->setAttribute('errorMessage', $message);
	}

	/**
	 * Set response data
	 * @param string $status
	 */
	public function setResponse($status)
	{
		$this->setAttribute('status', $status);
	}

	/**
	 * Add a response data item
	 * @param $item
	 * @param $value
	 */
	public function setResponseDataItem($item, $value)
	{
		$dataObject = JsonHelper::decode($this->getResponseData());
		if (!is_object($dataObject))
		{
			$dataObject = new \StdClass;
		}
		$dataObject->{$item} = $value;
		$this->setAttribute('responseData', $dataObject);
	}
}
