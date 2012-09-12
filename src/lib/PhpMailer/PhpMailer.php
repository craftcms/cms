<?php

/*
.---------------------------------------------------------------------------.
|   Software: PhpMailer - PHP email class                                   |
|   Version: 5.2.1                                                          |
|   Site: https://code.google.com/a/apache-extras.org/p/phpmailer/          |
| ------------------------------------------------------------------------- |
|     Admin: Jim Jagielski (project admininistrator)                        |
|   Authors: Andy Prevost (codeworxtech) codeworxtech@users.sourceforge.net |
|          : Marcus Bointon (coolbru) coolbru@users.sourceforge.net         |
|          : Jim Jagielski (jimjag) jimjag@gmail.com                        |
|   Founder: Brent R. Matzelle (original founder)                           |
| Copyright (c) 2010-2012, Jim Jagielski. All Rights Reserved.              |
| Copyright (c) 2004-2009, Andy Prevost. All Rights Reserved.               |
| Copyright (c) 2001-2003, Brent R. Matzelle                                |
| ------------------------------------------------------------------------- |
|   License: Distributed under the Lesser General Public License (LGPL)     |
|            http://www.gnu.org/copyleft/lesser.html                        |
| This program is distributed in the hope that it will be useful - WITHOUT  |
| ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or     |
| FITNESS FOR A PARTICULAR PURPOSE.                                         |
'---------------------------------------------------------------------------'
*/

/**
 * PhpMailer - PHP email transport class
 * NOTE: Requires PHP version 5 or later
 * @package PhpMailer
 * @author Andy Prevost
 * @author Marcus Bointon
 * @author Jim Jagielski
 * @copyright 2010 - 2012 Jim Jagielski
 * @copyright 2004 - 2009 Andy Prevost
 * @version $Id: PhpMailer.php 450 2010-06-23 16:46:33Z coolbru $
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

if (version_compare(PHP_VERSION, '5.0.0', '<') ) exit("Sorry, this version of PhpMailer will only run on PHP version 5 or greater!\n");

/**
 *
 */
class PhpMailer
{
	/**
	 * Email priority (1 = High, 3 = Normal, 5 = low).
	 * @var int
	 */
	public $priority = 3;

	/**
	 * Sets the CharSet of the message.
	 * @var string
	 */
	public $charSet = 'iso-8859-1';

	/**
	 * Sets the Content-type of the message.
	 * @var string
	 */
	public $contentType = 'text/plain';

	/**
	 * Sets the Encoding of the message. Options for this are "8bit", "7bit", "binary", "base64", and "quoted-printable".
	 * @var string
	 */
	public $encoding = '8bit';

	/**
	 * Holds the most recent mailer error message.
	 * @var string
	 */
	public $errorInfo = '';

	/**
	 * Sets the From email address for the message.
	 * @var string
	 */
	public $from = 'root@localhost';

	/**
	 * Sets the From name of the message.
	 * @var string
	 */
	public $fromName = 'Root User';

	/**
	 * Sets the Sender email (Return-Path) of the message.  If not empty, will be sent via -f to sendmail or as 'MAIL FROM' in smtp mode.
	 * @var string
	 */
	public $sender = '';

	/**
	 * Sets the Subject of the message.
	 * @var string
	 */
	public $subject = '';

	/**
	 * Sets the body of the message.	This can be either an HTML or text body.
	 * If HTML then run IsHTML(true).
	 * @var string
	 */
	public $body = '';

	/**
	 * Sets the text-only body of the message.  This automatically sets the email to multipart/alternative.  This body can be read by mail
	 * clients that do not have HTML email capability such as mutt. Clients that can read HTML will view the normal Body.
	 * @var string
	 */
	public $altBody = '';

	/**
	 * Stores the complete compiled MIME message body.
	 * @var string
	 * @access protected
	 */
	protected $mimeBody = '';

	/**
	 * Stores the complete compiled MIME message headers.
	 * @var string
	 * @access protected
	 */
	protected $mimeHeader = '';

	/**
	 * Stores the complete sent MIME message (Body and Headers)
	 * @var string
	 * @access protected
	 */
	protected $sentMimeMessage = '';

	/**
	 * Sets word wrapping on the body of the message to a given number of
	 * characters.
	 * @var int
	 */
	public $wordWrap = 0;

	/**
	 * Method to send mail: ("mail", "sendmail", or "smtp").
	 * @var string
	 */
	public $mailer = 'mail';

	/**
	 * Sets the path of the sendmail program.
	 * @var string
	 */
	public $sendMail = '/usr/sbin/sendmail';

	/**
	 * Path to PhpMailer plugins.  Useful if the Smtp class is in a different directory than the PHP include path.
	 * @var string
	 */
	public $pluginDir = '';

	/**
	 * Sets the email address that a reading confirmation will be sent.
	 * @var string
	 */
	public $confirmReadingTo = '';

	/**
	 * Sets the hostname to use in Message-Id and Received headers and as default HELO string. If empty, the value returned
	 * by SERVER_NAME is used or 'localhost.localdomain'.
	 * @var string
	 */
	public $hostname = '';

	/**
	 * Sets the message ID to be used in the Message-Id header.
	 * If empty, a unique id will be generated.
	 * @var string
	 */
	public $messageID = '';

	/////////////////////////////////////////////////
	// Properties for Smtp
	/////////////////////////////////////////////////

	/**
	 * Sets the Smtp hosts.  All hosts must be separated by a semicolon.  You can also specify a different port
	 * for each host by using this format: [hostname:port] (e.g. "smtp1.example.com:25;smtp2.example.com").
	 * Hosts will be tried in order.
	 * @var string
	 */
	public $host = 'localhost';

	/**
	 * Sets the default Smtp server port.
	 * @var int
	 */
	public $port = 25;

	/**
	 * Sets the SMTP HELO of the message (default is $hostname).
	 * @var string
	 */
	public $helo = '';

	/**
	 * Sets connection prefix.  Options are "", "ssl" or "tls"
	 * @var string
	 */
	public $smtpSecure = '';

	/**
	 * Sets Smtp authentication. Utilizes the userName and password variables.
	 * @var bool
	 */
	public $smtpAuth = false;

	/**
	 * Sets Smtp username.
	 * @var string
	 */
	public $userName = '';

	/**
	 * Sets Smtp password.
	 * @var string
	 */
	public $password = '';

	/**
	 * Sets the Smtp server timeout in seconds.  This function will not work with the win32 version.
	 * @var int
	 */
	public $timeout = 10;

	/**
	 * Sets Smtp class debugging on or off.
	 * @var bool
	 */
	public $smtpDebug = false;

	/**
	 * Prevents the Smtp connection from being closed after each mail
	 * sending.	If this is set to true then to close the connection
	 * requires an explicit call to SmtpClose().
	 * @var bool
	*/
	public $smtpKeepAlive = false;

	/**
	 * Provides the ability to have the TO field process individual emails, instead of sending to entire TO addresses
	 * @var bool
	 */
	public $singleTo = false;

	/**
	 * If singleTo is true, this provides the array to hold the email addresses
	 * @var bool
	 */
	public $singleToArray = array();

 /**
	 * Provides the ability to change the line ending
	 * @var string
	 */
	public $lineEnding = "\n";

	/**
	 * Used with DKIM DNS Resource Record
	 * @var string
	 */
	public $dkimSelector = 'phpmailer';

	/**
	 * Used with DKIM DNS Resource Record
	 * optional, in format of email address 'you@yourdomain.com'
	 * @var string
	 */
	public $dkimIdentity = '';

	/**
	 * Used with DKIM DNS Resource Record
	 * @var string
	 */
	public $dkimPassPhrase = '';

	/**
	 * Used with DKIM DNS Resource Record
	 * optional, in format of email address 'you@yourdomain.com'
	 * @var string
	 */
	public $dkimDomain = '';

	/**
	 * Used with DKIM DNS Resource Record
	 * optional, in format of email address 'you@yourdomain.com'
	 * @var string
	 */
	public $dkimPrivate = '';

	/**
	 * Callback Action function name
	 * the function that handles the result of the send email action. Parameters:
	 *   bool    $result       result of the send action
	 *   string  $to           email address of the recipient
	 *   string  $cc           cc email addresses
	 *   string  $bcc          bcc email addresses
	 *   string  $subject      the subject
	 *   string  $body         the email body
	 * @var string
	 */
	public $actionFunction = ''; //'callbackAction';

	/**
	 * Sets the PhpMailer Version number
	 * @var string
	 */
	public $version = '5.2.1';

	/**
	 * What to use in the X-Mailer header
	 * @var string
	 */
	public $xMailer = '';

	/////////////////////////////////////////////////
	// Private and protected properties
	/////////////////////////////////////////////////

	protected $smtp = NULL;
	protected $to = array();
	protected $cc = array();
	protected $bcc = array();
	protected $replyTo = array();
	protected $allRecipients = array();
	protected $attachment = array();
	protected $customHeader = array();
	protected $messageType = '';
	protected $boundary = array();
	protected $language = array();
	protected $errorCount = 0;
	protected $signCertFile = '';
	protected $signKeyFile = '';
	protected $signKeyPass = '';
	protected $exceptions = false;

	/////////////////////////////////////////////////
	// Constants
	/////////////////////////////////////////////////

	const STOP_MESSAGE	= 0; // message only, continue processing
	const STOP_CONTINUE = 1; // message?, likely ok to continue processing
	const STOP_CRITICAL = 2; // message, plus full stop, critical error reached

	/**
	 * Constructor
	 * @param boolean $exceptions Should we throw external exceptions?
	 */
	function __construct($exceptions = false)
	{
		$this->exceptions = ($exceptions == true);
	}

	/**
	 * Sets message type to HTML.
	 * @param bool $isHtml
	 * @return void
	 */
	public function isHtml($isHtml = true)
	{
		if ($isHtml)
			$this->contentType = 'text/html';
		else
			$this->contentType = 'text/plain';
	}

	/**
	 * Sets Mailer to send message using Smtp.
	 * @return void
	 */
	public function isSmtp()
	{
		$this->mailer = 'smtp';
	}

	/**
	 * Sets Mailer to send message using PHP mail() function.
	 * @return void
	 */
	public function isMail()
	{
		$this->mailer = 'mail';
	}

	/**
	 * Sets Mailer to send message using the sendmail program.
	 * @return void
	 */
	public function isSendmail()
	{
		if (!stristr(ini_get('sendmail_path'), 'sendmail'))
			$this->sendMail = '/var/qmail/bin/sendmail';

		$this->mailer = 'sendmail';
	}

	/**
	 * Sets Mailer to send message using the qmail MTA.
	 * @return void
	 */
	public function isQmail()
	{
		if (stristr(ini_get('sendmail_path'), 'qmail'))
			$this->sendMail = '/var/qmail/bin/sendmail';

		$this->mailer = 'sendmail';
	}

	/**
	 * Adds a "To" address.
	 * @param string $address
	 * @param string $name
	 * @return boolean true on success, false if address already used
	 */
	public function addAddress($address, $name = '')
	{
		return $this->addAnAddress('to', $address, $name);
	}

	/**
	 * Adds a "Cc" address.
	 * Note: this function works with the Smtp mailer on win32, not with the "mail" mailer.
	 * @param string $address
	 * @param string $name
	 * @return boolean true on success, false if address already used
	 */
	public function addCc($address, $name = '')
	{
		return $this->addAnAddress('cc', $address, $name);
	}

	/**
	 * Adds a "Bcc" address.
	 * Note: this function works with the Smtp mailer on win32, not with the "mail" mailer.
	 * @param string $address
	 * @param string $name
	 * @return boolean true on success, false if address already used
	 */
	public function addBcc($address, $name = '')
	{
		return $this->addAnAddress('bcc', $address, $name);
	}

	/**
	 * Adds a "Reply-to" address.
	 * @param string $address
	 * @param string $name
	 * @return boolean
	 */
	public function addReplyTo($address, $name = '')
	{
		return $this->addAnAddress('Reply-To', $address, $name);
	}

	/**
	 * Adds an address to one of the recipient arrays
	 * Addresses that have been added already return false, but do not throw exceptions
	 * @param string $kind One of 'to', 'cc', 'bcc', 'ReplyTo'
	 * @param string $address The email address to send to
	 * @param string $name
	 * @throws phpMailerException
	 * @return boolean true on success, false if address already used or invalid in some way
	 * @access protected
	 */
	protected function addAnAddress($kind, $address, $name = '')
	{
		if (!preg_match('/^(to|cc|bcc|Reply-To)$/', $kind))
		{
			$this->setError($this->lang('Invalid recipient array').': '.$kind);
			if ($this->exceptions)
				throw new phpMailerException('Invalid recipient array: ' . $kind);

			if ($this->smtpDebug)
				echo $this->lang('Invalid recipient array').': '.$kind;

			return false;
		}

		$address = trim($address);
		$name = trim(preg_replace('/[\r\n]+/', '', $name)); //Strip breaks and trim

		if (!self::validateAddress($address))
		{
			$this->setError($this->lang('invalid_address').': '. $address);

			if ($this->exceptions)
				throw new phpMailerException($this->lang('invalid_address').': '.$address);

			if ($this->smtpDebug)
				echo $this->lang('invalid_address').': '.$address;

			return false;
		}

		if ($kind != 'Reply-To')
		{
			if (!isset($this->allRecipients[strtolower($address)]))
			{
				array_push($this->$kind, array($address, $name));
				$this->allRecipients[strtolower($address)] = true;
				return true;
			}
		}
		else
		{
			if (!array_key_exists(strtolower($address), $this->replyTo))
			{
				$this->replyTo[strtolower($address)] = array($address, $name);
				return true;
			}
		}

		return false;
	}

	/**
	 * Set the From and FromName properties
	 * @param string $address
	 * @param string $name
	 * @param int    $auto
	 * @throws phpMailerException
	 * @return boolean
	 */
	public function setFrom($address, $name = '', $auto = 1)
	{
		$address = trim($address);
		$name = trim(preg_replace('/[\r\n]+/', '', $name)); //Strip breaks and trim

		if (!self::validateAddress($address))
		{
			$this->setError($this->lang('invalid_address').': '. $address);
			if ($this->exceptions)
				throw new phpMailerException($this->lang('invalid_address').': '.$address);

			if ($this->smtpDebug)
				echo $this->lang('invalid_address').': '.$address;

			return false;
		}

		$this->from = $address;
		$this->fromName = $name;

		if ($auto)
		{
			if (empty($this->replyTo))
				$this->addAnAddress('Reply-To', $address, $name);

		if (empty($this->sender))
			$this->sender = $address;
		}

		return true;
	}

	/**
	 * Check that a string looks roughly like an email address should
	 * Static so it can be used without instantiation
	 * Tries to use PHP built-in validator in the filter extension (from PHP 5.2), falls back to a reasonably competent regex validator
	 * Conforms approximately to RFC2822
	 * @link http://www.hexillion.com/samples/#Regex Original pattern found here
	 * @param string $address The email address to check
	 * @return boolean
	 * @static
	 * @access public
	 */
	public static function validateAddress($address)
	{
		if (function_exists('filter_var')) //Introduced in PHP 5.2
		{
			if (filter_var($address, FILTER_VALIDATE_EMAIL) === false)
				return false;
			else
				return true;
		}
		else
		{
			return preg_match('/^(?:[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+\.)*[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+@(?:(?:(?:[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!\.)){0,61}[a-zA-Z0-9_-]?\.)+[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!$)){0,61}[a-zA-Z0-9_]?)|(?:\[(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\]))$/', $address);
		}
	}

	/**
	 * Creates message and assigns Mailer. If the message is not sent successfully then it returns false.
	 * Use the ErrorInfo variable to view description of the error.
	 * @throws phpMailerException
	 * @return bool
	 */
	public function send()
	{
		try
		{
			if (!$this->preSend())
				return false;

			return $this->postSend();
		}
		catch (phpMailerException $e)
		{
			$this->sentMimeMessage = '';
			$this->setError($e->getMessage());

			if ($this->exceptions)
				throw $e;

		return false;
	}
	}

	/**
	 * @return bool
	 * @throws phpMailerException
	 */
	protected function preSend()
	{
		try
		{
			$mailHeader = "";

			if ((count($this->to) + count($this->cc) + count($this->bcc)) < 1)
				throw new phpMailerException($this->lang('provide_address'), self::STOP_CRITICAL);

			// Set whether the message is multipart/alternative
			if (!empty($this->altBody))
				$this->contentType = 'multipart/alternative';

			$this->errorCount = 0; // reset errors
			$this->setMessageType();

			// Refuse to send an empty message
			if (empty($this->body))
				throw new phpMailerException($this->lang('empty_message'), self::STOP_CRITICAL);

			$this->mimeHeader = $this->createHeader();
			$this->mimeBody = $this->createBody();

			// To capture the complete message when using mail(), create an extra header list which createHeader() doesn't fold in
			if ($this->mailer == 'mail')
			{
				if (count($this->to) > 0)
					$mailHeader .= $this->addrAppend("To", $this->to);
				else
					$mailHeader .= $this->headerLine("To", "undisclosed-recipients:;");

				$mailHeader .= $this->headerLine('Subject', $this->encodeHeader($this->secureHeader(trim($this->subject))));
				// if(count($this->cc) > 0)
					// $mailHeader .= $this->AddrAppend("Cc", $this->cc);
			}

			// digitally sign with DKIM if enabled
			if ($this->dkimDomain && $this->dkimPrivate)
			{
				$headerDKIM = $this->dkimAdd($this->mimeHeader, $this->encodeHeader($this->secureHeader($this->subject)), $this->mimeBody);
				$this->mimeHeader = str_replace("\r\n", "\n", $headerDKIM) . $this->mimeHeader;
			}

			$this->sentMimeMessage = sprintf("%s%s\r\n\r\n%s", $this->mimeHeader, $mailHeader, $this->mimeBody);
			return true;

		}
		catch (phpMailerException $e)
		{
			$this->setError($e->getMessage());

			if ($this->exceptions)
				throw $e;

			return false;
		}
	}

	/**
	 * @return bool
	 * @throws phpMailerException
	 */
	protected function postSend()
	{
		try
		{
			// Choose the mailer and send through it
			switch ($this->mailer)
			{
				case 'sendmail':
					return $this->sendMailSend($this->mimeHeader, $this->mimeBody);

				case 'smtp':
					return $this->smtpSend($this->mimeHeader, $this->mimeBody);

				case 'mail':
					return $this->mailSend($this->mimeHeader, $this->mimeBody);

				default:
					return $this->mailSend($this->mimeHeader, $this->mimeBody);
			}
		}
		catch (phpMailerException $e)
		{
			$this->setError($e->getMessage());

			if ($this->exceptions)
				throw $e;

			if ($this->smtpDebug)
				echo $e->getMessage()."\n";

			return false;
		}
	}

	/**
	 * Sends mail using the sendmail program.
	 * @param string $header The message headers
	 * @param string $body The message body
	 * @throws phpMailerException
	 * @access protected
	 * @return bool
	 */
	protected function sendMailSend($header, $body)
	{
		if ($this->sender != '')
			$sendMail = sprintf("%s -oi -f %s -t", escapeshellcmd($this->sendMail), escapeshellarg($this->sender));
		else
			$sendMail = sprintf("%s -oi -t", escapeshellcmd($this->sendMail));

		if ($this->singleTo === true)
		{
			foreach ($this->singleToArray as $key => $val)
			{
				if(!@$mail = popen($sendMail, 'w'))
					throw new phpMailerException($this->lang('execute') . $this->sendMail, self::STOP_CRITICAL);

				fputs($mail, "To: " . $val . "\n");
				fputs($mail, $header);
				fputs($mail, $body);

				$result = pclose($mail);

				// implement call back function if it exists
				$isSent = ($result == 0) ? 1 : 0;
				$this->doCallback($isSent, $val, $this->cc, $this->bcc, $this->subject, $body);

				if ($result != 0)
					throw new phpMailerException($this->lang('execute') . $this->sendMail, self::STOP_CRITICAL);
			}
		}
		else
		{
			if (!@$mail = popen($sendMail, 'w'))
				throw new phpMailerException($this->lang('execute') . $this->sendMail, self::STOP_CRITICAL);

			fputs($mail, $header);
			fputs($mail, $body);
			$result = pclose($mail);

			// implement call back function if it exists
			$isSent = ($result == 0) ? 1 : 0;
			$this->doCallback($isSent, $this->to, $this->cc, $this->bcc, $this->subject, $body);

			if ($result != 0)
				throw new phpMailerException($this->lang('execute') . $this->sendMail, self::STOP_CRITICAL);
		}

		return true;
	}

	/**
	 * Sends mail using the PHP mail() function.
	 * @param string $header The message headers
	 * @param string $body The message body
	 * @throws phpMailerException
	 * @access protected
	 * @return bool
	 */
	protected function mailSend($header, $body)
	{
		$toArr = array();

		foreach ($this->to as $t)
			$toArr[] = $this->addrFormat($t);

		$to = implode(', ', $toArr);

		if (empty($this->sender))
			$params = "-oi ";
		else
			$params = sprintf("-oi -f %s", $this->sender);

		if ($this->sender != '' && !ini_get('safe_mode'))
		{
			$oldFrom = ini_get('sendmail_from');
			ini_set('sendmail_from', $this->sender);

			if ($this->singleTo === true && count($toArr) > 1)
			{
				foreach ($toArr as $key => $val)
				{
					$rt = @mail($val, $this->encodeHeader($this->secureHeader($this->subject)), $body, $header, $params);

					// implement call back function if it exists
					$isSent = ($rt == 1) ? 1 : 0;
					$this->doCallback($isSent, $val, $this->cc, $this->bcc, $this->subject, $body);
				}
			}
			else
			{
				$rt = @mail($to, $this->encodeHeader($this->secureHeader($this->subject)), $body, $header, $params);

				// implement call back function if it exists
				$isSent = ($rt == 1) ? 1 : 0;
				$this->doCallback($isSent, $to, $this->cc, $this->bcc, $this->subject, $body);
			}
		}
		else
		{
			if ($this->singleTo === true && count($toArr) > 1)
			{
				foreach ($toArr as $key => $val)
				{
					$rt = @mail($val, $this->encodeHeader($this->secureHeader($this->subject)), $body, $header, $params);

					// implement call back function if it exists
					$isSent = ($rt == 1) ? 1 : 0;
					$this->doCallback($isSent, $val, $this->cc, $this->bcc, $this->subject, $body);
				}
			}
			else
			{
				$rt = @mail($to, $this->encodeHeader($this->secureHeader($this->subject)), $body, $header, $params);

				// implement call back function if it exists
				$isSent = ($rt == 1) ? 1 : 0;
				$this->doCallback($isSent, $to, $this->cc, $this->bcc, $this->subject, $body);
			}
		}

		if (isset($oldFrom))
		{
			ini_set('sendmail_from', $oldFrom);
		}

		if (!$rt)
			throw new phpMailerException($this->lang('instantiate'), self::STOP_CRITICAL);

		return true;
	}

	/**
	 * Sends mail via Smtp using PhpSmtp
	 * Returns false if there is a bad MAIL FROM, RCPT, or DATA input.
	 * @param string $header The message headers
	 * @param string $body The message body
	 * @throws phpMailerException
	 * @uses Smtp
	 * @access protected
	 * @return bool
	 */
	protected function smtpSend($header, $body)
	{
		require_once $this->pluginDir . 'Smtp.php';
		$badRcpt = array();

		if (!$this->smtpConnect())
			throw new phpMailerException($this->lang('smtp_connect_failed'), self::STOP_CRITICAL);

		$SmtpFrom = ($this->sender == '') ? $this->from : $this->sender;

		if (!$this->smtp->mail($SmtpFrom))
			throw new phpMailerException($this->lang('from_failed') . $SmtpFrom, self::STOP_CRITICAL);

		// Attempt to send attach all recipients
		foreach ($this->to as $to)
		{
			if (!$this->smtp->recipient($to[0]))
			{
				$badRcpt[] = $to[0];

				// implement call back function if it exists
				$isSent = 0;
				$this->doCallback($isSent, $to[0], '', '', $this->subject, $body);
			}
			else
			{
				// implement call back function if it exists
				$isSent = 1;
				$this->doCallback($isSent, $to[0], '', '', $this->subject, $body);
			}
		}

		foreach($this->cc as $cc)
		{
			if (!$this->smtp->recipient($cc[0]))
			{
				$badRcpt[] = $cc[0];

				// implement call back function if it exists
				$isSent = 0;
				$this->doCallback($isSent, '', $cc[0], '', $this->subject, $body);
			}
			else
			{
				// implement call back function if it exists
				$isSent = 1;
				$this->doCallback($isSent, '', $cc[0], '', $this->subject, $body);
			}
		}

		foreach ($this->bcc as $bcc)
		{
			if (!$this->smtp->recipient($bcc[0]))
			{
				$badRcpt[] = $bcc[0];

				// implement call back function if it exists
				$isSent = 0;
				$this->doCallback($isSent, '', '', $bcc[0], $this->subject, $body);
			}
			else
			{
				// implement call back function if it exists
				$isSent = 1;
				$this->doCallback($isSent, '', '', $bcc[0], $this->subject, $body);
			}
		}

		// Create error message for any bad addresses
		if (count($badRcpt) > 0 )
		{
			$badAddresses = implode(', ', $badRcpt);
			throw new phpMailerException($this->lang('recipients_failed') . $badAddresses);
		}

		if (!$this->smtp->data($header . $body))
			throw new phpMailerException($this->lang('data_not_accepted'), self::STOP_CRITICAL);

		if ($this->smtpKeepAlive == true)
			$this->smtp->reset();

		return true;
	}

	/**
	 * Initiates a connection to an Smtp server. Returns false if the operation failed.
	 * @uses Smtp
	 * @access public
	 * @throws phpMailerException
	 * @return bool
	 */
	public function smtpConnect()
	{
		if (is_null($this->smtp))
			$this->smtp = new Smtp();

		$this->smtp->doDebug = $this->smtpDebug;

		$hosts = explode(';', $this->host);
		$index = 0;
		$connection = $this->smtp->connected();

		// retry while there is no connection
		try
		{
			while ($index < count($hosts) && !$connection)
			{
				$hostInfo = array();

				if (preg_match('/^(.+):([0-9]+)$/', $hosts[$index], $hostInfo))
				{
					$host = $hostInfo[1];
					$port = $hostInfo[2];
				}
				else
				{
					$host = $hosts[$index];
					$port = $this->port;
				}

				$tls = ($this->smtpSecure == 'tls');
				$ssl = ($this->smtpSecure == 'ssl');

				if ($this->smtp->connect(($ssl ? 'ssl://':'').$host, $port, $this->timeout))
				{
					$hello = ($this->helo != '' ? $this->helo : $this->serverHostName());
					$this->smtp->hello($hello);

					if ($tls)
					{
						if (!$this->smtp->startTls())
							throw new phpMailerException($this->lang('tls'));

						// we must resend HELO after tls negotiation
						$this->smtp->hello($hello);
					}

					$connection = true;
					if ($this->smtpAuth)
					{
						if (!$this->smtp->authenticate($this->userName, $this->password))
							throw new phpMailerException($this->lang('authenticate'));
					}
				}

				$index++;

				if (!$connection)
					throw new phpMailerException($this->lang('connect_host'));
			}
		}
		catch (phpMailerException $e)
		{
			$this->smtp->reset();

			if ($this->exceptions)
				throw $e;
		}

		return true;
	}

	/**
	 * Closes the active Smtp session if one exists.
	 * @return void
	 */
	public function smtpClose()
	{
		if (!is_null($this->smtp))
		{
			if($this->smtp->connected())
			{
				$this->smtp->quit();
				$this->smtp->close();
			}
		}
	}

	/**
	 * Sets the language for all class error messages.
	 * Returns false if it cannot load the language file.	The default language is English.
	 * @param string $langcode ISO 639-1 2-character language code (e.g. Portuguese: "br")
	 * @param string $lang_path Path to the language file directory
	 * @return mixed
	 * @access public
	 */
	function setLanguage($langcode = 'en', $lang_path = 'language/')
	{
		// define full set of translatable strings
		$PHPMAILER_LANG = array(
		    'provide_address'      => 'You must provide at least one recipient email address.',
		    'mailer_not_supported' => ' mailer is not supported.',
		    'execute'              => 'Could not execute: ',
		    'instantiate'          => 'Could not instantiate mail function.',
		    'authenticate'         => 'Smtp Error: Could not authenticate.',
		    'from_failed'          => 'The following From address failed: ',
		    'recipients_failed'    => 'Smtp Error: The following recipients failed: ',
		    'data_not_accepted'    => 'Smtp Error: Data not accepted.',
		    'connect_host'         => 'Smtp Error: Could not connect to Smtp host.',
		    'file_access'          => 'Could not access file: ',
		    'file_open'            => 'File Error: Could not open file: ',
		    'encoding'             => 'Unknown encoding: ',
		    'signing'              => 'Signing Error: ',
		    'smtp_error'           => 'Smtp server error: ',
		    'empty_message'        => 'Message body empty',
		    'invalid_address'      => 'Invalid address',
		    'variable_set'         => 'Cannot set or reset variable: '
		);

		// overwrite language-specific strings. This way we'll never have missing translations - no more "language string failed to load"!
		$l = true;

		// there is no English translation file
		if ($langcode != 'en')
		{
			$l = @include $lang_path.'phpmailer.lang-'.$langcode.'.php';
		}

		$this->language = $PHPMAILER_LANG;

		return ($l == true); //Returns false if language not found
	}

	/**
	 * Return the current array of language strings
	 * @return array
	 */
	public function getTranslations()
	{
		return $this->language;
	}

	/**
	* Creates recipient headers.
	 * @param $type
	 * @param $addr
	 * @access public
	 * @return string
	 */
	public function addrAppend($type, $addr)
	{
		$addrStr = $type . ': ';
		$addresses = array();

		foreach ($addr as $a)
			$addresses[] = $this->addrFormat($a);

		$addrStr .= implode(', ', $addresses);
		$addrStr .= $this->lineEnding;

		return $addrStr;
	}

	/**
	 * Formats an address correctly.
	 * @param $addr
	 * @access public
	 * @return string
	 */
	public function addrFormat($addr)
	{
		if (empty($addr[1]))
		{
			return $this->secureHeader($addr[0]);
		}
		else
		{
			return $this->encodeHeader($this->secureHeader($addr[1]), 'phrase') . " <" . $this->secureHeader($addr[0]) . ">";
		}
	}

	/**
	 * Wraps message for use with mailers that do not automatically perform wrapping and for quoted-printable.
	 * Original written by philippe.
	 * @param string $message The message to wrap
	 * @param integer $length The line length to wrap to
	 * @param boolean $qpMode Whether to run in Quoted-Printable mode
	 * @access public
	 * @return string
	 */
	public function wrapText($message, $length, $qpMode = false)
	{
		$softBreak = ($qpMode) ? sprintf(" =%s", $this->lineEnding) : $this->lineEnding;

		// If utf-8 encoding is used, we will need to make sure we don't split multibyte characters when we wrap
		$isUTF8 = (strtolower($this->charSet) == "utf-8");

		$message = $this->fixEOL($message);

		if (substr($message, -1) == $this->lineEnding)
			$message = substr($message, 0, -1);

		$line = explode($this->lineEnding, $message);
		$message = '';

		for ($i = 0 ;$i < count($line); $i++)
		{
			$linePart = explode(' ', $line[$i]);
			$buf = '';

			for ($e = 0; $e < count($linePart); $e++)
			{
				$word = $linePart[$e];

				if ($qpMode and (strlen($word) > $length))
				{
					$spaceLeft = $length - strlen($buf) - 1;

					if ($e != 0)
					{
						if ($spaceLeft > 20)
						{
							$len = $spaceLeft;

							if ($isUTF8)
							{
								$len = $this->utf8CharBoundary($word, $len);
							}
							elseif (substr($word, $len - 1, 1) == "=")
							{
								$len--;
							}
							elseif (substr($word, $len - 2, 1) == "=")
							{
								$len -= 2;
							}

							$part = substr($word, 0, $len);
							$word = substr($word, $len);
							$buf .= ' ' . $part;
							$message .= $buf . sprintf("=%s", $this->lineEnding);
						}
						else
						{
							$message .= $buf . $softBreak;
						}

						$buf = '';
					}

					while (strlen($word) > 0)
					{
						$len = $length;

						if ($isUTF8)
						{
							$len = $this->utf8CharBoundary($word, $len);
						}
						elseif (substr($word, $len - 1, 1) == "=")
						{
							$len--;
						}
						elseif (substr($word, $len - 2, 1) == "=")
						{
							$len -= 2;
						}

						$part = substr($word, 0, $len);
						$word = substr($word, $len);

						if (strlen($word) > 0)
						{
							$message .= $part . sprintf("=%s", $this->lineEnding);
						}
						else
						{
							$buf = $part;
						}
					}
				}
				else
				{
					$buf_o = $buf;
					$buf .= ($e == 0) ? $word : (' ' . $word);

					if (strlen($buf) > $length and $buf_o != '')
					{
						$message .= $buf_o . $softBreak;
						$buf = $word;
					}
				}
			}

			$message .= $buf . $this->lineEnding;
		}

	return $message;
	}

	/**
	 * Finds last character boundary prior to maxLength in a utf-8 quoted (printable) encoded string.
	 * Original written by Colin Brown.
	 * @access public
	 * @param string $encodedText utf-8 QP text
	 * @param int $maxLength	find last character boundary prior to this length
	 * @return int
	 */
	public function utf8CharBoundary($encodedText, $maxLength)
	{
		$foundSplitPos = false;
		$lookBack = 3;

		while (!$foundSplitPos)
		{
			$lastChunk = substr($encodedText, $maxLength - $lookBack, $lookBack);
			$encodedCharPos = strpos($lastChunk, "=");

			if ($encodedCharPos !== false)
			{
				// found start of encoded character byte within $lookBack block.
				// check the encoded byte value (the 2 chars after the '=')
				$hex = substr($encodedText, $maxLength - $lookBack + $encodedCharPos + 1, 2);
				$dec = hexdec($hex);

				// Single byte character.
				if ($dec < 128)
				{
					// If the encoded char was found at pos 0, it will fit otherwise reduce maxLength to start of the encoded char
					$maxLength = ($encodedCharPos == 0) ? $maxLength : $maxLength - ($lookBack - $encodedCharPos);
					$foundSplitPos = true;
				}
				elseif ($dec >= 192) // First byte of a multi byte character
				{
					// Reduce maxLength to split at start of character
					$maxLength = $maxLength - ($lookBack - $encodedCharPos);
					$foundSplitPos = true;
				}
				elseif ($dec < 192) // Middle byte of a multi byte character, look further back
				{
					$lookBack += 3;
				}
			}
			else
			{
				// No encoded character found
				$foundSplitPos = true;
			}
		}

		return $maxLength;
	}


	/**
	 * Set the body wrapping.
	 * @access public
	 * @return void
	 */
	public function setWordWrap()
	{
		if($this->wordWrap < 1)
			return;

		switch ($this->messageType)
		{
			case 'alt':
			case 'alt_inline':
			case 'alt_attach':
			case 'alt_inline_attach':
				$this->altBody = $this->wrapText($this->altBody, $this->wordWrap);
				break;

			default:
				$this->body = $this->wrapText($this->body, $this->wordWrap);
				break;
		}
	}

	/**
	 * Assembles message header.
	 * @access public
	 * @return string The assembled header
	 */
	public function createHeader()
	{
		$result = '';

		// Set the boundaries
		$uniqId = md5(uniqid(time()));
		$this->boundary[1] = 'b1_' . $uniqId;
		$this->boundary[2] = 'b2_' . $uniqId;
		$this->boundary[3] = 'b3_' . $uniqId;

		$result .= $this->headerLine('Date', self::RfcDate());
		if ($this->sender == '')
			$result .= $this->headerLine('Return-Path', trim($this->from));
		else
			$result .= $this->headerLine('Return-Path', trim($this->sender));

		// To be created automatically by mail()
		if ($this->mailer != 'mail')
		{
			if ($this->singleTo === true)
			{
				foreach ($this->to as $t)
				{
					$this->singleToArray[] = $this->addrFormat($t);
				}
			}
			else
			{
				if (count($this->to) > 0)
				{
					$result .= $this->addrAppend('To', $this->to);
				} elseif (count($this->cc) == 0)
				{
					$result .= $this->headerLine('To', 'undisclosed-recipients:;');
				}
			}
		}

		$from = array();
		$from[0][0] = trim($this->from);
		$from[0][1] = $this->fromName;
		$result .= $this->addrAppend('From', $from);

		// sendmail and mail() extract Cc from the header before sending
		if (count($this->cc) > 0)
			$result .= $this->addrAppend('Cc', $this->cc);

		// sendmail and mail() extract Bcc from the header before sending
		if ((($this->mailer == 'sendmail') || ($this->mailer == 'mail')) && (count($this->bcc) > 0))
			$result .= $this->addrAppend('Bcc', $this->bcc);

		if (count($this->replyTo) > 0)
			$result .= $this->addrAppend('Reply-To', $this->replyTo);

		// mail() sets the subject itself
		if ($this->mailer != 'mail')
			$result .= $this->headerLine('Subject', $this->encodeHeader($this->secureHeader($this->subject)));

		if($this->messageID != '')
			$result .= $this->headerLine('Message-ID', $this->messageID);
		else
			$result .= sprintf("Message-ID: <%s@%s>%s", $uniqId, $this->serverHostName(), $this->lineEnding);

		$result .= $this->headerLine('X-Priority', $this->priority);

		if ($this->xMailer)
			$result .= $this->headerLine('X-Mailer', $this->xMailer);
		else
			$result .= $this->headerLine('X-Mailer', 'PhpMailer '.$this->version.' (http://code.google.com/a/apache-extras.org/p/phpmailer/)');

		if ($this->confirmReadingTo != '')
			$result .= $this->headerLine('Disposition-Notification-To', '<' . trim($this->confirmReadingTo) . '>');

		// Add custom headers
		for ($index = 0; $index < count($this->customHeader); $index++)
			$result .= $this->headerLine(trim($this->customHeader[$index][0]), $this->encodeHeader(trim($this->customHeader[$index][1])));

		if (!$this->signKeyFile)
		{
			$result .= $this->headerLine('MIME-Version', '1.0');
			$result .= $this->getMailMime();
	}

		return $result;
	}

	/**
	 * Returns the message MIME.
	 * @access public
	 * @return string
	 */
	public function getMailMime()
	{
		$result = '';

		switch ($this->messageType)
		{
			case 'plain':
				$result .= $this->headerLine('Content-Transfer-Encoding', $this->encoding);
				$result .= $this->textLine('Content-Type: '.$this->contentType.'; charset="'.$this->charSet.'"');
				break;

			case 'inline':
				$result .= $this->headerLine('Content-Type', 'multipart/related;');
				$result .= $this->textLine("\tboundary=\"" . $this->boundary[1] . '"');
				break;

			case 'attach':
			case 'inline_attach':
			case 'alt_attach':
			case 'alt_inline_attach':
				$result .= $this->headerLine('Content-Type', 'multipart/mixed;');
				$result .= $this->textLine("\tboundary=\"" . $this->boundary[1] . '"');
				break;

			case 'alt':
			case 'alt_inline':
				$result .= $this->headerLine('Content-Type', 'multipart/alternative;');
				$result .= $this->textLine("\tboundary=\"" . $this->boundary[1] . '"');
				break;
		}

		if ($this->mailer != 'mail')
			$result .= $this->lineEnding.$this->lineEnding;

		return $result;
	}

	/**
	 * Returns the MIME message (headers and body). Only really valid post preSend().
	 * @access public
	 * @return string
	 */
	public function getSentMimeMessage()
	{
		return $this->sentMimeMessage;
	}


	/**
	 * Assembles the message body. Returns an empty string on failure.
	 * @access public
	 * @throws phpMailerException
	 * @return string The assembled message body
	 */
	public function createBody()
	{
		$body = '';

		if ($this->signKeyFile)
			$body .= $this->getMailMime();

		$this->setWordWrap();

		switch($this->messageType)
		{
			case 'plain':
				$body .= $this->encodeString($this->body, $this->encoding);
				break;

			case 'inline':
				$body .= $this->getBoundary($this->boundary[1], '', '', '');
				$body .= $this->encodeString($this->body, $this->encoding);
				$body .= $this->lineEnding.$this->lineEnding;
				$body .= $this->attachAll("inline", $this->boundary[1]);
				break;

			case 'attach':
				$body .= $this->getBoundary($this->boundary[1], '', '', '');
				$body .= $this->encodeString($this->body, $this->encoding);
				$body .= $this->lineEnding.$this->lineEnding;
				$body .= $this->attachAll("attachment", $this->boundary[1]);
				break;

			case 'inline_attach':
				$body .= $this->textLine("--" . $this->boundary[1]);
				$body .= $this->headerLine('Content-Type', 'multipart/related;');
				$body .= $this->textLine("\tboundary=\"" . $this->boundary[2] . '"');
				$body .= $this->lineEnding;
				$body .= $this->getBoundary($this->boundary[2], '', '', '');
				$body .= $this->encodeString($this->body, $this->encoding);
				$body .= $this->lineEnding.$this->lineEnding;
				$body .= $this->attachAll("inline", $this->boundary[2]);
				$body .= $this->lineEnding;
				$body .= $this->attachAll("attachment", $this->boundary[1]);
				break;

			case 'alt':
				$body .= $this->getBoundary($this->boundary[1], '', 'text/plain', '');
				$body .= $this->encodeString($this->altBody, $this->encoding);
				$body .= $this->lineEnding.$this->lineEnding;
				$body .= $this->getBoundary($this->boundary[1], '', 'text/html', '');
				$body .= $this->encodeString($this->body, $this->encoding);
				$body .= $this->lineEnding.$this->lineEnding;
				$body .= $this->endBoundary($this->boundary[1]);
				break;

			case 'alt_inline':
				$body .= $this->getBoundary($this->boundary[1], '', 'text/plain', '');
				$body .= $this->encodeString($this->altBody, $this->encoding);
				$body .= $this->lineEnding.$this->lineEnding;
				$body .= $this->textLine("--" . $this->boundary[1]);
				$body .= $this->headerLine('Content-Type', 'multipart/related;');
				$body .= $this->textLine("\tboundary=\"" . $this->boundary[2] . '"');
				$body .= $this->lineEnding;
				$body .= $this->getBoundary($this->boundary[2], '', 'text/html', '');
				$body .= $this->encodeString($this->body, $this->encoding);
				$body .= $this->lineEnding.$this->lineEnding;
				$body .= $this->attachAll("inline", $this->boundary[2]);
				$body .= $this->lineEnding;
				$body .= $this->endBoundary($this->boundary[1]);
				break;

			case 'alt_attach':
				$body .= $this->textLine("--" . $this->boundary[1]);
				$body .= $this->headerLine('Content-Type', 'multipart/alternative;');
				$body .= $this->textLine("\tboundary=\"" . $this->boundary[2] . '"');
				$body .= $this->lineEnding;
				$body .= $this->getBoundary($this->boundary[2], '', 'text/plain', '');
				$body .= $this->encodeString($this->altBody, $this->encoding);
				$body .= $this->lineEnding.$this->lineEnding;
				$body .= $this->getBoundary($this->boundary[2], '', 'text/html', '');
				$body .= $this->encodeString($this->body, $this->encoding);
				$body .= $this->lineEnding.$this->lineEnding;
				$body .= $this->endBoundary($this->boundary[2]);
				$body .= $this->lineEnding;
				$body .= $this->attachAll("attachment", $this->boundary[1]);
				break;

			case 'alt_inline_attach':
				$body .= $this->textLine("--" . $this->boundary[1]);
				$body .= $this->headerLine('Content-Type', 'multipart/alternative;');
				$body .= $this->textLine("\tboundary=\"" . $this->boundary[2] . '"');
				$body .= $this->lineEnding;
				$body .= $this->getBoundary($this->boundary[2], '', 'text/plain', '');
				$body .= $this->encodeString($this->altBody, $this->encoding);
				$body .= $this->lineEnding.$this->lineEnding;
				$body .= $this->textLine("--" . $this->boundary[2]);
				$body .= $this->headerLine('Content-Type', 'multipart/related;');
				$body .= $this->textLine("\tboundary=\"" . $this->boundary[3] . '"');
				$body .= $this->lineEnding;
				$body .= $this->getBoundary($this->boundary[3], '', 'text/html', '');
				$body .= $this->encodeString($this->body, $this->encoding);
				$body .= $this->lineEnding.$this->lineEnding;
				$body .= $this->attachAll("inline", $this->boundary[3]);
				$body .= $this->lineEnding;
				$body .= $this->endBoundary($this->boundary[2]);
				$body .= $this->lineEnding;
				$body .= $this->attachAll("attachment", $this->boundary[1]);
				break;
		}

		if ($this->isError())
		{
			$body = '';
		}
		elseif ($this->signKeyFile)
		{
			try
			{
				$file = tempnam('', 'mail');
				file_put_contents($file, $body); //TODO check this worked
				$signed = tempnam("", "signed");

				if (@openssl_pkcs7_sign($file, $signed, "file://".$this->signCertFile, array("file://".$this->signKeyFile, $this->signKeyPass), null))
				{
					@unlink($file);
					$body = file_get_contents($signed);
					@unlink($signed);
				}
				else
				{
					@unlink($file);
					@unlink($signed);
					throw new phpMailerException($this->lang("signing").openssl_error_string());
				}
			}
			catch (phpMailerException $e)
			{
				$body = '';

				if ($this->exceptions)
					throw $e;
			}
		}

		return $body;
	}

	/**
	 * Returns the start of a message boundary.
	 * @access protected
	 * @param $boundary
	 * @param $charSet
	 * @param $contentType
	 * @param $encoding
	 * @return string
	 */
	protected function getBoundary($boundary, $charSet, $contentType, $encoding)
	{
		$result = '';

		if ($charSet == '')
			$charSet = $this->charSet;

		if ($contentType == '')
			$contentType = $this->contentType;

		if ($encoding == '')
			$encoding = $this->encoding;

		$result .= $this->textLine('--' . $boundary);
		$result .= sprintf("Content-Type: %s; charset=\"%s\"", $contentType, $charSet);
		$result .= $this->lineEnding;
		$result .= $this->headerLine('Content-Transfer-Encoding', $encoding);
		$result .= $this->lineEnding;

		return $result;
	}

	/**
	 * Returns the end of a message boundary.
	 * @access protected
	 * @param $boundary
	 * @return string
	 */
	protected function endBoundary($boundary)
	{
		return $this->lineEnding.'--'.$boundary.'--'.$this->lineEnding;
	}

	/**
	* Sets the message type.
	* @access protected
	* @return void
	*/
	protected function setMessageType()
	{
		$this->messageType = array();

		if ($this->alternativeExists())
			$this->messageType[] = "alt";

		if ($this->inlineImageExists())
			$this->messageType[] = "inline";

		if ($this->attachmentExists())
			$this->messageType[] = "attach";

		$this->messageType = implode("_", $this->messageType);

		if ($this->messageType == "")
			$this->messageType = "plain";
	}

	/**
	 * Returns a formatted header line.
	 * @access public
	 * @param $name
	 * @param $value
	 * @return string
	 */
	public function headerLine($name, $value)
	{
		return $name.': '.$value.$this->lineEnding;
	}

	/**
	 * Returns a formatted mail line.
	 * @access public
	 * @param $value
	 * @return string
	 */
	public function textLine($value)
	{
		return $value.$this->lineEnding;
	}

	/**
	 * Adds an attachment from a path on the filesystem.
	 * Returns false if the file could not be found
	 * or accessed.
	 * @param string $path Path to the attachment.
	 * @param string $name Overrides the attachment name.
	 * @param string $encoding File encoding (see $Encoding).
	 * @param string $type File extension (MIME) type.
	 * @throws phpMailerException
	 * @return bool
	 */
	public function addAttachment($path, $name = '', $encoding = 'base64', $type = 'application/octet-stream')
	{
		try
		{
			if (!@is_file($path))
				throw new phpMailerException($this->lang('file_access') . $path, self::STOP_CONTINUE);

			$filename = basename($path);

			if ($name == '')
				$name = $filename;

			$this->attachment[] = array(
			    0 => $path,
			    1 => $filename,
			    2 => $name,
			    3 => $encoding,
			    4 => $type,
			    5 => false, // isStringAttachment
			    6 => 'attachment',
			    7 => 0
			);
		}
		catch (phpMailerException $e)
		{
			$this->setError($e->getMessage());

			if ($this->exceptions)
				throw $e;

			if ($this->smtpDebug)
				echo $e->getMessage()."\n";

			if ($e->getCode() == self::STOP_CRITICAL)
				return false;
		}

		return true;
	}

	/**
	 * Return the current array of attachments
	 * @return array
	 */
	public function getAttachments()
	{
		return $this->attachment;
	}

	/**
	 * Attaches all fs, string, and binary attachments to the message. Returns an empty string on failure.
	 * @access protected
	 * @param $dispositionType
	 * @param $boundary
	 * @return string
	 */
	protected function attachAll($dispositionType, $boundary)
	{
		// Return text of body
		$mime = array();
		$cidUniq = array();
		$incl = array();

		// Add all attachments
		foreach ($this->attachment as $attachment)
		{
			// Check if it is a valid disposition filter
			if ($attachment[6] == $dispositionType)
			{
				// Check for string attachment
				$bString = $attachment[5];

				if ($bString)
					$string = $attachment[0];
				else
					$path = $attachment[0];

				$inclHash = md5(serialize($attachment));

				if (in_array($inclHash, $incl))
					continue;

				$incl[] = $inclHash;

				$fileName = $attachment[1];
				$name = $attachment[2];
				$encoding = $attachment[3];
				$type = $attachment[4];
				$disposition = $attachment[6];
				$cid = $attachment[7];

				if ($disposition == 'inline' && isset($cidUniq[$cid]))
					continue;

				$cidUniq[$cid] = true;

				$mime[] = sprintf("--%s%s", $boundary, $this->lineEnding);
				$mime[] = sprintf("Content-Type: %s; name=\"%s\"%s", $type, $this->encodeHeader($this->secureHeader($name)), $this->lineEnding);
				$mime[] = sprintf("Content-Transfer-Encoding: %s%s", $encoding, $this->lineEnding);

				if ($disposition == 'inline')
					$mime[] = sprintf("Content-ID: <%s>%s", $cid, $this->lineEnding);

				$mime[] = sprintf("Content-Disposition: %s; filename=\"%s\"%s", $disposition, $this->encodeHeader($this->secureHeader($name)), $this->lineEnding.$this->lineEnding);

				// Encode as string attachment
				if ($bString)
				{
					$mime[] = $this->encodeString($string, $encoding);

					if ($this->isError())
						return '';

					$mime[] = $this->lineEnding.$this->lineEnding;
				}
				else
				{
					$mime[] = $this->encodeFile($path, $encoding);

					if ($this->isError())
						return '';

					$mime[] = $this->lineEnding.$this->lineEnding;
				}
			}
		}

		$mime[] = sprintf("--%s--%s", $boundary, $this->lineEnding);

		return implode("", $mime);
	}

	/**
	 * Encodes attachment in requested format.
	 * Returns an empty string on failure.
	 * @param string $path The full path to the file
	 * @param string $encoding The encoding to use; one of 'base64', '7bit', '8bit', 'binary', 'quoted-printable'
	 * @throws phpMailerException
	 * @see EncodeFile()
	 * @access protected
	 * @return string
	 */
	protected function encodeFile($path, $encoding = 'base64')
	{
		try
		{
			if (!is_readable($path))
				throw new phpMailerException($this->lang('file_open') . $path, self::STOP_CONTINUE);

		if (function_exists('get_magic_quotes'))
		{
			function get_magic_quotes()
			{
				return false;
			}
		}

			$magicQuotes = get_magic_quotes_runtime();

			if ($magicQuotes)
			{
				if (version_compare(PHP_VERSION, '5.3.0', '<'))
					set_magic_quotes_runtime(0);
				else
					ini_set('magic_quotes_runtime', 0);
			}

			$fileBuffer = file_get_contents($path);
			$fileBuffer = $this->encodeString($fileBuffer, $encoding);

			if ($magicQuotes)
			{
				if (version_compare(PHP_VERSION, '5.3.0', '<'))
					set_magic_quotes_runtime($magicQuotes);
				else
					ini_set('magic_quotes_runtime', $magicQuotes);
			}

			return $fileBuffer;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());
			return '';
		}
	}

	/**
	 * Encodes string to requested format.
	 * Returns an empty string on failure.
	 * @param string $str The text to encode
	 * @param string $encoding The encoding to use; one of 'base64', '7bit', '8bit', 'binary', 'quoted-printable'
	 * @access public
	 * @return string
	 */
	public function encodeString($str, $encoding = 'base64')
	{
		$encoded = '';

		switch (strtolower($encoding))
		{
			case 'base64':
				$encoded = chunk_split(base64_encode($str), 76, $this->lineEnding);
				break;

			case '7bit':
			case '8bit':
				$encoded = $this->fixEOL($str);

				// Make sure it ends with a line break
				if (substr($encoded, -(strlen($this->lineEnding))) != $this->lineEnding)
					$encoded .= $this->lineEnding;

				break;

			case 'binary':
				$encoded = $str;
				break;

			case 'quoted-printable':
				$encoded = $this->encodeQp($str);
				break;

			default:
				$this->setError($this->lang('encoding') . $encoding);
				break;
		}

		return $encoded;
	}

	/**
	 * Encode a header string to best (shortest) of Q, B, quoted or none.
	 * @access public
	 * @param        $str
	 * @param string $position
	 * @return string
	 */
	public function encodeHeader($str, $position = 'text')
	{
		$x = 0;

		switch (strtolower($position))
		{
			case 'phrase':
				if (!preg_match('/[\200-\377]/', $str))
				{
					// Can't use addslashes as we don't know what value has magic_quotes_sybase
					$encoded = addcslashes($str, "\0..\37\177\\\"");

					if (($str == $encoded) && !preg_match('/[^A-Za-z0-9!#$%&\'*+\/=?^_`{|}~ -]/', $str))
						return ($encoded);
					else
						return ("\"$encoded\"");
				}

				$x = preg_match_all('/[^\040\041\043-\133\135-\176]/', $str, $matches);
				break;

			case 'comment':
				$x = preg_match_all('/[()"]/', $str, $matches);
				// Fall-through

			case 'text':
			default:
				$x += preg_match_all('/[\000-\010\013\014\016-\037\177-\377]/', $str, $matches);
				break;
		}

		if ($x == 0)
			return ($str);

		$maxLen = 75 - 7 - strlen($this->charSet);

		// Try to select the encoding which should produce the shortest output
		if (strlen($str) / 3 < $x)
		{
			$encoding = 'B';
			if (function_exists('mb_strlen') && $this->hasMultiBytes($str))
			{
				// Use a custom function which correctly encodes and wraps long
				// multibyte strings without breaking lines within a character
				$encoded = $this->base64EncodeWrapMb($str);
			}
			else
			{
				$encoded = base64_encode($str);
				$maxLen -= $maxLen % 4;
				$encoded = trim(chunk_split($encoded, $maxLen, "\n"));
			}
		}
		else
		{
			$encoding = 'Q';
			$encoded = $this->encodeQ($str, $position);
			$encoded = $this->wrapText($encoded, $maxLen, true);
			$encoded = str_replace('='.$this->lineEnding, "\n", trim($encoded));
		}

		$encoded = preg_replace('/^(.*)$/m', " =?".$this->charSet."?$encoding?\\1?=", $encoded);
		$encoded = trim(str_replace("\n", $this->lineEnding, $encoded));

		return $encoded;
	}

	/**
	 * Checks if a string contains multibyte characters.
	 * @access public
	 * @param string $str multi-byte text to wrap encode
	 * @return bool
	 */
	public function hasMultiBytes($str)
	{
		if (function_exists('mb_strlen'))
		{
			return (strlen($str) > mb_strlen($str, $this->charSet));
		}
		else
		{
			// Assume no multibytes (we can't handle without mbstring functions anyway)
			return false;
		}
	}

	/**
	 * Correctly encodes and wraps long multibyte strings for mail headers
	 * without breaking lines within a character.
	 * Adapted from a function by paravoid at http://uk.php.net/manual/en/function.mb-encode-mimeheader.php
	 * @access public
	 * @param string $str multi-byte text to wrap encode
	 * @return string
	 */
	public function base64EncodeWrapMb($str)
	{
		$start = "=?".$this->charSet."?B?";
		$end = "?=";
		$encoded = "";

		$mbLength = mb_strlen($str, $this->charSet);

		// Each line must have length <= 75, including $start and $end
		$length = 75 - strlen($start) - strlen($end);

		// Average multi-byte ratio
		$ratio = $mbLength / strlen($str);

		// Base64 has a 4:3 ratio
		$offset = $avgLength = floor($length * $ratio * .75);

		for ($i = 0; $i < $mbLength; $i += $offset)
		{
			$lookBack = 0;

			do
			{
				$offset = $avgLength - $lookBack;
				$chunk = mb_substr($str, $i, $offset, $this->charSet);
				$chunk = base64_encode($chunk);
				$lookBack++;
			}
			while (strlen($chunk) > $length);

			$encoded .= $chunk.$this->lineEnding;
		}

		// Chomp the last linefeed
		$encoded = substr($encoded, 0, -strlen($this->lineEnding));

		return $encoded;
	}

	/**
	 * Encode string to quoted-printable.  Only uses standard PHP, slow, but will always work
	 * @access public
	 * @param string  $input
	 * @param integer $lineMax Number of chars allowed on a line before wrapping
	 * @param bool    $spaceConv
	 * @internal param string $string the text to encode
	 * @return string
	 */
	public function encodeQPphp( $input = '', $lineMax = 76, $spaceConv = false)
	{
		$hex = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F');
		$lines = preg_split('/(?:\r\n|\r|\n)/', $input);
		$eol = "\r\n";
		$escape = '=';
		$output = '';

		while (list(, $line) = each($lines))
		{
			$linLen = strlen($line);
			$newline = '';

			for ($i = 0; $i < $linLen; $i++)
			{
				$c = substr($line, $i, 1);
				$dec = ord($c);

				if (($i == 0) && ($dec == 46))
				{
					// convert first point in the line into =2E
					$c = '=2E';
				}

				if ($dec == 32)
				{
					if ($i == ($linLen - 1))
					{
						// convert space at eol only
						$c = '=20';
					}
					else if ($spaceConv)
					{
						$c = '=20';
					}
				}
				elseif (($dec == 61) || ($dec < 32) || ($dec > 126))
				{
					// always encode "\t", which is *not* required
					$h2 = floor($dec/16);
					$h1 = floor($dec%16);
					$c = $escape.$hex[$h2].$hex[$h1];
				}

				if ((strlen($newline) + strlen($c)) >= $lineMax)
				{
					// CRLF is not counted
					$output .= $newline.$escape.$eol; // soft line break; " =\r\n" is okay
					$newline = '';

					// check if newline first character will be point or not
					if ($dec == 46)
						$c = '=2E';
				}

				$newline .= $c;
			} // end of for

			$output .= $newline.$eol;
		} // end of while

		return $output;
	}

	/**
	 * Encode string to RFC2045 (6.7) quoted-printable format
	 * Uses a PHP5 stream filter to do the encoding about 64x faster than the old version
	 * Also results in same content as you started with after decoding
	 * @see EncodeQPphp()
	 * @access public
	 * @param string $string the text to encode
	 * @param integer $lineMax Number of chars allowed on a line before wrapping
	 * @param boolean $spaceConv Dummy param for compatibility with existing EncodeQP function
	 * @return string
	 * @author Marcus Bointon
	 */
	public function encodeQp($string, $lineMax = 76, $spaceConv = false)
	{
		if (function_exists('quoted_printable_encode'))
		{
			//Use native function if it's available (>= PHP5.3)
			return quoted_printable_encode($string);
		}

		$filters = stream_get_filters();
		if (!in_array('convert.*', $filters))
		{
			// got convert stream filter?
			return $this->encodeQPphp($string, $lineMax, $spaceConv); //Fall back to old implementation
		}

		$fp = fopen('php://temp/', 'r+');
		$string = preg_replace('/\r\n?/', $this->lineEnding, $string); //Normalise line breaks
		$params = array('line-length' => $lineMax, 'line-break-chars' => $this->lineEnding);
		$s = stream_filter_append($fp, 'convert.quoted-printable-encode', STREAM_FILTER_READ, $params);

		fputs($fp, $string);
		rewind($fp);
		$out = stream_get_contents($fp);
		stream_filter_remove($s);
		$out = preg_replace('/^\./m', '=2E', $out); //Encode . if it is first char on a line, workaround for bug in Exchange
		fclose($fp);

		return $out;
	}

	/**
	 * Encode string to q encoding.
	 * @link http://tools.ietf.org/html/rfc2047
	 * @param string $str the text to encode
	 * @param string $position Where the text is going to be used, see the RFC for what that means
	 * @access public
	 * @return string
	 */
	public function encodeQ($str, $position = 'text')
	{
		// There should not be any EOL in the string
		$encoded = preg_replace('/[\r\n]*/', '', $str);

		switch (strtolower($position))
		{
			case 'phrase':
				$encoded = preg_replace("/([^A-Za-z0-9!*+\/ -])/e", "'='.sprintf('%02X', ord('\\1'))", $encoded);
				break;

			case 'comment':
				$encoded = preg_replace("/([\(\)\"])/e", "'='.sprintf('%02X', ord('\\1'))", $encoded);
			case 'text':
			default:
				// Replace every high ascii, control =, ? and _ characters
				// TODO using /e (equivalent to eval()) is probably not a good idea
				$encoded = preg_replace('/([\000-\011\013\014\016-\037\075\077\137\177-\377])/e', "'='.sprintf('%02X', ord(stripslashes('\\1')))", $encoded);
				break;
		}

		// Replace every spaces to _ (more readable than =20)
		$encoded = str_replace(' ', '_', $encoded);

		return $encoded;
	}

	/**
	 * Adds a string or binary attachment (non-filesystem) to the list.  This method can be used to attach ascii or binary data, such as a BLOB record from a database.
	 * @param string $string String attachment data.
	 * @param string $filename Name of the attachment.
	 * @param string $encoding File encoding (see $Encoding).
	 * @param string $type File extension (MIME) type.
	 * @return void
	 */
	public function addStringAttachment($string, $filename, $encoding = 'base64', $type = 'application/octet-stream')
	{
		// Append to $attachment array
		$this->attachment[] = array(
		    0 => $string,
		    1 => $filename,
		    2 => basename($filename),
		    3 => $encoding,
		    4 => $type,
		    5 => true,	// isStringAttachment
		    6 => 'attachment',
		    7 => 0
		);
	}

	/**
	* Adds an embedded attachment.  This can include images, sounds, and just about any other document. Make sure to set the $type to an
	* image type. For JPEG images use "image/jpeg" and for GIF images use "image/gif".
	* @param string $path Path to the attachment.
	* @param string $cid Content ID of the attachment. Use this to identify the Id for accessing the image in an HTML form.
	* @param string $name Overrides the attachment name.
	* @param string $encoding File encoding (see $Encoding).
	* @param string $type File extension (MIME) type.
	* @return bool
	*/
	public function addEmbeddedImage($path, $cid, $name = '', $encoding = 'base64', $type = 'application/octet-stream')
	{
		if (!@is_file($path))
		{
			$this->setError($this->lang('file_access').$path);
			return false;
		}

		$filename = basename($path);
		if ($name == '')
			$name = $filename;

		// Append to $attachment array
		$this->attachment[] = array(
		    0 => $path,
		    1 => $filename,
		    2 => $name,
		    3 => $encoding,
		    4 => $type,
		    5 => false, // isStringAttachment
		    6 => 'inline',
		    7 => $cid
		);

		return true;
	}

	/**
	 * @param $string
	 * @param $cid
	 * @param string $filename
	 * @param string $encoding
	 * @param string $type
	 */
	public function addStringEmbeddedImage($string, $cid, $filename = '', $encoding = 'base64', $type = 'application/octet-stream')
	{
		// Append to $attachment array
		$this->attachment[] = array(
		    0 => $string,
		    1 => $filename,
		    2 => basename($filename),
		    3 => $encoding,
		    4 => $type,
		    5 => true, // isStringAttachment
		    6 => 'inline',
		    7 => $cid
		);
	}

	/**
	 * Returns true if an inline attachment is present.
	 * @access public
	 * @return bool
	 */
	public function inlineImageExists()
	{
		foreach ($this->attachment as $attachment)
		{
			if ($attachment[6] == 'inline')
				return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function attachmentExists()
	{
		foreach($this->attachment as $attachment)
		{
			if ($attachment[6] == 'attachment')
				return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function alternativeExists()
	{
		return strlen($this->altBody) > 0;
	}

	/**
	 * Clears all recipients assigned in the TO array.  Returns void.
	 * @return void
	 */
	public function clearAddresses()
	{
		foreach ($this->to as $to)
		{
			unset($this->allRecipients[strtolower($to[0])]);
		}

		$this->to = array();
	}

	/**
	 * Clears all recipients assigned in the CC array. Returns void.
	 * @return void
	 */
	public function clearCcs()
	{
		foreach ($this->cc as $cc)
		{
			unset($this->allRecipients[strtolower($cc[0])]);
		}

		$this->cc = array();
	}

	/**
	 * Clears all recipients assigned in the BCC array. Returns void.
	 * @return void
	 */
	public function clearBccs()
	{
		foreach($this->bcc as $bcc)
		{
			unset($this->allRecipients[strtolower($bcc[0])]);
		}

		$this->bcc = array();
	}

	/**
	 * Clears all recipients assigned in the ReplyTo array. Returns void.
	 * @return void
	 */
	public function clearReplyTos()
	{
		$this->replyTo = array();
	}

	/**
	 * Clears all recipients assigned in the TO, CC and BCC  Returns void.
	 * @return void
	 */
	public function clearAllRecipients()
	{
		$this->to = array();
		$this->cc = array();
		$this->bcc = array();
		$this->allRecipients = array();
	}

	/**
	 * Clears all previously set filesystem, string, and binary attachments. Returns void.
	 * @return void
	 */
	public function clearAttachments()
	{
		$this->attachment = array();
	}

	/**
	 * Clears all custom headers. Returns void.
	 * @return void
	 */
	public function clearCustomHeaders()
	{
		$this->customHeader = array();
	}

	/**
	 * Adds the error message to the error container.
	 * @access protected
	 * @param $msg
	 * @return void
	 */
	protected function setError($msg)
	{
		$this->errorCount++;

		if ($this->mailer == 'smtp' && !is_null($this->smtp))
		{
			$lastError = $this->smtp->getError();

			if (!empty($lastError) && array_key_exists('smtp_msg', $lastError))
				$msg .= '<p>'.$this->lang('smtp_error').$lastError['smtp_msg']."</p>\n";
		}

		$this->errorInfo = $msg;
	}

	/**
	 * Returns the proper RFC 822 formatted date.
	 * @access public
	 * @return string
	 * @static
	 */
	public static function RfcDate()
	{
		$tz = date('Z');
		$tzs = ($tz < 0) ? '-' : '+';
		$tz = abs($tz);
		$tz = (int)($tz/3600) * 100 + ($tz % 3600) / 60;
		$result = sprintf("%s %s%04d", date('D, j M Y H:i:s'), $tzs, $tz);

		return $result;
	}

	/**
	 * Returns the server hostname or 'localhost.localdomain' if unknown.
	 * @access protected
	 * @return string
	 */
	protected function serverHostName()
	{
		if (!empty($this->hostname))
		{
			$result = $this->hostname;
		}
		elseif (isset($_SERVER['SERVER_NAME']))
		{
			$result = $_SERVER['SERVER_NAME'];
		}
		else
		{
			$result = 'localhost.localdomain';
		}

		return $result;
	}

	/**
	 * Returns a message in the appropriate language.
	 * @access protected
	 * @param $key
	 * @return string
	 */
	protected function lang($key)
	{
		if (count($this->language) < 1)
		{
			// set the default language
			$this->setLanguage('en');
		}

		if (isset($this->language[$key]))
			return $this->language[$key];
		else
			return 'Language string failed to load: '.$key;
	}

	/**
	 * Returns true if an error occurred.
	 * @access public
	 * @return bool
	 */
	public function isError()
	{
		return ($this->errorCount > 0);
	}

	/**
	 * Changes every end of line from CR or LF to CRLF.
	 * @access public
	 * @param $str
	 * @return string
	 */
	public function fixEOL($str)
	{
		$str = str_replace("\r\n", "\n", $str);
		$str = str_replace("\r", "\n", $str);
		$str = str_replace("\n", $this->lineEnding, $str);

		return $str;
	}

	/**
	 * Adds a custom header.
	 * @access public
	 * @param $customHeader
	 * @return void
	 */
	public function addCustomHeader($customHeader)
	{
		$this->customHeader[] = explode(':', $customHeader, 2);
	}

	/**
	 * Evaluates the message and returns modifications for inline images and backgrounds
	 * @access public
	 * @param        $message
	 * @param string $basedir
 	 * @return mixed $message
	 */
	public function msgHtml($message, $basedir = '')
	{
		preg_match_all("/(src|background)=[\"'](.*)[\"']/Ui", $message, $images);

		if (isset($images[2]))
		{
			foreach ($images[2] as $i => $url)
			{
				// do not change urls for absolute images (thanks to corvuscorax)
				if (!preg_match('#^[A-z]+://#', $url))
				{
					$fileName = basename($url);
					$directory = dirname($url);
					($directory == '.') ? $directory = '': '';
					$cid = 'cid:'.md5($fileName);
					$ext = pathinfo($fileName, PATHINFO_EXTENSION);
					$mimeType = self::_mimeTypes($ext);

					if (strlen($basedir) > 1 && substr($basedir, -1) != '/')
						$basedir .= '/';

					if (strlen($directory) > 1 && substr($directory, -1) != '/')
						$directory .= '/';

					if ($this->addEmbeddedImage($basedir.$directory.$fileName, md5($fileName), $fileName, 'base64', $mimeType))
						$message = preg_replace("/".$images[1][$i]."=[\"']".preg_quote($url, '/')."[\"']/Ui", $images[1][$i]."=\"".$cid."\"", $message);
				}
			}
		}

		$this->isHtml(true);
		$this->body = $message;

		if (empty($this->altBody))
		{
			$textMsg = trim(strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/s', '', $message)));

			if (!empty($textMsg))
				$this->altBody = html_entity_decode($textMsg, ENT_QUOTES, $this->charSet);
		}

		if (empty($this->altBody))
			$this->altBody = 'To view this email message, open it in an email client that can render HTML.'."\n\n";
	return $message;
	}

	/**
	* Gets the MIME type of the embedded or inline image
	* @param string File extension
	* @access public
	* @return string MIME type of ext
	* @static
	*/
	public static function _mimeTypes($ext = '')
	{
		$mimes = array(
		    'hqx'   => 'application/mac-binhex40',
		    'cpt'   => 'application/mac-compactpro',
		    'doc'   => 'application/msword',
		    'bin'   => 'application/macbinary',
		    'dms'   => 'application/octet-stream',
		    'lha'   => 'application/octet-stream',
		    'lzh'   => 'application/octet-stream',
		    'exe'   => 'application/octet-stream',
		    'class' => 'application/octet-stream',
		    'psd'   => 'application/octet-stream',
		    'so'    => 'application/octet-stream',
		    'sea'   => 'application/octet-stream',
		    'dll'   => 'application/octet-stream',
		    'oda'   => 'application/oda',
		    'pdf'   => 'application/pdf',
		    'ai'    => 'application/postscript',
		    'eps'   => 'application/postscript',
		    'ps'    =>  'application/postscript',
		    'smi'   => 'application/smil',
		    'smil'  => 'application/smil',
		    'mif'   => 'application/vnd.mif',
		    'xls'   => 'application/vnd.ms-excel',
		    'ppt'   => 'application/vnd.ms-powerpoint',
		    'wbxml' => 'application/vnd.wap.wbxml',
		    'wmlc'  => 'application/vnd.wap.wmlc',
		    'dcr'   => 'application/x-director',
		    'dir'   => 'application/x-director',
		    'dxr'   => 'application/x-director',
		    'dvi'   => 'application/x-dvi',
		    'gtar'  => 'application/x-gtar',
		    'php'   => 'application/x-httpd-php',
		    'php4'  => 'application/x-httpd-php',
		    'php3'  => 'application/x-httpd-php',
		    'phtml' => 'application/x-httpd-php',
		    'phps'  => 'application/x-httpd-php-source',
		    'js'    => 'application/x-javascript',
		    'swf'   => 'application/x-shockwave-flash',
		    'sit'   => 'application/x-stuffit',
		    'tar'   => 'application/x-tar',
		    'tgz'   => 'application/x-tar',
		    'xhtml' => 'application/xhtml+xml',
		    'xht'   => 'application/xhtml+xml',
		    'zip'   => 'application/zip',
		    'mid'   => 'audio/midi',
		    'midi'  => 'audio/midi',
		    'mpga'  => 'audio/mpeg',
		    'mp2'   => 'audio/mpeg',
		    'mp3'   => 'audio/mpeg',
		    'aif'   => 'audio/x-aiff',
		    'aiff'  => 'audio/x-aiff',
		    'aifc'  => 'audio/x-aiff',
		    'ram'   => 'audio/x-pn-realaudio',
		    'rm'    => 'audio/x-pn-realaudio',
		    'rpm'   => 'audio/x-pn-realaudio-plugin',
		    'ra'    => 'audio/x-realaudio',
		    'rv'    => 'video/vnd.rn-realvideo',
		    'wav'   => 'audio/x-wav',
		    'bmp'   => 'image/bmp',
		    'gif'   => 'image/gif',
		    'jpeg'  => 'image/jpeg',
		    'jpg'   => 'image/jpeg',
		    'jpe'   => 'image/jpeg',
		    'png'   => 'image/png',
		    'tiff'  => 'image/tiff',
		    'tif'   => 'image/tiff',
		    'css'   => 'text/css',
		    'html'  => 'text/html',
		    'htm'   => 'text/html',
		    'shtml' => 'text/html',
		    'txt'   => 'text/plain',
		    'text'  => 'text/plain',
		    'log'   => 'text/plain',
		    'rtx'   => 'text/richtext',
		    'rtf'   => 'text/rtf',
		    'xml'   => 'text/xml',
		    'xsl'   => 'text/xml',
		    'mpeg'  => 'video/mpeg',
		    'mpg'   => 'video/mpeg',
		    'mpe'   => 'video/mpeg',
		    'qt'    => 'video/quicktime',
		    'mov'   => 'video/quicktime',
		    'avi'   => 'video/x-msvideo',
		    'movie' => 'video/x-sgi-movie',
		    'doc'   => 'application/msword',
		    'word'  => 'application/msword',
		    'xl'    => 'application/excel',
		    'eml'   => 'message/rfc822'
	);
	return (!isset($mimes[strtolower($ext)])) ? 'application/octet-stream' : $mimes[strtolower($ext)];
	}

	/**
	 * Set (or reset) Class Objects (variables)
	 *
	 * Usage Example:
	 * $page->set('X-Priority', '3');
	 *
	 * @access public
	 *
	 * @param string $name Parameter Name
	 * @param mixed  $value Parameter Value
	 * NOTE: will not work with arrays, there are no arrays to set/reset
	 *
	 * @throws phpMailerException
	 * @todo Should this not be using __set() magic function?
	 * @return bool
	 */
	public function set($name, $value = '')
	{
		try
		{
			if (isset($this->$name))
				$this->$name = $value;
			else
				throw new phpMailerException($this->lang('variable_set') . $name, self::STOP_CRITICAL);
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			if ($e->getCode() == self::STOP_CRITICAL)
				return false;
		}

		return true;
	}

	/**
	 * Strips newlines to prevent header injection.
	 * @access public
	 * @param string $str String
	 * @return string
	 */
	public function secureHeader($str)
	{
		$str = str_replace("\r", '', $str);
		$str = str_replace("\n", '', $str);
		return trim($str);
	}

	/**
	 * Set the private key file and password to sign the message.
	 *
	 * @param        $certFileName
	 * @param string $keyFileName Parameter File Name
	 * @param string $keyPass Password for private key
	 */
	public function sign($certFileName, $keyFileName, $keyPass)
	{
		$this->signCertFile = $certFileName;
		$this->signKeyFile = $keyFileName;
		$this->signKeyPass = $keyPass;
	}

	/**
	 * @param $txt
	 * @return string
	 */
	public function dkimQp($txt)
	{
		$tmp = '';
		$line = '';

		for ($i = 0; $i < strlen($txt); $i++)
		{
			$ord = ord($txt[$i]);

			if (((0x21 <= $ord) && ($ord <= 0x3A)) || $ord == 0x3C || ((0x3E <= $ord) && ($ord <= 0x7E)))
				$line .= $txt[$i];
			else
				$line .= "=".sprintf("%02X", $ord);
	}
	return $line;
	}

	/**
	 * Generate DKIM signature
	 *
	 * @access public
	 * @param string $s Header
	 * @return string
	 */
	public function dkimSign($s)
	{
		$privKeyStr = file_get_contents($this->dkimPrivate);

		if ($this->dkimPassPhrase != '')
			$privKey = openssl_pkey_get_private($privKeyStr, $this->dkimPassPhrase);
		else
			$privKey = $privKeyStr;

		if (openssl_sign($s, $signature, $privKey))
			return base64_encode($signature);
	}

	/**
	 * Generate DKIM Canonicalization Header
	 *
	 * @access public
	 * @param string $s Header
	 * @return mixed|string
	 */
	public function dkimHeaderC($s)
	{
		$s = preg_replace("/\r\n\s+/", " ", $s);
		$lines = explode("\r\n", $s);

		foreach ($lines as $key => $line)
		{
			list($heading, $value) = explode(":", $line, 2);
			$heading = strtolower($heading);
			$value = preg_replace("/\s+/", " ", $value) ; // Compress useless spaces
			$lines[$key] = $heading.":".trim($value) ; // Don't forget to remove WSP around the value
		}

		$s = implode("\r\n", $lines);
		return $s;
	}

	/**
	 * Generate DKIM Canonicalization Body
	 *
	 * @access public
	 * @param string $body Message Body
	 * @return mixed|string
	 */
	public function dkimBodyC($body)
	{
		if ($body == '')
			return "\r\n";

		// stabilize line endings
		$body = str_replace("\r\n", "\n", $body);
		$body = str_replace("\n", "\r\n", $body);

		while (substr($body, strlen($body) - 4, 4) == "\r\n\r\n")
		{
			$body = substr($body, 0, strlen($body) - 2);
		}

		return $body;
	}

	/**
	 * Create the DKIM header, body, as new header
	 *
	 * @access public
	 * @param string $headersLine Header lines
	 * @param string $subject Subject
	 * @param string $body Body
	 * @return string
	 */
	public function dkimAdd($headersLine, $subject, $body)
	{
		$DKIMsignatureType      = 'rsa-sha1'; // Signature & hash algorithms
		$DKIMcanonicalization   = 'relaxed/simple'; // Canonicalization of header/body
		$DKIMquery              = 'dns/txt'; // Query method
		$DKIMtime               = time() ; // Signature Timestamp = seconds since 00:00:00 - Jan 1, 1970 (UTC time zone)
		$subjectHeader          = "Subject: $subject";
		$headers                = explode($this->lineEnding, $headersLine);

		foreach ($headers as $header)
		{
			if (strpos($header, 'From:') === 0)
			{
				$fromHeader = $header;
			}
			elseif (strpos($header, 'To:') === 0)
			{
				$toHeader = $header;
			}
		}

		$from      = str_replace('|', '=7C', $this->dkimQp($fromHeader));
		$to        = str_replace('|', '=7C', $this->dkimQp($toHeader));
		$subject   = str_replace('|', '=7C', $this->dkimQp($subjectHeader)) ; // Copied header fields (dkim-quoted-printable
		$body      = $this->dkimBodyC($body);
		$dkimLen   = strlen($body) ; // Length of body
		$dkimB64   = base64_encode(pack("H*", sha1($body))) ; // Base64 of packed binary SHA-1 hash of body
		$ident     = ($this->dkimIdentity == '')? '' : " i=" . $this->dkimIdentity . ";";
		$dkimhdrs  = "DKIM-Signature: v=1; a=" . $DKIMsignatureType . "; q=" . $DKIMquery . "; l=" . $dkimLen . "; s=" . $this->dkimSelector . ";\r\n".
				"\tt=" . $DKIMtime . "; c=" . $DKIMcanonicalization . ";\r\n".
				"\th=From:To:Subject;\r\n".
				"\td=" . $this->dkimDomain . ";" . $ident . "\r\n".
				"\tz=$from\r\n".
				"\t|$to\r\n".
				"\t|$subject;\r\n".
				"\tbh=" . $dkimB64 . ";\r\n".
				"\tb=";

		$toSign    = $this->dkimHeaderC($fromHeader . "\r\n" . $toHeader . "\r\n" . $subjectHeader . "\r\n" . $dkimhdrs);
		$signed    = $this->dkimSign($toSign);

		return "X-PHPMAILER-DKIM: phpmailer.worxware.com\r\n".$dkimhdrs.$signed."\r\n";
	}

	/**
	 * @param $isSent
	 * @param $to
	 * @param $cc
	 * @param $bcc
	 * @param $subject
	 * @param $body
	 */
	protected function doCallback($isSent, $to, $cc, $bcc, $subject, $body)
	{
		if (!empty($this->actionFunction) && function_exists($this->actionFunction))
		{
			$params = array($isSent, $to, $cc, $bcc, $subject, $body);
			call_user_func_array($this->actionFunction, $params);
		}
	}
}

/**
 *
 */
class phpMailerException extends Exception
{
	/**
	 * @return string
	 */
	public function errorMessage()
	{
		$errorMsg = '<strong>'.$this->getMessage()."</strong><br />\n";
		return $errorMsg;
	}
}
