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
 * PhpMailer - Php Pop Before Smtp Authentication Class
 * NOTE: Designed for use with PHP version 5 and up
 * @package PhpMailer
 * @author Andy Prevost
 * @author Marcus Bointon
 * @author Jim Jagielski
 * @copyright 2010 - 2012 Jim Jagielski
 * @copyright 2004 - 2009 Andy Prevost
 * @license http://www.gnu.org/copyleft/lesser.html Distributed under the Lesser General Public License (LGPL)
 * @version $Id: class.pop3.php 450 2010-06-23 16:46:33Z coolbru $
 */

/**
 * Pop Before Smtp Authentication Class
 * Version 5.2.1
 *
 * Author: Richard Davey (rich@corephp.co.uk)
 * Modifications: Andy Prevost
 * License: LGPL, see PhpMailer License
 *
 * Specifically for PhpMailer to allow Pop before Smtp authentication.
 * Does not yet work with APOP - if you have an APOP account, contact Richard Davey
 * and we can test changes to this script.
 *
 * This class is based on the structure of the Smtp class originally authored by Chris Ryan
 *
 * This class is rfc 1939 compliant and implements all the commands
 * required for Pop3 connection, authentication and disconnection.
 *
 * @package PhpMailer
 * @author Richard Davey
 */

class Pop3
{
	/**
	* Default Pop3 port
	* @var int
	*/
	public $pop3Port = 110;

	/**
	* Default Timeout
	* @var int
	*/
	public $pop3Timeout = 30;

	/**
	* Pop3 Carriage Return + Line Feed
	* @var string
	*/
	public $crlf = "\r\n";

	/**
	* Displaying Debug warnings? (0 = now, 1+ = yes)
	* @var int
	*/
	public $doDebug = 2;

	/**
	* Pop3 Mail Server
	* @var string
	*/
	public $host;

	/**
	* Pop3 Port
	* @var int
	*/
	public $port;

	/**
	* Pop3 Timeout Value
	* @var int
	*/
	public $tVal;

	/**
	* Pop3 Username
	* @var string
	*/
	public $userName;

	/**
	* Pop3 Password
	* @var string
	*/
	public $password;

	/**
	* Sets the Pop3 PhpMailer Version number
	* @var string
	*/
	public $version = '5.2.1';

	private $popConn;
	private $connected;
	private $error; // Error log array

	/**
	* Constructor, sets the initial values
	* @access public
	* @return Pop3
	*/
	function __construct()
	{
		$this->popConn = 0;
		$this->connected = false;
		$this->error     = null;
	}

	/**
	 * Combination of public events - connect, login, disconnect
	 *
	 * @param string   $host
	 * @param bool|int $port
	 * @param bool|int $tVal
	 * @param string   $username
	 * @param string   $password
	 * @param int      $debugLevel
	 *
	 * @return bool
	 */
	public function authorize($host, $port = false, $tVal = false, $username, $password, $debugLevel = 0)
	{
		$this->host = $host;

		// If no port value is passed, retrieve it
		if ($port == false)
			$this->port = $this->pop3Port;
		else
			$this->port = $port;

		// If no timeout value is passed, retrieve it
		if ($tVal == false)
			$this->tVal = $this->pop3Timeout;
		else
			$this->tVal = $tVal;

		$this->doDebug = $debugLevel;
		$this->userName = $username;
		$this->password = $password;

		// Refresh the error log
		$this->error = null;

		// Connect
		$result = $this->connect($this->host, $this->port, $this->tVal);

		if ($result)
		{
			$loginResult = $this->login($this->userName, $this->password);

			if ($loginResult)
			{
				$this->disconnect();
				return true;
			}
		}

		// We need to disconnect regardless if the login succeeded
		$this->disconnect();

		return false;
	}

	/**
	 * Connect to the Pop3 server
	 *
	 * @param string   $host
	 * @param bool|int $port
	 * @param integer  $tVal
	 * @return boolean
	 */
	public function connect($host, $port = false, $tVal = 30)
	{
		// Are we already connected?
		if ($this->connected)
			return true;

		// On Windows this will raise a Php Warning error if the hostname doesn't exist.
		// Rather than suppress it with @fsockopen, let's capture it cleanly instead
		set_error_handler(array(&$this, 'catchWarning'));

		// Connect to the Pop3 server
		$this->popConn = fsockopen($host, // Pop3 Host
					  $port,    // Port #
					  $errNo,   // Error Number
					  $errStr,  // Error Message
					  $tVal);   // Timeout (seconds)

		// Restore the error handler
		restore_error_handler();

		// Does the Error Log now contain anything?
		if ($this->error && $this->doDebug >= 1)
			$this->displayErrors();

		//  Did we connect?
		if ($this->popConn == false)
		{
			// It would appear not...
			$this->error = array(
				    'error' => "Failed to connect to server $host on port $port",
				    'errno' => $errNo,
				    'errstr' => $errStr
			);

			if ($this->doDebug >= 1)
				$this->displayErrors();

			return false;
		}

		// Increase the stream time-out

		// Check for PHP 4.3.0 or later
		if (version_compare(phpversion(), '5.0.0', 'ge'))
			stream_set_timeout($this->popConn, $tVal, 0);
		else
		{
			// Does not work on Windows
			if (substr(PHP_OS, 0, 3) !== 'WIN')
				socket_set_timeout($this->popConn, $tVal, 0);
		}

		// Get the Pop3 server response
		$Pop3Response = $this->getResponse();

		// Check for the +OK
		if ($this->checkResponse($Pop3Response))
		{
			// The connection is established and the Pop3 server is talking
			$this->connected = true;
			return true;
		}
	}

	/**
	 * Login to the Pop3 server (does not support APOP yet)
	 * @access public
	 * @param string $userName
	 * @param string $password
	 * @return boolean
	 */
	public function login($userName = '', $password = '')
	{
		if ($this->connected == false)
		{
			$this->error = 'Not connected to Pop3 server';

			if ($this->doDebug >= 1)
				$this->displayErrors();
		}

		if (empty($userName))
			$userName = $this->userName;

		if (empty($password))
			$password = $this->password;

		$PopUserName = "USER $userName".$this->crlf;
		$PopPassword = "PASS $password".$this->crlf;

		// Send the Username
		$this->sendString($PopUserName);
		$Pop3Response = $this->getResponse();

		if ($this->checkResponse($Pop3Response))
		{
			// Send the Password
			$this->sendString($PopPassword);
			$Pop3Response = $this->getResponse();

			if ($this->checkResponse($Pop3Response))
				return true;
			else
				return false;
		}
		else
			return false;
	}

	/**
	 * Disconnect from the Pop3 server
	 * @access public
	 */
	public function disconnect()
	{
		$this->sendString('QUIT');
		fclose($this->popConn);
	}

	/**
	 * Get the socket response back.
	 * $size is the maximum number of bytes to retrieve
	 * @access private
	 * @param integer $size
	 * @return string
	 */
	private function getResponse($size = 128)
	{
		$Pop3Response = fgets($this->popConn, $size);
		return $Pop3Response;
	}

	/**
	 * Send a string down the open socket connection to the POP3 server
	 * @access private
	 * @param string $string
	 * @return integer
	 */
	private function sendString($string)
	{
		$bytesSent = fwrite($this->popConn, $string, strlen($string));
		return $bytesSent;
	}

	/**
	 * Checks the Pop3 server response for +OK or -ERR
	 * @access private
	 * @param string $string
	 * @return boolean
	 */
	private function checkResponse($string)
	{
		if (substr($string, 0, 3) !== '+OK')
		{
			$this->error = array(
				    'error' => "Server reported an error: $string",
				    'errno' => 0,
				    'errstr' => ''
			);

			if ($this->doDebug >= 1)
				$this->displayErrors();

			return false;
		}
		else
			return true;
	}

	/**
	 * If debug is enabled, display the error message array
	 * @access private
	 */
	private function displayErrors()
	{
		echo '<pre>';

		foreach ($this->error as $singleError)
			print_r($singleError);

		echo '</pre>';
	}

	/**
	 * Takes over from PHP for the socket warning handler
	 *
	 * @access private
	 * @param integer $errNo
	 * @param string $errStr
	 * @param string $errFile
	 * @param integer $errLine
	 */
	private function catchWarning($errNo, $errStr, $errFile, $errLine)
	{
		$this->error[] = array(
			    'error' => "Connecting to the Pop3 server raised a PHP warning: ",
			    'errno' => $errNo,
			    'errstr' => $errStr
		);
	}
}
