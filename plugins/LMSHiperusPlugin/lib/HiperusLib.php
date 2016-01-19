<?php
/*
 * HiperusLIB - Open Source telephony lib
 *
 * Copyright (C) 2010 - 2011 Telekomunikacja Blizej
 *
 * See devel.hiperus.pl for more information about
 * the HiperusLIB and HiperusAPI project. 
 *
 * Version: 1.0
 */

/******** Configuration section - sekcja konfiguracji *************************/

// plik w którym będzie przechowywana sesja
if(!defined('H_SESSION_FILE')) define('H_SESSION_FILE','/var/lib/hiperus/hiperus1.session');

if (!defined('H_LOCK_FILE')) define('H_LOCK_FILE', '/var/lib/hiperus/hiperus1.lock');

/******** End of configuration section ****************************************/

define('H_URI','https://backend.hiperus.pl:8080/hiperusapi.php');

//define('DEBUG_API', 1);

/**
 * 
 */
class HiperusLib {

    private $_h_username;
    private $_h_password;
    private $_h_domain;
    
    private $_h_realm;
    private $_h_sessid;
    private $_h_id_reseller;
    private $_h_debug = false;

	private $soapClient = null;

    /**
     * __construct()
     */
    public function __construct($realm='PLATFORM_MNGM',$username=null,$password=null,$domain=null) {
		if (defined('DEBUG_API'))
			echo __METHOD__ . PHP_EOL;
        $this->_h_realm = $realm;
        $this->_h_username = $username;
        $this->_h_password = $password;
        $this->_h_domain = $domain;
        
        if(!file_exists(H_SESSION_FILE)) {
            $this->hStartSession();
        } elseif(filesize(H_SESSION_FILE) == 0) {
            $this->hStartSession();
        } else {
            $this->hContinueSession();
        }
    }
    
    
    /**
     * hStartSession()
     */
    private function hStartSession() {
    
        if($this->_h_username && $this->_h_password) {
            $username = $this->_h_username;
            $password = $this->_h_password;
            $domain = $this->_h_domain;
        } else {    
		$username = ConfigHelper::getConfig('hiperus_c5.username');
		$password = ConfigHelper::getConfig('hiperus_c5.password');
		$domain = ConfigHelper::getConfig('hiperus_c5.domain');
        }
        
        
        $req = new stdClass();
        $req->username = $username;
        $req->password = $password;
        $req->domain = $domain;
	//echo "$username\n$password\n$domain\n";

        $this->_h_session = null; // generate new sessid

        $response = $this->sendRequest("Login",$req);
        
        if (get_class($response) != 'stdClass' || !$response->success) {
            throw new Exception("HiperusLIB login failed: ".$response->error_message);
        } 
        
        $this->_h_session = $response->sessid;
        //$this->_h_id_reseller
        if(!file_put_contents(H_SESSION_FILE,$this->_h_session)) {
            throw new Exception("HiperusLIB unable to save session");
        }
        chmod(H_SESSION_FILE,0660);

    }


    /**
     * hContinueSession()
     */
    private function hContinueSession() {
        $session_f_content = file_get_contents(H_SESSION_FILE);
        if($session_f_content===false)
            throw new Exception("Unable to get session information from file");
        
        $this->_h_session = trim($session_f_content);
        
        $req = new stdClass();

        $response = $this->sendRequest("CheckLogin",$req);

        if(!$response->success) {
            unlink(H_SESSION_FILE);
            throw new Exception("HiperusLIB reopen session failed: ".$response->error_message);
        }

        if($this->_h_session != $response->sessid)
            throw new Exception("HiperusLIB session matching fatal error");
        
        if(!$response->result_set[0]['logged']) {
            $this->hStartSession();
        }
                    
    }


    /**
     * sendRequest()
     */
    public function sendRequest($action,$req) {
        if($this->_h_debug) _h_debug("SEND Action: $action");
        $sessid = $this->_h_session;
        $realm = $this->_h_realm;

	// global hiperus locking mechanism to avoid request limit (30 reqs on 10 secs) on hiperus server
	$fh = fopen(H_LOCK_FILE, "c+");
	if (!$fh)
		throw new Exception("HiperusLib couldn't open lock file: " . H_LOCK_FILE);
	flock($fh, LOCK_EX);
	if (filesize(H_LOCK_FILE)) {
		$lock = fread($fh, filesize(H_LOCK_FILE));
		$params = explode(' ', $lock);
		if (count($params) < 2) {
			$timestamp = time();
			$counter = 0;
		} else {
			$timestamp = intval($params[0]);
			if (time() - $timestamp > 5) {
				$timestamp = time();
				$counter = 0;
			} else
				$counter = intval($params[1]);
		}
	} else {
		$timestamp = time();
		$counter = 0;
	}
	$counter++;
	if (defined('DEBUG_API'))
		echo __METHOD__ . ': API call number=' . $counter . PHP_EOL;
	if ($counter >= 7) {
		ftruncate($fh, 0);
		rewind($fh);
		if (time() - $timestamp < 5) {
			if (defined('DEBUG_API')) {
				echo __METHOD__ . ': time interval between first API call and last API call: ' . (time() - $timestamp). ' seconds' . PHP_EOL;
				echo __METHOD__ . ': API call number >= 7 in 5 seconds, sleeping for ' . (5 - (time() - $timestamp)) . ' seconds' . PHP_EOL;
			}
			sleep(5 - (time() - $timestamp));
		}
	} else {
		rewind($fh);
		fwrite($fh, "$timestamp $counter");
	}
	fflush($fh);
	flock($fh, LOCK_UN);
	fclose($fh);

		if (is_null($this->soapClient))
			$this->soapClient = new SoapClient(null, array(
				'uri' => H_URI,
				'location' => H_URI,
			));

		$ret = $this->soapClient->request($realm, $action, $req, $sessid);

        if($this->_h_debug) {
            _h_debug("REQUEST ====>\n");
            _h_debug("REALM: $realm ACTION: $action SESSID: $sessid\n");
            print_r($req);
            _h_debug("RESPONSE <=======\n");
            print_r($ret);
        }
        
        return $ret;
    }
}


/*==============================================================
    Helper function - obj2xml - Hiperus
===============================================================*/
function obj2xml(&$parent,$o) {
    $v = get_object_vars($o);
    foreach($v as $key=>$val) {
        if(is_object($val)) {
            $el = new DOMElement($key);
            $parent->appendChild($el);
            obj2xml($el,$val);
        } elseif(is_array($val)) {
            $el = new DOMElement($key);
            $parent->appendChild($el);
            array2xml($el,$val);
        } else {
            $parent->appendChild(new DOMElement($key,$val));
        }
    }
}

/*==============================================================
    Helper function - array2xml - Hiperus
===============================================================*/
function array2xml(&$parent,$a) {
    foreach($a as $key=>$val) {
        if(is_integer($key))
            $keystr = "record";    
        else
            $keystr = $key;
            
        if(is_object($val)) {
            $el = new DOMElement($keystr);
            $parent->appendChild($el);
            obj2xml($el,$val);
        } elseif(is_array($val)) {
            $el = new DOMElement($keystr);
            $parent->appendChild($el);
            array2xml($el,$val);
        } else {
            $parent->appendChild(new DOMElement($keystr,$val));
        }
    }
}


/*==============================================================
    Helper function - xml2obj - Hiperus
===============================================================*/
function xml2obj($domElement,$ident="-") {
    $ret_obj = new stdClass();
    if($domElement->childNodes) {
        $ridx = 0;
        foreach($domElement->childNodes as $c_elem) {
            $_t = $c_elem->tagName;
            if($c_elem->nodeType == XML_ELEMENT_NODE && $c_elem->childNodes->length == 0) {
                $ret_obj->$_t = null;
            } elseif($c_elem->nodeType == XML_ELEMENT_NODE && $c_elem->childNodes->length == 1 && $c_elem->childNodes->item(0)->nodeType == XML_TEXT_NODE) {
                $ret_obj->$_t = $c_elem->childNodes->item(0)->wholeText;
            } elseif($c_elem->nodeType == XML_ELEMENT_NODE && $c_elem->childNodes->length >= 1) {
            
                if($_t == 'result_set') {
                    $n_c_elem = $c_elem;
                    foreach($n_c_elem->childNodes as $n) {
                        $ret_obj->result_set[] = xml2obj($n,$ident."-");
                    }
                
                } elseif($_t == 'fields') {
                    $n_c_elem = $c_elem;
                    foreach($n_c_elem->childNodes as $n) {
                        $ret_obj->fields[] = xml2obj($n,$ident."-");
                    }                
                } else {
                    $ret_obj->$_t = xml2obj($c_elem,$ident."-");                
                }                
            }
        }
    }
    
    
    return $ret_obj;
}

function _h_debug($str) {
    print "[".date("Y-m-d H:i:s")."] DEBUG: ".$str."\n";
}

?>
