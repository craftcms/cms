<?php
namespace Blocks;

/**
 *
 */
class EmailMessage
{
	private $_from;
	private $_replyTo;
	private $_to = array();
	private $_cc = array();
	private $_bcc = array();
	private $_subject;
	private $_body;
	private $_altBody;
	private $_isHtml;

	/**
	 * @param EmailAddress $from
	 * @param array                $to
	 *
	 * @throws Exception
	 */
	function __construct(EmailAddress $from, array $to)
	{
		if (empty($to))
			throw new Exception('You must specify an email address to send to.');

		if (StringHelper::isNullOrEmpty($from->getEmailAddress()))
			throw new Exception('You must specify a "from" email address.');

		$this->_from = $from;
		$this->_replyTo = $from;
		$this->_to = $to;
	}

	/**
	 * @return mixed
	 */
	public function getBody()
	{
		return $this->_body;
	}

	/**
	 * @param $body
	 */
	public function setBody($body)
	{
		$this->_body = $body;
	}

	/**
	 * @return mixed
	 */
	public function getAltBody()
	{
		return $this->_altBody;
	}

	/**
	 * @param $altBody
	 */
	public function setAltBody($altBody)
	{
		$this->_altBody = $altBody;
	}

	/**
	 * @return EmailAddress
	 */
	public function getFrom()
	{
		return $this->_from;
	}

	/**
	 * @return array
	 */
	public function getTo()
	{
		return $this->_to;
	}

	/**
	 * @return EmailAddress
	 */
	public function getReplyTo()
	{
		return $this->_replyTo;
	}

	/**
	 * @param $replyTo
	 */
	public function setReplyTo($replyTo)
	{
		$this->_replyTo = $replyTo;
	}

	/**
	 * @return array
	 */
	public function getCc()
	{
		return $this->_cc;
	}

	/**
	 * @param $cc
	 */
	public function setCc($cc)
	{
		$this->_cc = $cc;
	}

	/**
	 * @return array
	 */
	public function getBcc()
	{
		return $this->_bcc;
	}

	/**
	 * @param $bcc
	 */
	public function setBcc($bcc)
	{
		$this->_bcc = $bcc;
	}

	/**
	 * @return mixed
	 */
	public function getSubject()
	{
		return $this->_subject;
	}

	/**
	 * @param $subject
	 */
	public function setSubject($subject)
	{
		$this->_subject = $subject;
	}

	/**
	 * @return mixed
	 */
	public function getIsHtml()
	{
		return $this->_isHtml;
	}

	/**
	 * @param $isHtml
	 */
	public function setIsHtml($isHtml)
	{
		$this->_isHtml = $isHtml;
	}
}
