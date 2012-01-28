<?php
/**
 *
 */
class bEmailMessage
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
	 * @param       $from
	 * @param array $to
	 */
	function __construct(bEmailAddress $from, array $to)
	{
		if (empty($to))
			throw new bException('You must specify an email address to send to.');

		if (bStringHelper::isNullOrEmpty($from->getEmailAddress()))
			throw new bException('You must specify a "from" email address.');

		$this->_from = $from;
		$this->_replyTo = $from;
		$this->_to = $to;
	}

	public function getBody()
	{
		return $this->_body;
	}

	public function setBody($body)
	{
		$this->_body = $body;
	}

	public function getAltBody()
	{
		return $this->_altBody;
	}

	public function setAltBody($altBody)
	{
		$this->_altBody = $altBody;
	}

	public function getFrom()
	{
		return $this->_from;
	}

	public function getTo()
	{
		return $this->_to;
	}

	public function getReplyTo()
	{
		return $this->_replyTo;
	}

	public function setReplyTo($replyTo)
	{
		$this->_replyTo = $replyTo;
	}

	public function getCc()
	{
		return $this->_cc;
	}

	public function setCc($cc)
	{
		$this->_cc = $cc;
	}

	public function getBcc()
	{
		return $this->_bcc;
	}

	public function setBcc($bcc)
	{
		$this->_bcc = $bcc;
	}

	public function getSubject()
	{
		return $this->_subject;
	}

	public function setSubject($subject)
	{
		$this->_subject = $subject;
	}

	public function getIsHtml()
	{
		return $this->_isHtml;
	}

	public function setIsHtml($isHtml)
	{
		$this->_isHtml = $isHtml;
	}
}
