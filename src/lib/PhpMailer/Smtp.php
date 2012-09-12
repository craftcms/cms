<?php

/*
.---------------------------------------------------------------------------.
|  Software: PhpMailer - PHP email class                                    |
|   Version: 5.2.1                                                          |
|      Site: https://code.google.com/a/apache-extras.org/p/phpmailer/       |
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
 * PhpMailer - Php Smtp email transport class
 * NOTE: Designed for use with PHP version 5 and up
 * @package PhpMailer
 * @author Andy Prevost
 * @author Marcus Bointon
 * @copyright 2004 - 2008 Andy Prevost
 * @author Jim Jagielski
 * @copyright 2010 - 2012 Jim Jagielski
 * @license http://www.gnu.org/copyleft/lesser.html Distributed under the Lesser General Public License (LGPL)
 * @version $Id: class.smtp.php 450 2010-06-23 16:46:33Z coolbru $
 */

/**
 * Smtp is rfc 821 compliant and implements all the rfc 821 Smtp commands except TURN which will always return a not implemented
 * error. Smtp also provides some utility methods for sending mail to an Smtp server.
 * original author: Chris Ryan
 */

class Smtp
{
	/**
	 * Smtp server port
	 * @var int
	 */
	public $smtpPort = 25;

	/**
	 * Smtp reply line ending
	 * @var string
	 */
	public $crlf = "\r\n";

	/**
	 * Sets whether debugging is turned on
	 * @var bool
	 */
	public $doDebug; // the level of debug to perform

	/**
	 * Sets VERP use on/off (default is off)
	 * @var bool
	 */
	public $doVerp = false;

	/**
	 * Sets the Smtp PhpMailer Version number
	 * @var string
	 */
	public $version = '5.2.1';

	private $smtpConn; // the socket to the server
	private $error; // error if any on the last call
	private $heloReply; // the reply the server sent to us for HELO

	/**
	 * Initialize the class so that the data is in a known state.
	 * @access public
	 * @return Smtp
	 */
	function __construct()
	{
		$this->smtpConn = 0;
		$this->error = null;
		$this->heloReply = null;
		$this->doDebug = 0;
	}

	/**
	 * Connect to the server specified on the port specified. If the port is not specified use the default SmtpPort.
	 * If tval is specified then a connection will try and be established with the server for that number of seconds.
	 * If tval is not specified the default is 30 seconds to try on the connection.
	 *
	 * SMTP CODE SUCCESS: 220
	 * SMTP CODE FAILURE: 421
	 *
	 * @param     $host
	 * @param int $port
	 * @param int $tVal
	 *
	 * @return bool
	 */
	public function connect($host, $port = 0, $tVal = 30)
	{
		// set the error val to null so there is no confusion
		$this->error = null;

		// make sure we are __not__ connected
		if ($this->connected())
		{
			// already connected, generate error
			$this->error = array("error" => "Already connected to a server");
			return false;
		}

		if (empty($port))
			$port = $this->smtpPort;

		// connect to the Smtp server
		$this->smtpConn = @fsockopen($host,    // the host of the server
									 $port,    // the port to use
									 $errNo,   // error number if any
									 $errStr,  // error message if any
									 $tVal);   // give up after ? secs

		// verify we connected properly
		if (empty($this->smtpConn))
		{
			$this->error = array("error" => "Failed to connect to server",
								 "errno" => $errNo,
								 "errstr" => $errStr);

			if ($this->doDebug >= 1)
				echo "SMTP -> ERROR: ".$this->error["error"].": $errStr ($errNo)".$this->crlf.'<br />';

			return false;
		}

		// Smtp server can take longer to respond, give longer timeout for first read Windows does not have support for this timeout function
		if (substr(PHP_OS, 0, 3) != "WIN")
			socket_set_timeout($this->smtpConn, $tVal, 0);

		// get any announcement
		$announce = $this->getLines();

		if($this->doDebug >= 2)
			echo "SMTP -> FROM SERVER:".$announce.$this->crlf.'<br />';

		return true;
	}

	/**
	 * Initiate a TLS communication with the server.
	 *
	 * SMTP CODE 220 Ready to start TLS
	 * SMTP CODE 501 Syntax error (no parameters allowed)
	 * SMTP CODE 454 TLS not available due to temporary reason
	 * @return bool success
	 */
	public function startTls()
	{
		$this->error = null; # to avoid confusion

		if(!$this->connected())
		{
			$this->error = array("error" => "Called StartTLS() without being connected");
			return false;
		}

		fputs($this->smtpConn,"STARTTLS" . $this->crlf);

		$rply = $this->getLines();
		$code = substr($rply, 0, 3);

		if ($this->doDebug >= 2)
			echo "SMTP -> FROM SERVER:" . $rply . $this->crlf . '<br />';

		if ($code != 220)
		{
			$this->error = array("error" => "STARTTLS not accepted from server", "smtp_code" => $code, "smtp_msg"  => substr($rply,4));

			if ($this->doDebug >= 1)
				echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->crlf . '<br />';

			return false;
		}

		// Begin encrypted connection
		if (!stream_socket_enable_crypto($this->smtpConn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT))
			return false;

		return true;
	}

	/**
	 * Performs Smtp authentication.  Must be run after running the
	 * Hello() method.  Returns true if successfully authenticated.
	 * @param $username
	 * @param $password
	 * @return bool
	 */
	public function authenticate($username, $password)
	{
		// Start authentication
		fputs($this->smtpConn,"AUTH LOGIN".$this->crlf);

		$rply = $this->getLines();
		$code = substr($rply,0,3);

		if ($code != 334)
		{
			$this->error = array("error" => "AUTH not accepted from server", "smtp_code" => $code, "smtp_msg" => substr($rply,4));

			if ($this->doDebug >= 1)
				echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->crlf . '<br />';

			return false;
		}

		// Send encoded username
		fputs($this->smtpConn, base64_encode($username).$this->crlf);

		$rply = $this->getLines();
		$code = substr($rply, 0, 3);

		if ($code != 334)
		{
			$this->error = array("error" => "Username not accepted from server", "smtp_code" => $code, "smtp_msg" => substr($rply,4));

			if ($this->doDebug >= 1)
				echo "SMTP -> ERROR: ".$this->error["error"].": ".$rply.$this->crlf.'<br />';

			return false;
		}

		// Send encoded password
		fputs($this->smtpConn, base64_encode($password).$this->crlf);

		$rply = $this->getLines();
		$code = substr($rply, 0, 3);

		if ($code != 235)
		{
			$this->error = array("error" => "Password not accepted from server", "smtp_code" => $code, "smtp_msg" => substr($rply,4));

			if ($this->doDebug >= 1)
				echo "SMTP -> ERROR: ".$this->error["error"].": ".$rply.$this->crlf.'<br />';

			return false;
		}

		return true;
	}

	/**
	 * Returns true if connected to a server otherwise false
	 * @access public
	 * @return bool
	 */
	public function connected()
	{
		if (!empty($this->smtpConn))
		{
			$sockStatus = socket_get_status($this->smtpConn);

			if ($sockStatus["eof"])
			{
				// the socket is valid but we are not connected
				if ($this->doDebug >= 1)
					echo "SMTP -> NOTICE:".$this->crlf."EOF caught while checking if connected";

				$this->close();

				return false;
			}

			return true; // everything looks good
		}

		return false;
	}

	/**
	 * Closes the socket and cleans up the state of the class. It is not considered good to use this function without
	 * first trying to use QUIT.
	 * @access public
	 * @return void
	 */
	public function close()
	{
		$this->error = null; // so there is no confusion
		$this->heloReply = null;

		if (!empty($this->smtpConn))
		{
			// close the connection and cleanup
			fclose($this->smtpConn);
			$this->smtpConn = 0;
		}
	}

	/**
	 * Issues a data command and sends the msg_data to the server finializing the mail transaction. $msgData is the message
	 * that is to be send with the headers. Each header needs to be on a single line followed by a <CRLF> with the message headers
	 * and the message body being seperated by and additional <CRLF>.
	 *
	 * Implements rfc 821: DATA <CRLF>
	 *
	 * SMTP CODE INTERMEDIATE: 354
	 *     [data]
	 *     <CRLF>.<CRLF>
	 *     SMTP CODE SUCCESS: 250
	 *     SMTP CODE FAILURE: 552,554,451,452
	 * SMTP CODE FAILURE: 451,554
	 * SMTP CODE ERROR  : 500,501,503,421
	 *
	 * @param $msgData
	 * @return bool
	 */
	public function data($msgData)
	{
		$this->error = null; // so no confusion is caused

		if (!$this->connected())
		{
			$this->error = array("error" => "Called data() without being connected");
			return false;
		}

		fputs($this->smtpConn, "DATA".$this->crlf);

		$rply = $this->getLines();
		$code = substr($rply, 0, 3);

		if ($this->doDebug >= 2)
			echo "SMTP -> FROM SERVER:" . $rply . $this->crlf . '<br />';

		if ($code != 354)
		{
			$this->error = array("error" => "DATA command not accepted from server", "smtp_code" => $code, "smtp_msg" => substr($rply,4));

			if ($this->doDebug >= 1)
				echo "SMTP -> ERROR: ".$this->error["error"].": ".$rply.$this->crlf.'<br />';

			return false;
		}

		/* The server is ready to accept data. According to rfc 821 we should not send more than 1000 (including the CRLF)
		 * characters on a single line so we will break the data up into lines by \r and/or \n then if needed we will break
		 * each of those into smaller lines to fit within the limit. In addition we will be looking for lines that start with
		 * a period '.' and append and additional period '.' to that line. NOTE: this does not count towards limit.
		 */

		// normalize the line breaks so we know the explode works
		$msgData = str_replace("\r\n", "\n", $msgData);
		$msgData = str_replace("\r", "\n", $msgData);
		$lines = explode("\n", $msgData);

		/* We need to find a good way to determine is headers are in the msgData or if it is a straight msg body
		 * currently I am assuming rfc 822 definitions of msg headers and if the first field of the first line (':' separated)
		 * does not contain a space then it _should_ be a header and we can process all lines before a blank "" line as
		 * headers.
		 */

		$field = substr($lines[0],0,strpos($lines[0],":"));
		$inHeaders = false;

		if (!empty($field) && !strstr($field," "))
			$inHeaders = true;

		$maxLineLength = 998; // used below; set here for ease in change

		while (list(, $line) = @each($lines))
		{
			$linesOut = null;

			if ($line == "" && $inHeaders)
				$inHeaders = false;

			// ok we need to break this line up into several smaller lines
			while (strlen($line) > $maxLineLength)
			{
				$pos = strrpos(substr($line, 0, $maxLineLength), " ");

				// Patch to fix DOS attack
				if (!$pos)
				{
					$pos = $maxLineLength - 1;
					$linesOut[] = substr($line, 0, $pos);
					$line = substr($line, $pos);
				}
				else
				{
					$linesOut[] = substr($line, 0, $pos);
					$line = substr($line, $pos + 1);
				}

				// if processing headers add a LWSP-char to the front of new line rfc 822 on long msg headers
				if ($inHeaders)
					$line = "\t" . $line;
			}

			$linesOut[] = $line;

			// send the lines to the server
			while (list(, $line_out) = @each($linesOut))
			{
				if (strlen($line_out) > 0)
				{
					if (substr($line_out, 0, 1) == ".")
						$line_out = ".".$line_out;
				}

				fputs($this->smtpConn, $line_out.$this->crlf);
			}
		}

		// message data has been sent
		fputs($this->smtpConn, $this->crlf.".".$this->crlf);

		$rply = $this->getLines();
		$code = substr($rply, 0, 3);

		if ($this->doDebug >= 2)
			echo "SMTP -> FROM SERVER:".$rply.$this->crlf.'<br />';

		if ($code != 250)
		{
			$this->error = array("error" => "DATA not accepted from server", "smtp_code" => $code, "smtp_msg" => substr($rply,4));

			if ($this->doDebug >= 1)
				echo "SMTP -> ERROR: ".$this->error["error"].": ".$rply.$this->crlf.'<br />';

			return false;
		}

		return true;
	}

	/**
	 * Sends the HELO command to the smtp server. This makes sure that we and the server are in the same known state.
	 * Implements from rfc 821: HELO <SP> <domain> <CRLF>
	 *
	 * SMTP CODE SUCCESS: 250
	 * SMTP CODE ERROR  : 500, 501, 504, 421
	 *
	 * @param string $host
	 * @return bool
	 */
	public function hello($host = '')
	{
		$this->error = null; // so no confusion is caused

		if (!$this->connected())
		{
			$this->error = array("error" => "Called Hello() without being connected");
			return false;
		}

		// if hostname for HELO was not specified send default
		if (empty($host))
		{
			// determine appropriate default to send to server
			$host = "localhost";
		}

		// Send extended hello first (RFC 2821)
		if (!$this->sendHello("EHLO", $host))
		{
			if (!$this->sendHello("HELO", $host))
				return false;
		}

		return true;
	}

	/**
	 * Sends a HELO/EHLO command.
	 * @access private
	 * @param $hello
	 * @param $host
	 * @return bool
	 */
	private function sendHello($hello, $host)
	{
		fputs($this->smtpConn, $hello." ".$host.$this->crlf);

		$rply = $this->getLines();
		$code = substr($rply, 0, 3);

		if ($this->doDebug >= 2)
			echo "SMTP -> FROM SERVER: ".$rply.$this->crlf.'<br />';

		if ($code != 250)
		{
			$this->error = array("error" => $hello." not accepted from server", "smtp_code" => $code, "smtp_msg" => substr($rply,4));

			if ($this->doDebug >= 1)
				echo "SMTP -> ERROR: ".$this->error["error"].": ".$rply.$this->crlf.'<br />';

			return false;
		}

		$this->heloReply = $rply;

		return true;
	}

	/**
	 * Starts a mail transaction from the email address specified in $from. Returns true if successful or false otherwise. If True
	 * the mail transaction is started and then one or more Recipient commands may be called followed by a Data command.
	 *
	 * Implements rfc 821: MAIL <SP> FROM:<reverse-path> <CRLF>
	 *
	 * SMTP CODE SUCCESS: 250
	 * SMTP CODE SUCCESS: 552,451,452
	 * SMTP CODE SUCCESS: 500,501,421
	 *
	 * @param $from
	 * @return bool
	 */
	public function mail($from)
	{
		$this->error = null; // so no confusion is caused

		if (!$this->connected())
		{
			$this->error = array("error" => "Called Mail() without being connected");
			return false;
		}

		$useVerp = ($this->doVerp ? "XVERP" : "");
		fputs($this->smtpConn,"MAIL FROM:<".$from.">".$useVerp.$this->crlf);

		$rply = $this->getLines();
		$code = substr($rply, 0, 3);

		if ($this->doDebug >= 2)
			echo "SMTP -> FROM SERVER:".$rply.$this->crlf.'<br />';

		if ($code != 250)
		{
			$this->error = array("error" => "MAIL not accepted from server", "smtp_code" => $code, "smtp_msg" => substr($rply,4));

			if ($this->doDebug >= 1)
				echo "SMTP -> ERROR: ".$this->error["error"].": ".$rply.$this->crlf.'<br />';

			return false;
		}

		return true;
	}

	/**
	 * Sends the quit command to the server and then closes the socket if there is no error or the $close_on_error argument is true.
	 * Implements from rfc 821: QUIT <CRLF>
	 *
	 * SMTP CODE SUCCESS: 221
	 * SMTP CODE ERROR  : 500
	 *
	 * @param bool $closeOnError
	 * @return bool
	 */
	public function quit($closeOnError = true)
	{
		$this->error = null; // so there is no confusion

		if (!$this->connected())
		{
			$this->error = array("error" => "Called Quit() without being connected");
			return false;
		}

		// send the quit command to the server
		fputs($this->smtpConn, "quit".$this->crlf);

		// get any good-bye messages
		$byeMsg = $this->getLines();

		if ($this->doDebug >= 2)
			echo "SMTP -> FROM SERVER:".$byeMsg.$this->crlf.'<br />';

		$rVal = true;
		$e = null;

		$code = substr($byeMsg, 0, 3);

		if ($code != 221)
		{
			// use e as a tmp var cause Close will overwrite $this->error
			$e = array("error" => "Smtp server rejected quit command", "smtp_code" => $code, "smtp_rply" => substr($byeMsg,4));
			$rVal = false;

			if ($this->doDebug >= 1)
				echo "SMTP -> ERROR: ".$e["error"].": ".$byeMsg.$this->crlf.'<br />';
		}

		if (empty($e) || $closeOnError)
			$this->close();

		return $rVal;
	}

	/**
	 * Sends the command RCPT to the Smtp server with the TO: argument of $to. Returns true if the recipient was accepted false if it was rejected.
	 * Implements from rfc 821: RCPT <SP> TO:<forward-path> <CRLF>
	 *
	 * SMTP CODE SUCCESS: 250,251
	 * SMTP CODE FAILURE: 550,551,552,553,450,451,452
	 * SMTP CODE ERROR  : 500,501,503,421
	 *
	 * @param $to
	 * @return bool
	 */
	public function recipient($to)
	{
		$this->error = null; // so no confusion is caused

		if (!$this->connected())
		{
			$this->error = array("error" => "Called Recipient() without being connected");
			return false;
		}

		fputs($this->smtpConn, "RCPT TO:<".$to.">".$this->crlf);

		$rply = $this->getLines();
		$code = substr($rply, 0, 3);

		if ($this->doDebug >= 2)
			echo "SMTP -> FROM SERVER:".$rply.$this->crlf.'<br />';

		if ($code != 250 && $code != 251)
		{
			$this->error = array("error" => "RCPT not accepted from server", "smtp_code" => $code, "smtp_msg" => substr($rply,4));

			if ($this->doDebug >= 1)
				echo "SMTP -> ERROR: ".$this->error["error"].": ".$rply.$this->crlf.'<br />';

			return false;
		}

		return true;
	}

	/**
	 * Sends the RSET command to abort and transaction that is currently in progress. Returns true if successful false
	 * otherwise.
	 *
	 * Implements rfc 821: RSET <CRLF>
	 *
	 * SMTP CODE SUCCESS: 250
	 * SMTP CODE ERROR  : 500,501,504,421
	 * @return bool
	 */
	public function reset()
	{
		$this->error = null; // so no confusion is caused

		if (!$this->connected())
		{
			$this->error = array("error" => "Called Reset() without being connected");
			return false;
		}

		fputs($this->smtpConn,"RSET".$this->crlf);

		$rply = $this->getLines();
		$code = substr($rply, 0, 3);

		if ($this->doDebug >= 2)
			echo "SMTP -> FROM SERVER:".$rply.$this->crlf.'<br />';

		if ($code != 250)
		{
			$this->error = array("error" => "RSET failed", "smtp_code" => $code, "smtp_msg" => substr($rply,4));

			if ($this->doDebug >= 1)
				echo "SMTP -> ERROR: " . $this->error["error"] . ": " . $rply . $this->crlf . '<br />';

			return false;
		}

		return true;
	}

	/**
	 * Starts a mail transaction from the email address specified in $from. Returns true if successful or false otherwise. If True
	 * the mail transaction is started and then one or more Recipient commands may be called followed by a Data command. This command
	 * will send the message to the users terminal if they are logged in and send them an email.
	 *
	 * Implements rfc 821: SAML <SP> FROM:<reverse-path> <CRLF>
	 *
	 * SMTP CODE SUCCESS: 250
	 * SMTP CODE SUCCESS: 552,451,452
	 * SMTP CODE SUCCESS: 500,501,502,421
	 *
	 * @param $from
	 * @return bool
	 */
	public function sendAndMail($from)
	{
		$this->error = null; // so no confusion is caused

		if (!$this->connected())
		{
			$this->error = array("error" => "Called SendAndMail() without being connected");
			return false;
		}

		fputs($this->smtpConn, "SAML FROM:".$from.$this->crlf);

		$rply = $this->getLines();
		$code = substr($rply, 0, 3);

		if ($this->doDebug >= 2)
			echo "SMTP -> FROM SERVER:" . $rply . $this->crlf . '<br />';

		if ($code != 250)
		{
			$this->error = array("error" => "SAML not accepted from server", "smtp_code" => $code, "smtp_msg" => substr($rply,4));

			if ($this->doDebug >= 1)
				echo "SMTP -> ERROR: ".$this->error["error"].": ".$rply.$this->crlf.'<br />';

			return false;
		}

		return true;
	}

	/**
	 * This is an optional command for Smtp that this class does not support. This method is here to make the RFC821 Definition
	 * complete for this class and __may__ be implimented in the future
	 *
	 * Implements from rfc 821: TURN <CRLF>
	 *
	 * SMTP CODE SUCCESS: 250
	 * SMTP CODE FAILURE: 502
	 * SMTP CODE ERROR  : 500, 503
	 * @return bool
	 */
	public function turn()
	{
		$this->error = array("error" => "This method, TURN, of the Smtp is not implemented");

		if ($this->doDebug >= 1)
			echo "SMTP -> NOTICE: ".$this->error["error"].$this->crlf.'<br />';

		return false;
	}

	/**
	 * Get the current error
	 * @return array
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * Read in as many lines as possible either before eof or socket timeout occurs on the operation.
	 * With Smtp we can tell if we have more lines to read if the 4th character is '-' symbol. If it is a space then we don't
	 * need to read anything else.
	 * @access private
	 * @return string
	 */
	private function getLines()
	{
		$data = "";
		while (!feof($this->smtpConn))
		{
			$str = @fgets($this->smtpConn, 515);

			if ($this->doDebug >= 4)
			{
				echo "SMTP -> get_lines(): \$data was \"$data\"".$this->crlf.'<br />';
				echo "SMTP -> get_lines(): \$str is \"$str\"".$this->crlf.'<br />';
			}

			$data .= $str;

			if ($this->doDebug >= 4)
				echo "SMTP -> get_lines(): \$data is \"$data\"".$this->crlf.'<br />';

			// if 4th character is a space, we are done reading, break the loop
			if (substr($str,3,1) == " ")
				break;

		}

		return $data;
	}
}
