<?php
/**
 * RESTfooly - a simple HTTP handler written in PHP. It uses REST bindings.
 *
 * Copyright (C) 2011  P. Andreas Möller
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see http://www.opensource.org/licenses/gpl-3.0.html.
 *
 * @author P. Andreas Möller (kontakt@pamoller.com)
 * @version 0.1
 */
class RESTfooly {
	/**
	 * create, read, update or delete any object (None)
	 */
	const ACCESS_CONTROL_NONE = 0;
	/**
	 * read any object (Read Only)
	 */
	const ACCESS_CONTROL_READ = 1;
	/**
	 * read or create any object (Read Write)
	 */
	const ACCESS_CONTROL_WRITE = 2;
	/**
	 * read or create any object update or delete those created in this session (Session Based)
	 */
	const ACCESS_CONTROL_SESSION = 3;
	/**
	 * custom based access control - customizer can overwrite httpAccessControlCustom().
	 * the method is called fst when evaluateing a http request
	 */
	const ACCESS_CONTROL_CUSTOM = 4;
	/**
	 * default access control method
	 */
	const ACCESS_CONTROL_METHOD = 0;
	/**
	 * verbose errors disabeled by default
	 */
	const ERROR_VERBOSE = true;
	/**
	 * encoding of http request response
	 */
	const HTTP_RESPONSE_ENCODING = "utf8"; 
	/**
	 * http response 200
	 */
	const HTTP_RESPONSE_200 = "HTTP/1.1 200 OK";
	/**
	 * http response 201
	 */
	const HTTP_RESPONSE_201 = "HTTP/1.1 201 Created";
	/**
	 * http response 400
	 */
	const HTTP_RESPONSE_400 = "HTTP/1.1 400 Bad Request";
	/**
	 * http response 401
	 */
	const HTTP_RESPONSE_401 = "HTTP/1.1 401 Unauthorized";
	/**
	 * http response 404
	 */
	const HTTP_RESPONSE_404 = "HTTP/1.1 404 Not Found";
	/**
	 * http response 405
	 */
	const HTTP_RESPONSE_405 = "HTTP/1.1 405 Method Not Allowed";
	/**
	 * http response status ok
	 */
	const HTTP_RESPONSE_STATUS_OK = "ok";
	/**
	 * http response status not ok
	 */
	const HTTP_RESPONSE_STATUS_ERROR = "error";
	/**
	 * assume objects addressed within the server root. choose
	 * /tree if objects are adressable by /tree/object
	 */
	const HTTP_BASE_URI = "/";
	/**
	 * assume this encoding for body if parameter charset is obmitted
	 */
	const MEDIA_CHARSET = "ISO-8859-1";
	/**
	 * media type of json request
	 */
	const MEDIA_TYPE_JSON = "application/json";
	/**
	 * media type of form data
	 */
	const MEDIA_TYPE_FORM = "application/x-www-form-urlencoded";
	/**
	 * default connect string
	 */
	const DB_CONNECT_STRING = "include/RESTfooly.sqlite";
	/**
	 * encoding of database
	 */
	const DB_ENCODING = "UTF-8";
	/**
	 * maximum number of rows in a result set
	 */
	const DB_MAX_ROWS = 500;
	/**
	 * maximum string length
	 */
	const DB_MAX_LENGTH = 2056;
	/**
	 * expression of sysdate
	 */
	const DB_NOW = "date('now')";
	/**
	 * auto field created
	 */
	const DB_CREATED = "created";
	/**
	 *  auto field updated
	 */
	const DB_UPDATED = "updated";
	/**
	 * connect to database by this string
	 */
	protected $dbConnectString;
	/**
	 * maximum number of rows within a result set
	 */
	protected $dbMaxRows;
	/**
	 * database object
	 */
	protected $dbObject;
	/**
	 * if enabeled, error code and message displayed within repsonse body
	 */
	protected $errorVerbose;
	/**
	 * access control maps permissions to request methods and objects
	 */
	protected $httpAccessControl;
	/**
	 * base url (cut off from request object)
	 */
	protected $httpRequestBaseUri;
	/**
	 * raw body of http request
	 */
	protected $httpRequestBody;
	/**
	 * media type of http request body (without attributes eg. application/json)
	 */
	protected $httpRequestMediaType;
	/**
	 * encoding of http request body
	 */
	protected $httpRequestMediaEncoding; 
	/**
	 * http request method
	 */
	protected $httpRequestMethod;
	/**
	 * requested object or collection
	 */
	protected $httpRequestObject;
	/**
	 * id of requested object
	 */
	protected $httpRequestObjectid;
	/**
	 * maximum length of uri params or values
	 */
	protected $httpRequestMaxLength;
	/**
	 * http request uri
	 */
	protected $httpRequestUri;
	/**
	 * http request variables
	 */
	protected $httpRequestVariables;
	/**
	 * class constructor
	 * @param array	$cfg	config param
 	 */
	public function __construct($cfg=array()) {
		// connect string
		$this->dbConnectString = $this->stringParam($cfg, "DB_CONNECT_STRING", "string", self::DB_CONNECT_STRING);
		// set base url
		$this->httpRequestBaseUri = $this->httpRequestBaseUriValidate($cfg);
		// error_verbose mode
		$this->errorVerbose = $this->stringParam($cfg, "ERROR_VERBOSE", "boolean", self::ERROR_VERBOSE); 
		// access control
		$this->httpAccessControl=$this->stringParam($cfg, "ACCESS_CONTROL", "integer", self::ACCESS_CONTROL_METHOD);
		// max rows
		$this->dbMaxRows = $this->stringParam($cfg, "DB_MAX_ROWS", "integer", self::DB_MAX_ROWS);
		// maximum string length
		$this->httpRequestMaxLength = $this->stringParam($cfg, "DB_MAX_LENGTH", "integer", self::DB_MAX_LENGTH);
	}
	/**
	 * validate Base URL of
	 * @param array $cfg
	 * @return string
	 */
	protected function httpRequestBaseUriValidate($cfg) {
		$str = $this->stringParam($cfg, "HTTP_BASE_URI", "string", self::HTTP_BASE_URI);
		// request leading slash
		if (!(substr($str, 0, 1) === "/")) {
				$this->error("err201", "Leading slash missed within HTTP_BASE_URI ($str)");
		}
		// add missing trailing slash!
		if (!(substr($str, -1, 1) === "/")) {
				$str = sprintf("%s/", $str);
		}

		return sprintf("/^%s/", preg_quote($str, "/"));	
	}
	/**
	 * evaluate http request
	 */
	public function httpRequestHandle() {
		// do custom access control
		if ($this->httpAccessControl === self::ACCESS_CONTROL_CUSTOM) {
			$this->httpAccessControlCustom();
		}
		// get request method
		$this->httpRequestMethod();
		// validate request method
		$this->httpRequestMethodValidate();
		// get request uri
		$this->httpRequestUri();
		// get request object
		$this->httpRequestObject();
		// switch by media-type
		$this->httpRequestMediaType();
		// handle request by rest
		$this->httpRequestREST();
	}
	/**
	 * custom access control
	 */
	protected function httpAccessControlCustom() {}
	/**
	 * break execution, if objectid was not stored in session before
	 */
	protected function httpAccessControlSession() {
		if (!($this->httpAccessControl === self::ACCESS_CONTROL_SESSION)) {
			return;
		}
		// Do not start a session, if not needed!!
		session_start();
		// owns no objects or not this
		if (!isset($_SESSION["userObjects"][$this->httpRequestObject]) or !in_array($this->httpRequestObjectid, $_SESSION["userObjects"][$this->httpRequestObject])) {
			$this->error("err401", "Unauthorized", self::HTTP_RESPONSE_401);
		}
	} 
	/**
	 * initialize required class member or break execution
	 */
	protected function httpRequestMethod() {
		if (!isset($_SERVER["REQUEST_METHOD"])) {
			$this->error("err400", "Bad Request");
		}
		$this->httpRequestMethod = strtoupper($_SERVER["REQUEST_METHOD"]);
	}
	/**
	 * break execution, if request method is not allowed by access method
	 */
	protected function httpRequestMethodValidate() {
		switch($this->httpAccessControl) {
			case self::ACCESS_CONTROL_READ:
				$allowed = array("GET");
			break;
			case self::ACCESS_CONTROL_WRITE:
				$allowed = array("POST", "GET");
			break;
			default:
				$allowed = array("POST", "GET", "PUT", "DELETE");
			break;
		}
		if (!in_array($this->httpRequestMethod, $allowed)) {
			$this->error("err009", "method not allowed", self::HTTP_RESPONSE_405, sprintf("Allow: %s", implode(",", $allowed)));
		}
	}
	/**
	 * initialize optional class member by allowed media types only,
	 * break execution otherwise
	 */
	protected function httpRequestMediaType() {
		if (isset($_SERVER["CONTENT_TYPE"])) {
			$parts = array_map("trim", explode(";", $_SERVER["CONTENT_TYPE"]));
			if (!in_array($parts[0], array(self::MEDIA_TYPE_FORM))) {#, self::MEDIA_TYPE_JSON))) {
				$this->error("err014", "Media type not allowed");
			} else {
				$this->httpRequestMediaType = $parts[0];
			}
			// charset may a parameter
			for($i = 1; $i < count($parts); $i++) {
				$params=array_map("trim", explode("=", $parts[$i]));
				if (strtolower($params[0]) === "charset") {
					$this->httpRequestMediaEncoding=str_replace('"', "", $params[1]);
					break;
				} 
			}	
		}
	}
	/**
	 * initialize required class member or break execution
	 */
	protected function httpRequestUri() {
		if (!isset($_SERVER["REQUEST_URI"])) {
			$this->error("err010", "No request URI given");
		}
		$this->httpRequestUri = $_SERVER["REQUEST_URI"];
	}
	/**
	 * map request uri to requested object and objectid (optional)
	 */
	protected function httpRequestObject() {
		if (preg_match($this->httpRequestBaseUri, $this->httpRequestUri) === 0) {
			$this->error("err201", sprintf("Bad request, BaseUrl (%s) not found on request(%s)", $this->httpRequestBaseUri, $this->httpRequestUri));
		}
		// strip base uri and uri params
		$parts = explode("/", preg_replace(array($this->httpRequestBaseUri, "/\?.*$/"), "", $this->httpRequestUri));
		if (count($parts) > 2) {
			$this->error("err202", sprintf("Format error (%s)", $this->httpRequestUri));
		}
		if (strlen($parts[0]) === 0) {
			$this->error("err203", sprintf("No object given (%s)", $this->httpRequestUri));
		}
		$this->httpRequestObject=$this->stringKey($parts[0]);
		$this->dbObjectSchema();
		if (isset($parts[1])) {
			$this->httpRequestObjectid=$this->stringValue($parts[1]);
		}
	}
	/**
	 * initialize class member by encoded, validated keys and values 
	 */
	protected function httpRequestVariables($vars) {
		if (is_array($vars)) {
			foreach(array_diff(array_keys($vars), array($this->dbPrimaryKey(), self::DB_CREATED, self::DB_UPDATED)) as $key) {
				$this->httpRequestVariables[$this->dbColumn($this->stringKey($this->stringEncode($key)))] = $this->dbValue($this->stringValue($this->stringEncode($vars[$key])), $this->dbColumnType($this->stringEncode($key)));
			}		
		}
	}
	/**
	 * convert encoding of string
	 * @param string $str
	 * @param string $to	
	 * @return string
	 */
	protected function stringEncode($str, $to=self::DB_ENCODING) {
		if (($convstr = iconv($this->httpRequestMediaEncoding?$this->httpRequestMediaEncoding:self::MEDIA_CHARSET, self::DB_ENCODING, $str)) === false) {
			$this->error("err502", "Can't convert string");
		}
		
		return $convstr;
	}
	/**
	 * map a string of media type application/x-www-form-urlencoded to an assoc arrray 
	 * @param string $str	
	 * @return array
	 */
	protected function stringDecodeForm($str) {
		$pairs = explode("&", $str);
		if (is_array($pairs)) {
			foreach($pairs as $pair) {
				$parts=explode("=", $pair);
				if (isset($parts[0])) {
					$rv[$parts[0]] = isset($parts[1])?$parts[1]:null;
				}	
			}

			return $rv;
		}			
	}
	/**
	 * validate input string against key-schema
	 * @param string $str
	 * @return string
	 */
	protected function stringKey($str) {
		if (!preg_match("/^[a-zA-Z][a-zA-z0-9]+/", $this->stringValue($str))){
			$this->error("err080", "No key ($str)");
		}

		return $str;
	}
	/**
	 * validate input string against value-schema
	 * @param string $str
	 * @return string
	 */
	protected function stringValue($str) {
		if (strlen($str) > $this->httpRequestMaxLength) {
			$this->error("err081", "Value exceeds max length($str)");
		}

		return $str;
	}
	/**
     * returns plural of an english noun (patterns taken from ruby on rails)
     * @param string $word
     * @return string 
     */
    protected function stringPlural($word) {
		foreach(array('/(equipment|information|rice|money|species|series|fish|sheep)/i' => '\1', '/move$/i' => 'moves', '/sex$/i' => 'sexes', '/child$/i' => 'children', '/man$/i' => 'men', '/person$/i' => 'people', '/(quiz)$/i' => '\1zes', '/^(ox)$/i' => '\1en', '/([m|l])ouse$/i' => '\1ice', '/(matr|vert|ind)ix|ex$/i' => '\1ices', '/(x|ch|ss|sh)$/i' => '\1es', '/([^aeiouy]|qu)ies$/i' => '\1y', '/([^aeiouy]|qu)y$/i' => '\1ies', '/(hive)$/i' => '\1s', '/(?:([^f])fe|([lr])f)$/i' => '\1\2ves', '/sis$/i' => 'ses', '/([ti])um$/i' => '\1a' , '/(buffal|tomat)o$/i' => '\1oes', '/(bu)s$/i' => '\1ses', '/(alias|status)/i' => '\1es', '/(octop|vir)us$/i' => '\1i', '/(ax|test)is$/i' => '\1es', '/s$/i' => 's', '/$/' => 's') as $pattern => $replace) {
			if (preg_match($pattern, $word)) {

				return preg_replace($pattern, $replace, $word, 1);
			}
		}
		
		return $word;
    }

    /**
     * returns singular of an english noun (see stringPlural)
     * @param string $word
     * @return string
     */
    protected function stringSingular($word) {
        foreach(array('/(equipment|information|rice|money|species|series|fish|sheep)$/i' => '\\1', '/people$/i' => 'person', '/men$/i' => 'man', '/children$/i' => 'child', '/sexes$/i' => 'sex', '/moves$/i' => 'move', '/(quiz)zes$/i' => '\\1', '/(matr)ices$/i' => '\\1ix', '/(vert|ind)ices$/i' => '\\1ex', '/^(ox)en/i' => '\\1', '/(alias|status)es$/i' => '\\1', '/([octop|vir])i$/i' => '\\1us', '/(cris|ax|test)es$/i' => '\\1is', '/(shoe)s$/i' => '\\1', '/(o)es$/i' => '\\1', '/(bus)es$/i' => '\\1', '/([m|l])ice$/i' => '\\1ouse', '/(x|ch|ss|sh)es$/i' => '\\1', '/(m)ovies$/i' => '\\1ovie', '/(s)eries$/i' => '\\1eries', '/([^aeiouy]|qu)ies$/i' => '\\1y', '/([lr])ves$/i' => '\\1f', '/(tive)s$/i' => '\\1', '/(hive)s$/i' => '\\1', '/([^f])ves$/i' => '\\1fe', '/(^analy)ses$/i' => '\\1sis', '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\\1\\2sis', '/([ti])a$/i' => '\\1um', '/(n)ews$/i' => '\\1ews', '/s$/i' => '') as $pattern => $replace) {
			if (preg_match($pattern, $word)) {

				return preg_replace($pattern, $replace, $word, 1);
			}
		}
		
		return $word;
    } 
	/**
	 * read ddl-schema of requested object
	 */
	protected function dbObjectSchema() {
		$this->dbConnect();
		if(!($this->dbObjectSchema = @$this->dbObject->fetchColumnTypes($this->dbRelationName($this->httpRequestObject)))) {
			$this->error("err089", sprintf("%s is no object", $this->httpRequestObject));
		}
	}
	/**
	 * break execution, if requested colum does not exist
	 * @param string $str	requested column name
	 * @return string
	 */
	protected function dbColumn($str) {
		if (!$this->dbHasColumn($str)) {
			$this->error("err072", "column not found ($str)");
		}

		return $str;
	}
	/**
	 * returns true, if column exists in schema
	 * @param string $str	
	 */
	protected function dbHasColumn($str) {
		
		return isset($this->dbObjectSchema[$str])?true:false;
	}
	/**
	 * return data type of column
	 * @param string $column
	 * @return string
	 */
	protected function dbColumnType($str) {

		return $this->dbObjectSchema[$this->dbColumn($str)];
	}
	/**
	 * map values to ddl-datatypes (deprecated)
	 * @param string $str
	 * @param string $type
	 */
	protected function dbValue($str, $type) {
		switch(strtoupper($type)) {
			case "TEXT":
				return (string) $str;
			break;
			case "INTEGER":
				return (integer) $str;
			break;
			case "FLOAT":
				return (float) $str;
			break;
			default:
				// beware of more different data types !!!
				return (string) $str;
			break;
		}
	}
	/**
	 * extract a value from an array with respect to data type and default
	 * @param array	$arr
	 * @param string $key
	 * @param string $type
	 * @param mixed $default
 	 */
 	protected function stringParam($arr, $key, $type="string", $default=null) {
		if (!isset($arr)) {
			$this->error("err005", "array expected");
		}
		if (!isset($arr[$key]) && isset($default)) {
			return $default;
		} 
		if (!(gettype($arr[$key]) === $type)) {
			$this->error("err03", "wrong data type ($key, $type)");
		}	
		
		return $arr[$key];
	}
	/**
	 * mark object as user object within current session
	 * @param string $str
	 */
	protected function sessionSetUserObject($str) {
		if (!($this->httpAccessControl === self::ACCESS_CONTROL_SESSION)) {
			return;
		}
		// Do not start a session if not needed!
		session_start();
		if (isset($_SESSION["userObjects"][$this->httpRequestObject])) {
			$_SESSION["userObjects"][$this->httpRequestObject] = array_unique(array_merge($_SESSION["userObjects"][$this->httpRequestObject], array($str)));	
		} else {
			$_SESSION["userObjects"][$this->httpRequestObject] = array($str);
		}
	}
	/**
	 * read http request body (may add encoding here)
	 */
	protected function httpRequestBody() {
		if (isset($_SERVER["CONTENT_LENGTH"]) && $_SERVER["CONTENT_LENGTH"] > 0) {
			$fh = fopen("php://input", "r");
			if ($fh === null) {
				$this->error("err0015", "Can not open ressource");
			}
			while ($data = fread($fh, 1024)) {
				$this->httpRequestBody .= $data;
			}
			fclose($fh);
		}
	}
	/**
	 * evaluate http request restfully
	 */
	protected function httpRequestREST() {
		switch($this->httpRequestMethod) {
			case "POST":
				$this->httpRequestVariables($_POST);
				$this->dbInsert();
			break;
			case "GET":
				$this->httpRequestVariables($_GET);
				$this->dbSelect();
			break;
			case "PUT":
				$this->httpAccessControlSession();
				$this->httpRequestBody();
				$this->httpRequestVariables($this->stringDecodeForm($this->httpRequestBody));
				$this->dbUpdate();
			break;
			case "DELETE":
				$this->httpAccessControlSession();
				$this->dbDelete();
			break;
			default:
				$this->error("err008", "Programm should canceled before");
			break;
		}		
	}
	/**
	 * create db handle
	 */
	protected function dbConnect() {
		// do not create twice
		if (!isset($this->dbObject)) {
			$this->dbObject = sqlite_factory($this->dbConnectString, 0666, $err);
			if (!$this->dbObject) {
				$this->error("err001", "No Database connection ($err)");
			}
			$this->dbError();
		}		
	}
	/**
	 * execute sql query
	 * @param string
	 * @return void
	 */
	protected function dbExecute($sql) {
		$this->dbConnect();
		$this->dbObject->queryExec($sql);
		$this->dbError($sql);
	}
	/**
	 * read last inserted row id
	 * @return string
	 */
	protected function dbLastid() {
		// return value not clear if no row was inserted
		if (!($lid = $this->dbObject->lastInsertRowid())) {
			$this->dbError("err0067", "Can't read last rowid ($co)");
		}
		
		return $lid;
	}
	/**
	 * initialize class memeber by result handle of an executed, unbuffered sql query 
	 */
	protected function dbQuery($sql) {
		$this->dbConnect();
		$this->dbObject->resultHandle = $this->dbObject->unbufferedQuery($sql, SQLITE_BOTH, $err);
		// no last error, error bounded to $err by reference
		if ($err) {
			$this->error("err0065",$err);
		}
	}
	/**
	 * return all rows of a result set within an array
	 * @return array
	 */
	 protected function dbGetAll($sql) {
		$this->dbQuery($sql);
		 while(($row = $this->dbObject->resultHandle->fetch(SQLITE_ASSOC))) {
			 $rows[] = $row;
		}
		
		return isset($rows)?$rows:array();
	}	
	/**
	 * returns first value of fst row within a result set
	 * @return string
	 */
	 protected function dbGetOne($sql) {
		$this->dbQuery($sql);

		return $this->dbObject->resultHandle->fetchSingle();
	}	
	/**
	 * execute sql statements of a file
	 * @param string $file		uri of file
	 */
	public function dbLoad($file) {
		$this->dbConnect();
		if (!is_readable($file)) {
			$this->error("err005", "Can't read schema file");
		}
		// TODO add pragma  encoding=self::DB_ENCODING;
		foreach(explode(";", implode("", file($file))) as $state) {
			$this->dbObject->query($state);
		}
	}
	/**
	 * insert an object
	 */
	protected function dbInsert() {
		$this->dbExecute(sprintf("INSERT INTO %s (%s) VALUES (%s)", $this->dbRelationName(), $this->dbColumns(), $this->dbValues()));
		$this->sessionSetUserObject($this->dbLastid());
		$this->httpResponse(array(sprintf("%s", $this->dbPrimaryKey()) => $this->dbLastid()), self::HTTP_RESPONSE_201);
	}
	/**
	 * select a collection or an object
	 */
	protected function dbSelect() {
		if (isset($this->httpRequestObjectid)) {
			$where = sprintf("WHERE %s = %s", $this->dbPrimaryKey(), $this->dbPrimaryKeyId());
		} else if (is_array($this->httpRequestVariables)) {
			$where = sprintf("WHERE %s", $this->dbColumnValues());
		}
		$this->httpResponse($this->dbGetAll(sprintf("SELECT * FROM %s %s LIMIT %s", $this->dbRelationName(), isset($where)?$where:null, $this->dbMaxRows)));
	}
	/**
	 * update an object
	 */
	protected function dbUpdate() {
		if (!is_array($this->httpRequestVariables)) {
			$this->error("err088", "no datas to update");
		}
		$this->dbExecute(sprintf("UPDATE %s SET %s WHERE %s = %s", $this->dbRelationName(), $this->dbColumnValues(true), $this->dbPrimaryKey(), $this->dbPrimaryKeyId()));
		$this->httpResponse(array(sprintf("%s", $this->dbPrimaryKey()) => $this->dbPrimaryKeyId(false)));
	}
	/**
	 * delete an object
	 */
	protected function dbDelete() {
		$this->dbExecute(sprintf("DELETE FROM %s WHERE %s=%s", $this->dbRelationName(), $this->dbPrimaryKey(), $this->dbPrimaryKeyId()));	
		$this->httpResponse(array(sprintf("%s", $this->dbPrimaryKey()) => $this->httpRequestObjectid));
	}
	/**
	 * handle db errors
	 */
	protected function dbError($co=null) {
		if ($this->dbObject->lastError()) {
			$this->error("err100", sqlite_error_string($this->dbObject->lastError()."[$co]"));
		}
	}
	/**
	 * escape string for database operation
	 * @param string $str
	 * @return string
	 */
	protected function dbEscape($str) {
		return sprintf("'%s'", sqlite_escape_string($str));
	}
	/**
	 * return list of assignments of columns and values
	 * @param boolean $comma		comma-separated list
	 * @return string
	 */
	protected function dbColumnValues($comma = false) {
		if (is_array($this->httpRequestVariables)) {
			foreach($this->httpRequestVariables as $key => $value) {
				$cl[]=sprintf("%s = %s", $key, $this->dbEscape($value));
			}
			// add autofield if existing
			if ($comma && $this->dbHasColumn(self::DB_UPDATED)) {
				$cl[]=sprintf("%s = %s", self::DB_UPDATED, self::DB_NOW);
			}
			
			return implode($comma?",":" AND ", $cl);
		}
	}
	/**
	 * return comma-separated list of columns
	 * @return string
	 */
	protected function dbColumns() {
		if (!is_array($this->httpRequestVariables)) {
			$this->error("err090", "no columns given");
		}
		// add autofield always if existing
		if ($this->dbHasColumn(self::DB_CREATED)) {
			return implode(",", array_merge(array_keys($this->httpRequestVariables), array(self::DB_CREATED)));
		} else {
			return implode(",", array_keys($this->httpRequestVariables));
		}
	}
	/**
	 * return comma-separated list of columns values
	 * @return string
	 */
	protected function dbValues() {
		if (!is_array($this->httpRequestVariables)) {
			$this->error("err091", "no column values given");
		}
		foreach($this->httpRequestVariables as $key => $value) {
			$values[] = $this->dbEscape($value);
		}
		// add auto field
		if ($this->dbHasColumn(self::DB_CREATED)) {
			$values[] = self::DB_NOW;
		}
		
		return implode(",", $values);
	} 
	/**
	 * return name of objects corresponding primary key
	 * @return string
	 */
	protected function dbPrimaryKey() {

		return sprintf("%s_id", $this->stringSingular($this->httpRequestObject));
	}
	/**
	 * return value of primary key (db escaped)
	 * @param boolean $quoted
	 * @return string
	 */
	protected function dbPrimaryKeyId($quoted=true) {
		if ($this->httpRequestObjectid === null) {
			$this->error("err021", "Objectid missing");
		}

		return $quoted?$this->dbEscape($this->httpRequestObjectid):$this->httpRequestObjectid;
	}	
	/**
	 * return name of objects relation name
	 * @return string
	 */
	protected function dbRelationName() {

		return $this->httpRequestObject;
	}
	/**
	 * return a schema template
	 * @param string $str
	 */
	public function dbSchemaTemplate($str) {
		
		return sprintf("CREATE TABLE %s (\n  %s_id integer primary key,\n  created text,\n  updated text
);", $this->stringPlural($str), $this->stringSingular($str));		
	}
	/**
	 * output an error and break execution
	 * @param string $errcode 	error code
	 * @param string $errmsg	error message
	 * @param string $rcode 	response code (fst line)
	 * @param string $allow		allow directive string 	
	 */
	protected function error($errcode, $errmsg, $rcode=self::HTTP_RESPONSE_404, $allow=null) {
		switch(php_sapi_name()) {
			case "cli":
				echo "$errcode\n";
				echo "$errmsg\n\n";
			break;
			default:
				header($rcode);	
				if ($allow) {
					header($allow);
				}
				if ($this->errorVerbose) {
					echo json_Encode($this->httpResponseObject(null, $errcode, $errmsg));
				}
			break;
		}

		exit(1);
	}
	/**
	 * output http response and terminate request handling
	 * @param mixed $data		request data object
	 * @param string $status	response status line
	 */
	protected function httpResponse($data, $status=self::HTTP_RESPONSE_200) {
		header($status);
		header(sprintf('Content-Type: %s; charset="%s"', self::MEDIA_TYPE_JSON, self::HTTP_RESPONSE_ENCODING));
		echo json_encode($this->httpResponseObject($data));

		exit(0);
	}
	/**
	 * create the response object
	 * @param mixed $data		response data
	 * @param string $errcode	error code
	 * @param string $errmsg	error message
	 * @param mixed
	 */
	protected function httpResponseObject($data=null, $errcode=null, $errmsg=null) {
		if ($errcode === null) {
			$robj["status"] = self::HTTP_RESPONSE_STATUS_OK;
		} else {
			$robj["status"] = self::HTTP_RESPONSE_STATUS_ERROR;
			$robj["errCode"] = $errcode;
			$robj["errMessage"] = $errmsg;
		}
		$robj["data"] = $data;
		
		return $robj;
	}
}
?>
