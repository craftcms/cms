<?php
/*********************************************************************************
Name: HelpSpotAPI Wrapper version 1.1
Author: Joe Landsman (joe.landsman@dnlomnimedia.com), DNL OmniMedia Inc.
Copyright: UserScape Inc
License for HelpSpotAPI: MIT License, http://www.opensource.org/licenses/mit-license.php
License for XML Parser by Keith Devens: http://keithdevens.com/software/license
Date: 1/14/2009
Desc: A fully implemented php wrapper class for the HelpSpot API.
Requires cURL & XML support. Includes Keith Devens' XML serializer/unserializer.

See Helpspot knowledgebook for API methods and attribute definitions:
http://www.userscape.com/helpdesk/index.php?pg=kb.book&id=6

************************************* NOTES *************************************
* The API Wrapper returns PHP array's (When experimenting with the class it can be helpful to use print_r to see the structure of the returned array)
* Methods always use an array for parameters
* Errors are stored in a string parameter of the API class ->errors
* Only GET requests can be cached, POST are never cached
* The default cache storage location can be set by passing in cacheDir as a parameter to HelpSpotAPI()

************************************ EXAMPLES ***********************************
MOST BASIC USAGE
Desc: Make a public API call which returns an array with the current API version installed.

	include('HelpSpotAPI.php');
	$hsapi = new HelpSpotAPI(array("helpSpotApiURL" => "http://.../api/index.php"));
	$result = $hsapi->version();

	print_r($result); //show returned array

PUBLIC REQUEST CREATION
Desc: Create a request via the public api which does not require a password. This is the same as creating a request via the portal.

	include('HelpSpotAPI.php');
	$hsapi = new HelpSpotAPI(array("helpSpotApiURL" => "http://.../api/index.php"));
	$result = $hsapi->requestCreate(array(
					'sFirstName' => 'Bob',
					'sLastName' => 'Jones',
					'sEmail' => 'bjones@company.com',
					'tNote' => 'This is a test note'
				));

PRIVATE REQUEST CREATING
Desc: This uses the private API to create a request which opens up many more possibilities for populating fields, assigning the request and more

	include('HelpSpotAPI.php');
	$hsapi = new HelpSpotAPI(array(
								'helpSpotApiURL' => 'http://.../api/index.php',
								'username' => 'todd@company.com',
								'password' => 'pass'
							));
	$result = $hsapi->privateRequestCreate(array(
								'sFirstName' => 'Bob',
								'sLastName' => 'Jones',
								'sEmail' => 'bjones@company.com',
								'tNote' => 'This is a test note',
								'xCategory' => 1,
								'xPersonAssignedTo' => 0
							));

SEARCHING FOR REQUESTS
Desc: Search for requests. It's also possible to retrieve filters of requests as well, see docs for details.

	include('HelpSpotAPI.php');
	$hsapi = new HelpSpotAPI(array(
								'helpSpotApiURL' => 'http://.../api/index.php',
								'username' => 'todd@company.com',
								'password' => 'pass'
							));
	$result = $hsapi->privateRequestSearch(array(
								'sUserId' => '76489',		//Customer ID to search for
								'relativedate' => 'past_7'	//Limits results to requests created in the past 7 days
							));

USING CACHING
Desc: In this example we're going to search for requests and cache the results of the search for 30 minutes.
* Note the cache defaults to 15 minutes if caching is used and no value is set for cacheTTL

	include('HelpSpotAPI.php');
	$hsapi = new HelpSpotAPI(array(
								'helpSpotApiURL' => 'http://.../api/index.php',
								'username' => 'todd@company.com',
								'password' => 'pass',
								'cacheDir' => '/tmp'		//Directory to place cache files in
							));
	$result = $hsapi->privateRequestSearch(array(
								'sUserId' => '76489',
								'relativedate' => 'past_7',
								'cacheRequest' => true,		//Enable caching of the results
								'cacheTTL' => 1800			//Time to cache results in seconds
							));

*********************************************************************************/

class HelpSpotAPI {
	/*** Vars ***/
	// General vars
	var $helpSpotApiURL			= '';	// URL of HelpSpot API
	var $username				= '';	// Username - used to access private API methods
	var $password				= '';	// Password - also used to access private API methods

	// Request vars
	var $queryString			= '';	// Query string used to call API (ie. ?method=version)
	var $callTimeout			= 10;	// Timeout for cURL request - in seconds (0 is indefinite!!!)
	var $httpRequestPost		= null;	// cURL will perform a POST request if this is set to TRUE

	// Caching vars
	var $cacheEngine			= null;	// Custom object to handle caching

	// Debug & misc vars
	var $errors				= '';	// Current error message string

	/*** Constructor ***/
	function __construct($params=array()) {
		// Vars
		$return 						= true;

		// HelpSpot API obj vars init
		$this->helpSpotApiURL 			= isset($params['helpSpotApiURL']) 		? $params['helpSpotApiURL'] : '';
		$this->username 				= isset($params['username'])			? $params['username'] : '';
		$this->password 				= isset($params['password']) 			? $params['password'] : '';
		$this->callTimeout 				= isset($params['callTimeout']) && is_int($params['callTimeout']) 		? $params['callTimeout'] : 10;
		$this->httpRequestPost 			= isset($params['httpRequestPost']) && is_bool($params['httpRequestPost']) 	? $params['httpRequestPost'] : false;
		$this->errors					= '';

		// Instantiate new cache engine
		$this->cacheEngine = new HelpSpotApiCacheEngine();
		$this->cacheEngine->cacheDir 	= isset($params['cacheDir']) ? $params['cacheDir'] : $this->_tmp_dir();

		// Check for cURL lib support
		if( !function_exists('curl_init') )
		{
			$this->errors .= "Error: cURL support is required.\n";
		}

		// Check for XML lib support
		if( !function_exists('xml_parser_create') )
		{
			$this->errors .= "Error: XML support is required.\n";
		}
	}

	/*** Public methods ***/
	// Get requests
	function customerGetRequests($params=array()) {
		$params['method'] = 'customer.getRequests';
		return $this->request( $params );
	}

	// Create request
	function requestCreate($params=array()) {
		$this->httpRequestPost = true;	// force POST request for this call
		$params['method'] = 'request.create';
		return $this->request( $params );
	}

	// Update request
	function requestUpdate($params=array()) {
		$this->httpRequestPost = true;	// force POST request for this call
		$params['method'] = 'request.update';
		return $this->request( $params );
	}

	// Get categories
	function requestGetCategories($params=array()) {
		$params['method'] = 'request.getCategories';
		return $this->request( $params );
	}

	// Get custom fields
	function requestGetCustomFields ($params=array()) {
		$params['method'] = 'request.getCustomFields';
		return $this->request( $params );
	}

	// Get request
	function requestGet($params=array()) {
		$params['method'] = 'request.get';
		return $this->request( $params );
	}

	// Get list of public forums
	function forumsList($params=array()) {
		$params['method'] = 'forums.list';
		return $this->request( $params );
	}

	// Get information on a forum
	function forumsGet($params=array()) {
		$params['method'] = 'forums.get';
		return $this->request( $params );
	}

	// Get list of topics from a given forum
	function forumsGetTopics($params=array()) {
		$params['method'] = 'forums.getTopics';
		return $this->request( $params );
	}

	// Return list of posts from a given topic
	function forumsGetPosts($params=array()) {
		$params['method'] = 'forums.getPosts';
		return $this->request( $params );
	}

	// Create a new forum topic
	function forumsCreateTopic($params=array()) {
		$this->httpRequestPost = true;	// force POST request for this call
		$params['method'] = 'forums.createTopic';
		return $this->request( $params );
	}

	// Create a new post in the specified topic
	function forumsCreatePost($params=array()) {
		$this->httpRequestPost = true;	// force POST request for this call
		$params['method'] = 'forums.createPost';
		return $this->request( $params );
	}

	// Return list of topics that match search string
	function forumsSearch($params=array()) {
		$params['method'] = 'forums.search';
		return $this->request( $params );
	}

	// Return list of all public knowledgebooks
	function kbList($params=array()) {
		$params['method'] = 'kb.list';
		return $this->request( $params );
	}

	// Return information on a knowledgebook
	function kbGet($params=array()) {
		$params['method'] = 'kb.get';
		return $this->request( $params );
	}

	// Return a knowledge books table of contents. Can optionally include the full HTML of each page of the book.
	function kbGetBookTOC($params=array()) {
		$params['method'] = 'kb.getBookTOC';
		return $this->request( $params );
	}

	// Return information on a knowledge book page.
	function kbGetPage($params=array()) {
		$params['method'] = 'kb.getPage';
		return $this->request( $params );
	}

	// Return a list of pages that match the search string.
	function kbSearch($params=array()) {
		$params['method'] = 'kb.search';
		return $this->request( $params );
	}

	// Adds a vote for this page as "helpful"
	function kbVoteHelpful($params=array()) {
		$params['method'] = 'kb.voteHelpful';
		return $this->request( $params );
	}

	// Adds a vote for this page as "not helpful"
	function kbVoteNotHelpful($params=array()) {
		$params['method'] = 'kb.voteNotHelpful';
		return $this->request( $params );
	}

	// Returns a list of field labels for use in your interfaces.
	function utilGetFieldLabels($params=array()) {
		$params['method'] = 'util.getFieldLabels';
		return $this->request( $params );
	}

	// Return current & min-version of API
	function version($params=array()) {
		$params['method'] = 'version';
		return $this->request( $params );
	}



	/*** Private methods ***/
	// Return current & min-version of API
	function privateVersion($params=array()) {
		$params['method'] = 'private.version';
		return $this->request( $params );
	}

	// Retrieve the portal password for an email or create a portal password for an email if that email doesn't have a password yet.
	function privateCustomerGetPasswordByEmail($params=array()) {
		$params['method'] = 'private.customer.getPasswordByEmail';
		return $this->request( $params );
	}

	// Set the portal password for an email account.
	function privateCustomerSetPasswordByEmail($params=array()) {
		$params['method'] = 'private.customer.setPasswordByEmail';
		return $this->request( $params );
	}

	// Create a new request.
	function privateRequestCreate($params=array()) {
		$this->httpRequestPost = true;	// force POST request for this call
		$params['method'] = 'private.request.create';
		return $this->request( $params );
	}

	// Update an existing request.
	function privateRequestUpdate($params=array()) {
		$this->httpRequestPost = true;	// force POST request for this call
		$params['method'] = 'private.request.update';
		return $this->request( $params );
	}

	// Return all information on a request.
	function privateRequestGet($params=array()) {
		$params['method'] = 'private.request.get';
		return $this->request( $params );
	}

	// Search for requests.
	function privateRequestSearch($params=array()) {
		$params['method'] = 'private.request.search';
		return $this->request( $params );
	}

	// Add a time tracker time event to a request.
	function privateRequestAddTimeEvent($params=array()) {
		$this->httpRequestPost = true;	// force POST request for this call
		$params['method'] = 'private.request.addTimeEvent';
		return $this->request( $params );
	}

	// Delete a time tracker time event.
	function privateRequestDeleteTimeEvent($params=array()) {
		$this->httpRequestPost = true;	// force POST request for this call
		$params['method'] = 'private.request.deleteTimeEvent';
		return $this->request( $params );
	}

	// Return all time tracker time events from a request.
	function privateRequestGetTimeEvents($params=array()) {
		$params['method'] = 'private.request.getTimeEvents';
		return $this->request( $params );
	}

	// Returns a list of all categories along with each categories related information such as reporting tags.
	function privateRequestGetCategories($params=array()) {
		$params['method'] = 'private.request.getCategories';
		return $this->request( $params );
	}

	// Returns a list of mailboxes.
	function privateRequestGetMailboxes($params=array()) {
		$params['method'] = 'private.request.getMailboxes';
		return $this->request( $params );
	}

	// Returns a list of status types.
	function privateRequestGetStatusTypes($params=array()) {
		$params['method'] = 'private.request.getStatusTypes';
		return $this->request( $params );
	}

	// Returns a list of custom fields.
	function privateRequestGetCustomFields($params=array()) {
		$params['method'] = 'private.request.getCustomFields';
		return $this->request( $params );
	}

	// Merge two requests.
	function privateRequestMerge($params=array()) {
		$this->httpRequestPost = true;	// force POST request for this call
		$params['method'] = 'private.request.merge';
		return $this->request( $params );
	}

	// Return the results of a users filter.
	function privateFilterGet($params=array()) {
		$params['method'] = 'private.filter.get';
		return $this->request( $params );
	}

	// Return column names for the fields returned in filters.
	function privateFilterGetColumnNames($params=array()) {
		$params['method'] = 'private.filter.getColumnNames';
		return $this->request( $params );
	}

	// Return time events based on search criteria.
	function privateTimetrackerSearch($params=array()) {
		$params['method'] = 'private.timetracker.search';
		return $this->request( $params );
	}

	// Return the authenticated users filters.
	function privateUserGetFilters($params=array()) {
		$params['method'] = 'private.user.getFilters';
		return $this->request( $params );
	}

	// Return the authenticated users preferences.
	function privateUserPreferences($params=array()) {
		$params['method'] = 'private.user.preferences';
		return $this->request( $params );
	}

	// Return all currently active staff in the system and their information.
	function privateUtilGetActiveStaff($params=array()) {
		$params['method'] = 'private.util.getActiveStaff';
		return $this->request( $params );
	}

	// Create an address book contact
	function privateAddressbookCreateContact($params=array()) {
		$this->httpRequestPost = true;	// force POST request for this call
		$params['method'] = 'private.addressbook.createContact';
		return $this->request( $params );
	}

	// Delete an address book contact
	function privateAddressbookDeleteContact($params=array()) {
		$this->httpRequestPost = true;	// force POST request for this call
		$params['method'] = 'private.addressbook.deleteContact';
		return $this->request( $params );
	}

	// Get all contacts
	function privateAddressbookGetContacts($params=array()) {
		$params['method'] = 'private.addressbook.getContacts';
		return $this->request( $params );
	}

	/*** Requester ***/
	// Execute a request to the API - does most of the heavy lifting
	function request($requestParams) {
		// Init vars
		$return = false;																// Preset return value
		$requestParams['output'] = 'xml';													// Force return type to XML - only supported return type
		$cacheRequest = isset($requestParams['cacheRequest']) && is_bool($requestParams['cacheRequest'])	// Should we cache this request? (default false)
						? $requestParams['cacheRequest'] : false;
		$cacheTTL = isset($requestParams['cacheTTL']) && is_int($requestParams['cacheTTL'])					// What is the TTL for this cached item? (default 15 minutes)
						? $requestParams['cacheTTL'] : 900;

		// Clean up after we've set the locally scoped $cacheRequest & $cacheTTL vars
		unset($requestParams['cacheRequest']);												// Kill this request param - it shouldn't be sent to API
		unset($requestParams['cacheTTL']);													// Kill this request param - it shouldn't be sent to API

		// Form up query string
		$this->queryString = implode('&', array_map( array("HelpSpotAPI", "implodeQueryParams"),
										array_keys($requestParams),
										array_values($requestParams) ) ) ;

		// If caching enabled for this call and cached version is available
		if( $cacheRequest && $this->cacheEngine->isCached($this->username.$this->helpSpotApiURL.$this->queryString, $cacheTTL) )
		{
			// If we can't fetch the cached content for some reason then update error string and return value
			if( !($return = $this->cacheEngine->getCached($this->username.$this->helpSpotApiURL.$this->queryString)) ) {
				$this->errors = $this->cacheEngine->errors;
				$return = false;
			}
		}
		// Else cache not enabled or cached content not available
		else
		{
			// Make API call and unserialize the returned XML
			$apiResponse = $this->makeApiCall();
			$return = HS_XML_unserialize($apiResponse);

			// Vars
			$firstDimKey = '';															// First dimension result key
			$secondDimKey = '';															// Second dimension result key

			// Get keys of 1st & 2nd dimension of array - used later to determine if a single item is returned in 3rd-dim of array.
			// If so, we will convert 3-dim array to 4-dim array to normalize output as multi-item returns are returned as 4-dim arrays.
			$firstDimKey = array_keys($return);
			$firstDimKey = $firstDimKey[0];

			if(isset($return[$firstDimKey]) && is_array($return[$firstDimKey])){
				$secondDimKey = array_keys($return[$firstDimKey]);
				$secondDimKey = $secondDimKey[0];
			}

			// If 3rd-dim is array, but not numerically indexed, then we've returned a single-item. Restructure array to 4-dims to normalize output.
			if( 	isset($return[$firstDimKey][$secondDimKey]) && is_array($return[$firstDimKey][$secondDimKey])
					&& !isset($return[$firstDimKey][$secondDimKey][0]) )
			{
				// Modify output data structure. Change return into 4-dim array (the way multi-item returns are returned - normalizes return data)
				$return[key($return)][key($return[key($return)])] = array(0=>$return[key($return)][key($return[key($return)])]);
			}

			// If errors returned then update error string (and don't cache content - regardless of $cacheRequests status)
			if( isset($return['errors']) && is_array($return['errors']) )
			{
				// if there were multiple errors (3-dim array = multiple errors)
				if( is_array($return['errors']['error'][0]) )
				{
					// Foreach error that occured concat error plus newline to this obj's error string
					foreach($return['errors']['error'] as $e)
					{
						$this->errors .= "Error #$e[id] - $e[description]\n";
					}
				}
				// Else there was only a single error (2-dim array = single error)
				else
				{	// Concat error plus newline to this obj's error string
					$this->errors .= "Error #".$return['errors']['error']['id']." - ".$return['errors']['error']['description']."\n";
				}
				$return = false;
			}
			// If caching enabled and this is not a POST request (never cache HTTP POST results) and there WAS an error caching the content.
			elseif( 	$cacheRequest
					&& !$this->httpRequestPost
					&& !$this->cacheEngine->cache($this->username.$this->helpSpotApiURL.$this->queryString, $return) )
			{
				// Get cacheEngine error and set to this obj's error string
				$this->errors = $this->cacheEngine->errors;
				$return = false;
			}
		}

		// Reset this var after $this->makeApiCall() - HS uses GET for majority of calls
		$this->httpRequestPost = false;


		// Return results
		return $return;
	}

	/*** Helper functions ***/
	// Make cURL call to API
	function makeApiCall() {
		$return = false;

		// If URL is set then perform cURL call
		if( strlen($this->helpSpotApiURL) )
		{
			$curl = curl_init();													// Init cURL call
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);								// We want a string back
			curl_setopt($curl, CURLOPT_TIMEOUT, $this->callTimeout);						// Timeout after $callTimeout seconds
			curl_setopt($curl, CURLOPT_USERPWD, $this->username.':'.$this->password);			// Set username & pw

			// If this is an HTTP POST request
			if( $this->httpRequestPost === true )
			{
				curl_setopt($curl, CURLOPT_URL, $this->helpSpotApiURL);					// Set URL for call
				curl_setopt($curl, CURLOPT_POST, true);									// This will be an HTTP POST request
				curl_setopt($curl, CURLOPT_POSTFIELDS, $this->queryString);					// Add query params to call
			}
			// Else, this is an HTTP GET request
			else
			{
				curl_setopt($curl, CURLOPT_URL, $this->helpSpotApiURL.'?'.$this->queryString);	// Just append query string to URL
			}

			//If https just accept the cert, dont verify
			if(strpos($this->helpSpotApiURL,'https') !== false)
			{
				curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
				curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
			}

			$return = curl_exec($curl);												// Make the call - get data

			// If error occurred then update error string
			if(curl_errno($curl))
			{
				$this->errors = 'Error: '.curl_error($curl);
				$return = false;
			}

			curl_close($curl);														// Properly close connection
		}
		// Else the URL is not set, update error string
		else
		{
			$this->errors = 'Error: The $helpSpotApiURL variable must be set to the URL of your HelpSpot installation.';
		}
		return $return;															// Return the data
	}

	// Glues together attribute and value with an '='. Used to create query string.
	function implodeQueryParams($k, $v) {
		return "$k=".urlencode($v);
	}

	// Alias of clearCache() method of cacheEngine object
	function clearCache() { $this->cacheEngine->clearCache(); }

	//Try and find a temporary directory
	function _tmp_dir(){
		if(function_exists("sys_get_temp_dir")){
			return sys_get_temp_dir();
		}elseif(is_writable('/tmp')){
			return '/tmp';
		}elseif(is_writable('c:\Windows\Temp')){
			return 'c:\Windows\Temp';
		}else{
			//Default to the current directory
			return './hsapi_cache';
		}
	}
}



/************************************************************************
Basic caching class. Used by HelpSpotAPI class above.
************************************************************************/
class HelpSpotApiCacheEngine {
	/*** Vars ***/
	var $cacheDir				= '';								// Directory to store cached requests in
	var $errors					= '';								// Current error message string

	/*** Constructor ***/
	function __construct($params=array()) {
		$this->errors = '';											// Default - clean error string
	}

	/*** Methods ***/
	// Cache $content that was retrieved with $queryString.  Content is PHP datatype unserialized from XML for best possible performance.
	function cache($inputString, $content) {
		$return = false;
		$fp = null;
		$fileName = $this->cacheDir.'/hscache_'.md5($inputString).'.txt';				// Set filename

		// If cacheDir is a valid directory || we can create the directory here
		if( is_dir($this->cacheDir) || mkdir($this->cacheDir) )
		{
			// If we can open the file
			if( $fp = fopen($fileName, 'w+') )
			{
				// If we can write to the file
				if( fwrite($fp, serialize($content)) )
				{
					// Protect cache files. We only need read perms for owner. They should not be
					// web-accessible as they are serialized arrays of potentially sensitive data.
					// NOTE: chmod() does not work the same on *nix & Windows. It is advisable to
					// keep cache files in a protected area that is not web-accessible.  Particularly
					// under Windows.
					chmod($fileName, 0400);
					$return = true;
				}
				// Else we can't write to file
				else
				{
					$this->errors = 'Cache error: Could not write to file "'.$fileName.'".';
				}
			}
			// Else we can't open file
			else
			{
				$this->errors = 'Cache error: Could not create file "'.$fileName.'".  Check directory permissions.';
			}
		}
		// cacheDir is not a valid directory and we can't create it
		else
		{
			$this->errors = 'Cache error: Could not create directory "'.$this->cacheDir.'".';
		}
		return $return;
	}

	// See if we have the results of this query cached
	function isCached($inputString, $cacheTTL) {
		$return = false;
		$fileName = $this->cacheDir.'/hscache_'.md5($inputString).'.txt';					// Set filename

		// If cache file exists and is current
		if( is_file($fileName) && ((mktime() - filemtime($fileName)) < $cacheTTL) )
		{
			$return = true;													// return TRUE
		}
		// Else if file exists, but is not current
		elseif( is_file($fileName) )
		{
			$this->deleteCacheFile($fileName);										// Delete old cache file
		}

		return $return;
	}

	// Get cached content
	function getCached($inputString) {
		$return = false;																// Preset return value
		$fileName = $this->cacheDir.'/hscache_'.md5($inputString).'.txt';		// Set filename

		// If we CAN NOT retrieve cached file
		if( !($return = file_get_contents($fileName)) )
		{
			$this->errors = 'Cache error: Could not retrieve cache file "'.$fileName.'".';
			$return = false;
		}
		// Else we CAN retrieve cached file
		else
		{
			// Unserialize cached data and return
			$return = unserialize($return);
		}
		return $return;
	}

	// Purge all cache files
	function clearCache() {
		// Foreach HS cache file in the cacheDir
		foreach($this->safe_glob($this->cacheDir) as $file)
		{
			// Delete cache file
			$this->deleteCacheFile($file);
		}
		return null;
	}

	// Delete a cache file
	function deleteCacheFile($file) {
		chmod($file, 0777);														// Remove "read only" designation (win)
		unlink($file);															// Delete cache file
	}

	// Get HS cache files - custom alias of PHP's native glob() function (glob() function is not available on many shared servers)
	function safe_glob($dir){
		$return = array();														// Array of files to return
		$dh = null;															// Directory handle

		// If dir exists and we can open it
		if( is_dir($dir) && ($dh=opendir($dir)))
		{
			// While we can retrieve files from directory
			while(($file = readdir($dh)) !== false)
			{
				// If the file found is not a directory & begins with "hscache_" (HS cache file)
				if( !is_dir($dir.$file)
					&& preg_match('/^hscache_/', $file) )
				{
					// Add file to return array
					$return[]=$dir.$file;
				}
			}
		}
		closedir($dh);
		return $return;
	}
}






###################################################################################
#
# XML Library, by Keith Devens, version 1.2b
# http://keithdevens.com/software/phpxml
#
# This code is Open Source, released under terms similar to the Artistic License.
# Read the license at http://keithdevens.com/software/license
#
###################################################################################

###################################################################################
#
# Modification
# Author: Joe Landsman (joe.landsman@dnlomnimedia.com)
# Date: 1/21/2009
# Notes:
# Altered function/class names to reflect HelpSpot namespace so as to prevent
# name collisions when this file is included in 3rd party applications.
#
# Altered calls to functions to remove pass by ref warnings
#
###################################################################################


###################################################################################
# HS_XML_unserialize: takes raw XML as a parameter (a string)
# and returns an equivalent PHP data structure
###################################################################################
function & HS_XML_unserialize(&$xml){
	$xml_parser = new HS_XML();
	$data = &$xml_parser->parse($xml);
	$xml_parser->destruct();
	return $data;
}
###################################################################################
# HS_XML_serialize: serializes any PHP data structure into XML
# Takes one parameter: the data to serialize. Must be an array.
###################################################################################
function & HS_XML_serialize(&$data, $level = 0, $prior_key = NULL){
	if($level == 0){ ob_start(); echo '<?xml version="1.0" ?>',"\n"; }
	while(list($key, $value) = each($data))
		if(!strpos($key, ' attr')) #if it's not an attribute
			#we don't treat attributes by themselves, so for an empty element
			# that has attributes you still need to set the element to NULL

			if(is_array($value) and array_key_exists(0, $value)){
				HS_XML_serialize($value, $level, $key);
			}else{
				$tag = $prior_key ? $prior_key : $key;
				echo str_repeat("\t", $level),'<',$tag;
				if(array_key_exists("$key attr", $data)){ #if there's an attribute for this element
					while(list($attr_name, $attr_value) = each($data["$key attr"]))
						echo ' ',$attr_name,'="',htmlspecialchars($attr_value),'"';
					reset($data["$key attr"]);
				}

				if(is_null($value)) echo " />\n";
				elseif(!is_array($value)) echo '>',htmlspecialchars($value),"</$tag>\n";
				else echo ">\n",HS_XML_serialize($value, $level+1),str_repeat("\t", $level),"</$tag>\n";
			}
	reset($data);
	if($level == 0){ $str = &ob_get_contents(); ob_end_clean(); return $str; }
}
###################################################################################
# XML class: utility class to be used with PHP's XML handling functions
###################################################################################
class HS_XML{
	var $parser;   #a reference to the XML parser
	var $document; #the entire XML structure built up so far
	var $parent;   #a pointer to the current parent - the parent will be an array
	var $stack;    #a stack of the most recent parent at each nesting level
	var $last_opened_tag; #keeps track of the last tag opened.

	function __construct(){
		$this->parser = xml_parser_create();
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser, 'open','close');
		xml_set_character_data_handler($this->parser, 'data');
	}
	function destruct(){ xml_parser_free($this->parser); }
	function & parse(&$data){
		$this->document = array();
		$this->stack    = array();
		$this->parent   = &$this->document;
		$result = xml_parse($this->parser, $data, true) ? $this->document : NULL;
		return $result;
	}
	function open(&$parser, $tag, $attributes){
		$this->data = ''; #stores temporary cdata
		$this->last_opened_tag = $tag;
		if(is_array($this->parent) and array_key_exists($tag,$this->parent)){ #if you've seen this tag before
			if(is_array($this->parent[$tag]) and array_key_exists(0,$this->parent[$tag])){ #if the keys are numeric
				#this is the third or later instance of $tag we've come across
				$key = count_numeric_items($this->parent[$tag]);
			}else{
				#this is the second instance of $tag that we've seen. shift around
				if(array_key_exists("$tag attr",$this->parent)){
					$arr = array('0 attr'=>&$this->parent["$tag attr"], &$this->parent[$tag]);
					unset($this->parent["$tag attr"]);
				}else{
					$arr = array(&$this->parent[$tag]);
				}
				$this->parent[$tag] = &$arr;
				$key = 1;
			}
			$this->parent = &$this->parent[$tag];
		}else{
			$key = $tag;
		}
		if($attributes) $this->parent["$key attr"] = $attributes;
		$this->parent  = &$this->parent[$key];
		$this->stack[] = &$this->parent;
	}
	function data(&$parser, $data){
		if($this->last_opened_tag != NULL) #you don't need to store whitespace in between tags
			$this->data .= $data;
	}
	function close(&$parser, $tag){
		if($this->last_opened_tag == $tag){
			$this->parent = $this->data;
			$this->last_opened_tag = NULL;
		}
		array_pop($this->stack);
		if($this->stack) $this->parent = &$this->stack[count($this->stack)-1];
	}
}
function count_numeric_items(&$array){
	return is_array($array) ? count(array_filter(array_keys($array), 'is_numeric')) : 0;
}
?>
