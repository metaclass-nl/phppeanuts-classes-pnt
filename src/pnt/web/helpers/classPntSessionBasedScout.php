<?php
/* Copyright (c) MetaClass, 2003-2013

Distrubuted and licensed under under the terms of the GNU Affero General Public License
version 3, or (at your option) any later version.

This program is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	
See the License, http://www.gnu.org/licenses/agpl.txt */

/**
* See what is context scouting, http://www.phppeanuts.org/site/index_php/Pagina/180/context+scouting.html
* 
* To keep track of the movements through the pages of the applications, 
* the SessionBasedScout stores footpints in the session. 
* However, its interface has been designed to allow the replacement by
* for example a database based scout that supports longer and persistent 
* footprint history. 

* Footprints exist of an uri for each footprint id, and another map
* of context ids (upward references) for each footprint id.
* Because from one page several pages be opened in a new window or tab, the scout
* stores any move so that it can become a context later on.
* @package pnt/web/helpers
*/
class PntSessionBasedScout { //implements ScoutInterface
	public $site;
	public $baseUrl;
	public $footprintsLimit;
	public $footprintUris;
	public $contextIds;
	
	function __construct($siteParam, $footprintsLimit = 30) {
		$this->site = $siteParam;
		$this->baseUrl = $siteParam->getBaseUrl();
		$this->footprintsLimit = $footprintsLimit;
	}

	/** @return a reference to a session variable for this baseUrl */
	function &sessionVar($key) {
		if (!isSet($_SESSION[$this->baseUrl][$key]))
			$_SESSION[$this->baseUrl][$key] = null;
		
		return $_SESSION[$this->baseUrl][$key];
	}
	
	/** @protected
	* @return Array of requestData by footprint id
	*/
	function &getFootprintUris() {
		if (isSet($this->footprintUris)) return $this->footprintUris;
		
		if ($this->sessionVar('pntFpUris') === null)
			$this->footprintUris = array();
		 else 
		 	$this->footprintUris = $this->sessionVar('pntFpUris');
		 	
		return $this->footprintUris;
	}

	/** @protected Returns the ids of the deeper context by context id
	* @return Array of int
	*/
	function getContextIds() {
		if (isSet($this->contextIds)) return $this->contextIds;
		
		if ($this->sessionVar('pntFpCtx') === null)
			$this->contextIds = array();
		 else 
			$this->contextIds = $this->sessionVar('pntFpCtx');
			
		return $this->contextIds;
	}

	/** Returns the footprint id of the referrer 
	* PREREQUISITE: the footprint for the current request has nog yet been registered
	* @return string or null
	*/
	function getReferrerId($requestData) {
		$uris = $this->getFootprintUris();
		$referrerId = isSet($requestData['pntRef']) ? $requestData['pntRef'] : null;
		if ($referrerId && isSet($uris[$referrerId])) return $referrerId;
		
		if (count($uris) == 0) return null;

		//try to find the referrer uri in the footprints.
		$httpReferer = $this->site->request->getServerValue('HTTP_REFERER');
		if ($httpReferer) {
			$refUri = $this->getFootprintUri($httpReferer);
//print "<BR>Searching referrer: $refUri";
			$tokens = array_keys($uris);
			for ($i=count($tokens)-1; $i>=0; $i--) {
				if ($uris[$tokens[$i]] == $refUri) 
					return $tokens[$i];
			}
//print "<BR> referrer not found";
		}
		//not found or no referrer, assume last
		end($uris);
		return key($uris);
	}

	/** Informs this about the current movement
	* @param string $fromId The id of the footprint we are moving FROM
	* @param String $direction 'up', 'down' or no direction 
	* @param Array $requestData the data to be used for storing the request uri
	* PRECONDITION: session is already started
	*/
	function moved($fromId, $direction = null, $requestData=null) {

		$uris =& $this->getFootprintUris();
		$this->getContextIds();
		$sm = $this->site->getSecurityManager();
		$footprintId = $sm->newFootprintId();
		$uris[$footprintId] = $this->getFootprintUri($this->getRequestUrl($requestData));

		if ($direction == 'up') {
			if (isSet($this->contextIds[$fromId])) {
				$contextId = $this->contextIds[$fromId];
				if (isSet($this->contextIds[$contextId])) 
					$this->contextIds[$footprintId] = $this->contextIds[$contextId];
			}
		} elseif ($direction == 'down') {
			if ($fromId)
				$this->contextIds[$footprintId] = $fromId;
		} else {
			if (isSet($this->contextIds[$fromId]))
				$this->contextIds[$footprintId] = $this->contextIds[$fromId];
		}
		if (count($uris) > $this->footprintsLimit) { // cut footprint tail
			reset($uris);
			$oldestId = key($uris);
			unSet($uris[$oldestId]);
			unSet($this->contextIds[$oldestId]);
		}

//print "<!--\n direction: $direction\n";
//print_r($this->footprintUris);
//print_r($this->contextIds);
//print "\n -->";
		$fpUris =& $this->sessionVar('pntFpUris'); //get reference to variable
		$fpUris = $uris; //set the referred variable
		$fpCtx =& $this->sessionVar('pntFpCtx');
		$fpCtx = $this->contextIds;
		return $footprintId;
	}


	function getRequestUrl($requestData) {
		if ($requestData === null)
			$requestData = $this->site->requestData;
	
		$url = $this->site->getBaseUrl();
		$url .= $this->site->getDir(). 'index.php?';
		$url .= $this->site->queryStringFrom($requestData);
		return $url;
	}

	function getFootprintUri($url) {

		$rootUrl = $this->getRootUrl();
		if (subStr($url, 0, strLen($rootUrl)) == $rootUrl) 
			$url = subStr($url, strLen($rootUrl));

		$url = Gen::stripQueryParam($url, 'pntScd');
//		$url = Gen::stripQueryParam($url, 'pntRef');  leave it in, it can be used for backtracking
		$url = Gen::stripQueryParam($url, 'PHPSESSID');
		$url = Gen::stripQueryParam($url, 'pntEditFeedback');
		$url = Gen::stripQueryParam($url, 'pntInfo');
		forEach(array_keys($_COOKIE) as $key)
			$url = Gen::stripQueryParam($url, $key);

		return $url;
	}
	
	function getRootUrl() {
		$baseUrl = $this->site->getBaseUrl();
		$protocolSlashesPos = strPos($baseUrl, '//');
		$rootSlashPos = strPos($baseUrl, '/', $protocolSlashesPos + 2);
		return subStr($baseUrl, 0, $rootSlashPos);
	}		
	
	/** 
	* @param string $footprintId the id of the footprint to get the context href from
	* @return String absolute url to context, or null if none
	*/
	function getContextHref($footprintId) {

		$contextIds = $this->getContextIds();
		if (!isSet($contextIds[$footprintId])) 
			return null; //has no context
			
		return $this->getFootprintHref($contextIds[$footprintId]);
	}

	/** 
	* @param string $footprintId the id of the footprint to get the href from
	* @return String absolute url, or null if none
	*/
	function getFootprintHref($footprintId) {
		$footprint = $this->getFootprint($footprintId);
		if (!$footprint) return null; //already scrolled or invalid
		
		return $this->getRootUrl(). $footprint;
	}
	
	function getFootprint($footprintId) {
		$uris = $this->getFootprintUris();
		return isSet($uris[$footprintId]) ? $uris[$footprintId] : null; 
	}
}