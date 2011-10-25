<?php
/**
 * Modified version of Zend_Http_Client_Adapter Interface of Zend
 *
 * Copyright (c) 2005-2010, Zend Technologies USA, Inc.
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 * 
 *     * Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *
 *     * Redistributions in binary form must reproduce the above copyright notice,
 *       this list of conditions and the following disclaimer in the documentation
 *       and/or other materials provided with the distribution.
 * 
 *     * Neither the name of Zend Technologies USA, Inc. nor the names of its
 *       contributors may be used to endorse or promote products derived from this
 *       software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * An interface description for HttpClientAdapter classes.
 *
 * These classes are used as connectors for HttpClient, performing the
 * tasks of connecting, writing, reading and closing connection to the server.
 *
 */

interface HttpClientAdapterInterface
{
	/**
	 * Set the configuration array for the adapter
	 *
	 * @param array $config
	 */
	public function setConfig($config = array());

	/**
	 * Connect to the remote server
	 *
	 * @param string  $host
	 * @param int     $port
	 * @param boolean $secure
	 */
	public function connect($host, $port = 80, $secure = false);

	/**
	 * Send request to the remote server
	 *
	 * @param string        $method
	 * @param UriHttp       $url
	 * @param string        $http_ver
	 * @param array         $headers
	 * @param string        $body
	 * @return string Request as text
	 */
	public function write($method, $url, $http_ver = '1.1', $headers = array(), $body = '');

	/**
	 * Read response from server
	 *
	 * @return string
	 */
	public function read();

	/**
	 * Close the connection to the server
	 *
	 */
	public function close();
}
