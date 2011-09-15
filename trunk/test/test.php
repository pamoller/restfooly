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
require_once("simpletest/autorun.php");
require_once("../include/RESTfooly.php");

class TestRESTfooly extends UnitTestCase {

	const HTTP_200 = "HTTP/1.1 200 OK";
	const HTTP_201 = "HTTP/1.1 201 CREATED";
	const HTTP_401 = "HTTP/1.1 401 UNAUTHORIZED";
	const HTTP_404 = "HTTP/1.1 404 NOT FOUND";
	const HTTP_405 = "HTTP/1.1 405 METHOD NOT ALLOWED";

	const TEST_DOMAIN = "127.0.0.1";

	/**
	 * keep default database and configuration
	 * 
	 */	
	function __construct() {
		// set the document encoding of your php scripts to internal encoding 
		// or set encoding as soon as possible to document encoding (best utf-8)!!
		mb_internal_encoding('UTF-8');
		@copy("../include/RESTfooly.inc", "../include/RESTfooly.inc.bak");
		@copy("../include/RESTfooly.sqlite", "../include/RESTfooly.sqlite.bak");
	}
	/**
	 * replay default database and configuration
	 */
	function __destruct() {
		@copy("../include/RESTfooly.inc.bak", "../include/RESTfooly.inc");
		@unlink("../include/RESTfooly.inc.bak");
		@copy("../include/RESTfooly.sqlite.bak", "../include/RESTfooly.sqlite");
		@unlink("../include/RESTfooly.sqlite.bak");
	}
	/**
	 * test server default settings (as possible)
	 */	
	function test_server_defaults() {
		$rf = new RestFooly(array("DB_CONNECT_STRING" => "../include/RESTfooly.sqlite"));
		// init database
		@unlink("../include/RESTfooly.sqlite");
		$rf->dbLoad("test.sqlite");
		$this->assertTrue(file_exists("../include/RESTfooly.sqlite"));
    }
	/**
	 * test defaults from default configuration
	 */
	function test_default() {
		// copy default settings
		copy("scenario/default.inc", "../include/RESTfooly.inc");
		// use not allowed method
		$resp= $this->httpRequestDo(self::TEST_DOMAIN, "/resources/annotations", "GGET");
		$this->expectResponseCode(405, $resp);
		// get an empty collection
		$resp= $this->httpRequestDo(self::TEST_DOMAIN, "/resources/annotations");
		$this->expectResponseCode(200, $resp);
		// get a unkown collection
		$resp  = $this->httpRequestDo(self::TEST_DOMAIN, "/resources/annotationss");
		$this->expectResponseCode(404, $resp);
		$this->expectErrorCode("err089", $resp);
		// post successfully
		$resp = $this->httpRequestDo(self::TEST_DOMAIN,"/resources/annotations", "POST", "classid=1&author=user&url=http://www.example.org/article&annotation=this is an odanary comment to this article");
		$this->expectResponseCode(201, $resp);
		$this->assertEqual("1", $resp["BodyParsed"]["data"]["annotation_id"]);
		// post successfully different url
		$resp = $this->httpRequestDo(self::TEST_DOMAIN,"/resources/annotations", "POST", "classid=1&author=user&url=http://www.example.org/article2&annotation=this is an odanary comment to this article");
		$this->expectResponseCode(201, $resp);
		$this->assertEqual("2", $resp["BodyParsed"]["data"]["annotation_id"]);
		// get collection
		$resp = $this->httpRequestDo(self::TEST_DOMAIN,"/resources/annotations");
		$this->expectResponseCode(200, $resp);
		$this->expectDataCount(2, $resp);
		// get by objectid
		$resp = $this->httpRequestDo(self::TEST_DOMAIN, "/resources/annotations/2");
		$this->expectResponseCode(200, $resp);
		$this->assertEqual("2", $resp["BodyParsed"]["data"][0]["annotation_id"]);
		// get by url param url
		$resp = $this->httpRequestDo(self::TEST_DOMAIN,"/resources/annotations?url=http://www.example.org/article");
		$this->expectResponseCode(200, $resp);
		$this->expectDataCount(1, $resp);
		// get by url param url with different value
		$resp = $this->httpRequestDo(self::TEST_DOMAIN,"/resources/annotations?url=http://www.example.org/article2");
		$this->expectResponseCode(200, $resp);
		$this->expectDataCount(1, $resp);
		// get not existing object
		$resp = $this->httpRequestDo(self::TEST_DOMAIN, "/resources/annotations/4");
		$this->expectResponseCode(200, $resp);
		$this->expectDataCount(0, $resp);
		// get non existing column
		$resp = $this->httpRequestDo(self::TEST_DOMAIN,"/resources/annotations?clossid=0");
		$this->expectResponseCode(404, $resp);
		$this->expectErrorCode("err072", $resp);
		// post wrong param
		$resp = $this->httpRequestDo(self::TEST_DOMAIN,"/resources/annotations", "POST", "clossid=1&author=user&url=http://www.example.org/article&annotation=this is an odanary comment to this article");
		$this->expectResponseCode(404, $resp);
		$this->expectErrorCode("err072", $resp);
		// delete object
		$resp = $this->httpRequestDo(self::TEST_DOMAIN,"/resources/annotations/2", "DELETE");
		$this->expectResponseCode(200, $resp);
		$this->assertEqual(2, $resp["BodyParsed"]["data"]["annotation_id"]);
		// query all
		$resp = $this->httpRequestDo(self::TEST_DOMAIN, "/resources/annotations");
		$this->expectResponseCode(200, $resp);
		$this->expectDataCount(1, $resp);
		// update one
		$resp = $this->httpRequestDo(self::TEST_DOMAIN, "/resources/annotations/1", "PUT", "classid=2&author=you&url=http://www.example.com/article&annotation=this is no odanary comment to this article");
		$this->expectResponseCode(200, $resp);
		$this->expectDataCount(1, $resp);
		// get not existing object
		$resp = $this->httpRequestDo(self::TEST_DOMAIN, "/resources/annotations/1");
		$this->expectResponseCode(200, $resp);
		$this->assertEqual("you", $resp["BodyParsed"]["data"][0]["author"]);
		// delete non existing object
		$resp = $this->httpRequestDo(self::TEST_DOMAIN,"/resources/annotations/2", "DELETE");
		$this->expectResponseCode(200, $resp);
		$this->assertEqual(2, $resp["BodyParsed"]["data"]["annotation_id"]);
		// NOTE: post not null column with null value
		//$resp = $this->httpRequestDo(TEST_DOMAIN,"/annotations", "POST", "author=user&url=http://www.example.org/article&annotation=this is an odanary comment to this article");
		//$this->resp_404($resp);
		//$this->assertEqual("err072", $resp["BodyParsed"]["err_code"]);
		// get same url
	}
	/**
	 * test read only access control
	 */
	function test_read() {
		// copy read
		copy("scenario/read.inc", "../include/RESTfooly.inc");
		// get an empty collection
		$resp= $this->httpRequestDo(self::TEST_DOMAIN, "/resources/read/annotations");
		$this->expectResponseCode(200, $resp);
		// post successfully
		$resp = $this->httpRequestDo(self::TEST_DOMAIN,"/resources/read/annotations", "POST", "classid=1&author=user&url=http://www.example.org/article&annotation=this is an odanary comment to this article");
		$this->expectResponseCode(405, $resp);
		// delete object
		$resp = $this->httpRequestDo(self::TEST_DOMAIN,"/resources/read/annotations/2", "DELETE");
		$this->expectResponseCode(405, $resp);
		// update one
		$resp = $this->httpRequestDo(self::TEST_DOMAIN, "/resources/read/annotations/1", "PUT", "classid=2&author=you&url=http://www.example.com/article&annotation=this is no odanary comment to this article");
		$this->expectResponseCode(405, $resp);
	}
	/**
	 * test read and write access control
	 */
	function test_write() {
		copy("scenario/write.inc", "../include/RESTfooly.inc");
		// get an empty collection
		$resp= $this->httpRequestDo(self::TEST_DOMAIN, "/resources/write/prefix/annotations");
		$this->expectResponseCode(200, $resp);
		// post successfully
		$resp = $this->httpRequestDo(self::TEST_DOMAIN,"/resources/write/prefix/annotations", "POST", "classid=1&author=user&url=http://www.example.org/article&annotation=this is an odanary comment to this article");
		$this->expectResponseCode(201, $resp);
		// delete object
		$resp = $this->httpRequestDo(self::TEST_DOMAIN,"/resources/write/prefix/annotations/2", "DELETE");
		$this->expectResponseCode(405, $resp);
		// update one
		$resp = $this->httpRequestDo(self::TEST_DOMAIN, "/resources/write/prefix/annotations/1", "PUT", "classid=2&author=you&url=http://www.example.com/article&annotation=this is no odanary comment to this article");
		$this->expectResponseCode(405, $resp);
	}
	/**
	 * test session based access control
	 */
	function test_session() {
		// test sessions
		copy("scenario/session.inc", "../include/RESTfooly.inc");
		// get collection
		$resp= $this->httpRequestDo(self::TEST_DOMAIN, "/resources/session/annotations");
		$this->expectResponseCode(200, $resp);
		// post successfully
		$resp = $this->httpRequestDo(self::TEST_DOMAIN,"/resources/session/annotations", "POST", "classid=1&author=user&url=http://www.example.org/article&annotation=this is an odanary comment");
		// keep id of own object
		$my = $resp["BodyParsed"]["data"]["annotation_id"];
		// keep session id
		$co = $resp["Set-Cookie"];
		// expect a cookie string
		$this->assertTrue((strlen($co) > 0));
		// expect annotation_id
		$this->assertEqual(3, $my);
		$this->expectResponseCode(201, $resp);
		// get collection limited to one row DB_MAX_ROWS
		$resp= $this->httpRequestDo(self::TEST_DOMAIN, "/resources/session/annotations");
		$this->expectResponseCode(200, $resp);
		$this->expectDataCount(1, $resp);
		// update one
		$resp = $this->httpRequestDo(self::TEST_DOMAIN, "/resources/session/annotations/1", "PUT", "classid=2&author=you&url=http://www.example.com/article&annotation=this is no odanary comment");
		$this->expectResponseCode(401, $resp);
		// delete object
		$resp = $this->httpRequestDo(self::TEST_DOMAIN,"/resources/session/annotations/1", "DELETE",null, "$co-invalid");
		$this->expectResponseCode(401, $resp);
		// update one
		$resp = $this->httpRequestDo(self::TEST_DOMAIN, sprintf("/resources/session/annotations/%s", $my), "PUT", "classid=2&author=you&url=http://www.example.com/article&annotation=this is an modified comment", $co);
		$this->expectResponseCode(200, $resp);
		// delete object
		$resp = $this->httpRequestDo(self::TEST_DOMAIN, sprintf("/resources/session/annotations/%s", $my), "DELETE", null, $co);
		$this->expectResponseCode(200, $resp);
		// post too long value
		$resp = $this->httpRequestDo(self::TEST_DOMAIN,"/resources/session/annotations", "POST", "classid=1&author=user&url=http://www.example.org/article&annotation=this is an non odanary comment to this article, that exceeds the allowed length of a string!");
		$my = $resp["BodyParsed"]["data"]["annotation_id"];
		$this->expectResponseCode(404, $resp);
		$this->expectErrorCode("err081", $resp);
	}
	/**
	 * test defaults from default configuration
	 */
	function test_encoding() {
		// copy default settings
		copy("scenario/default.inc", "../include/RESTfooly.inc");
		// post to test server
		$resp = $this->httpRequestDo(self::TEST_DOMAIN,"/resources/annotations", "POST", "classid=1&author=user&url=http://www.example.org/article&annotation=Schöne Äpfel aus Überlingen");
		$resp = $this->httpRequestDo(self::TEST_DOMAIN, sprintf("/resources/annotations/%s", $resp["BodyParsed"]["data"]["annotation_id"]));
		$this->assertEqual(mb_convert_encoding("Schöne Äpfel aus Überlingen", "UTF8"), $resp["BodyParsed"]["data"][0]["annotation"]);
		// post successfully different encoding
		$resp = $this->httpRequestDo(self::TEST_DOMAIN,"/resources/annotations", "POST", "classid=1&author=user&url=http://www.example.org/article&annotation=Schöne Äpfel aus Überlingen", null, "UTF-8");
		$resp = $this->httpRequestDo(self::TEST_DOMAIN, sprintf("/resources/annotations/%s", $resp["BodyParsed"]["data"]["annotation_id"]));
		$this->assertEqual(mb_convert_encoding("Schöne Äpfel aus Überlingen", "UTF8"), $resp["BodyParsed"]["data"][0]["annotation"]);
		// put another  
		$resp = $this->httpRequestDo(self::TEST_DOMAIN, sprintf("/resources/annotations/%s", $resp["BodyParsed"]["data"][0]["annotation_id"]), "PUT", "classid=2&author=you&url=http://www.example.com/article&annotation=Schöne Äpfelchen aus Überlingen", null, "UTF-8");
		$resp = $this->httpRequestDo(self::TEST_DOMAIN, sprintf("/resources/annotations/%s", $resp["BodyParsed"]["data"]["annotation_id"]));
		$this->assertEqual(mb_convert_encoding("Schöne Äpfelchen aus Überlingen", "UTF8"), $resp["BodyParsed"]["data"][0]["annotation"]);
	}

	/**
	 * check for errcode
	 */
	function expectErrorCode($errcode, $resp) {
		$this->assertEqual($errcode, $resp["BodyParsed"]["errCode"]);	
	}
	/**
	 * count number of datasets
	 */
	function expectDataCount($count, $resp) {
		$this->assertEqual($count, count($resp["BodyParsed"]["data"]));
	}
	/**
	 * expect response code
	 */
	function expectResponseCode($rcode, $resp) {
		print_r($resp);
		switch($rcode) {
			case 200:
				$this->assertEqual("ok", $resp["BodyParsed"]["status"]);
				$this->assertEqualI(self::HTTP_200, $resp["Code"]);
			break;
			case 201:
				$this->assertEqual("ok", $resp["BodyParsed"]["status"]);
				$this->assertEqualI(self::HTTP_201, $resp["Code"]);
			break;
			case 401:
				$this->assertEqualI(self::HTTP_401, $resp["Code"]);
			break;
			case 404:
				$this->assertEqualI(self::HTTP_404, $resp["Code"]);
			break;
			case 405: 
				$this->assertEqualI(self::HTTP_405, $resp["Code"]);
			break;
		}
	}
	/**
	 * do equal operation case insesetive
	 * @parmas string $a
	 * @params string $b
	 */
	function assertEqualI($a, $b) {
		$this->assertEqual(strtoupper($a), strtoupper($b));
	}

	/**
	 * prepare http request
	 * @param string $host		requested domain
	 * @param string $ruri		requested uri
	 * @param string $method	request method
	 * @param string $body		request body
	 * @param string $cookie	cookie string
	 */	
	public function httpRequestDo($host, $ruri, $method="GET", $body=null, $cookie=null, $charset="ISO-8859-1") {
		if (isset($body) && in_array($method, array("GET", "DELETE"))) {
			echo "No body allowed in GET or DELETE requests";
			exit(1);
		}
		$req = sprintf("%s %s HTTP/1.1\r\n", $method, $ruri);
		$req.= sprintf("Host: %s\r\n", $host);
		if (isset($body)) {
			$body = iconv("UTF-8", $charset, $body);
			$req.= sprintf("Content-Type: application/x-www-form-urlencoded; charset=%s\r\n", $charset);
			$req.= sprintf("Content-Length: %s\r\n", strlen($body));			
		}
		if(isset($cookie)) {
			$req.= sprintf("Cookie: %s\r\n", $cookie);
		}
		$req.= "Connection: Close\r\n\r\n"; 
		if ($body) {
			$req.= $body;
		}
		
		return $this->httpRequestExecute($host, $req);
	}
	/**
	 * do http reqeust and evaluate response
	 * @param string $req
	 * @return array
	 */
	protected function httpRequestExecute($host, $req=null, $port=80) {
		print_r($req);
		$fp = fsockopen($host, $port, $errno, $errmsg, 5);
		if (!$fp) {
			echo "$errmsg ($errno)";	
			exit(1);
		} else {
			fwrite($fp, $req);
			$resp="";
			while(!feof($fp)) {
				$resp.=fgets($fp, 128);
			}
			fclose($fp);
			print_r($resp);
			// parse the response
			$parts=explode("\r\n\r\n", $resp);
			if (!isset($parts[0])) {
				echo "protocol error";
			}
			$header = explode("\r\n", $parts[0]);
			$respObj["Code"] = $header[0];
			$respObj["BodyStr"] = $parts[1];
			$respObj["BodyParsed"] = json_decode($parts[1], true);
			for($i=1; $i<count($header); $i++) {
				$parts=explode(":", $header[$i]);
				if (!isset($parts[1])) {
					$this->error("err301", "protocol error");
				}
				if (strtoupper($parts[0]) === strtoupper("Set-Cookie")) {
					$parts[1]=preg_replace("/\s*;.*$/", "", $parts[1]);
				}
					
				$respObj[$parts[0]]=$parts[1];
			}
			
			return $respObj;	
		}		
	}
	/**
	 * write config file
	 */
	/*public static function createConfig() {
		echo "<?php\n";
		echo "/**
		echo "* connect to database (path to sqlite database)
		echo "* @var string
		echo "*\/
		echo "$cfg['DB_CONNECT_STRING'] = 'include/RESTfooly.sqlite';
		echo "/**
		echo "* cut off from request uri
		echo "* @var string
		echo "*\/
		echo "$cfg['HTTP_BASE_URI'] = '/resources';
		echo "/**
		echo "* enable error_verboseging
		echo "* @var boolean
		echo "*\/
		echo "$cfg['ERROR_VERBOSE'] = true;
		echo "/**
		echo "* type of access control * @var integer
		echo "*\/
		echo "$cfg['ACCESS_CONTROL'] = 0;
		echo "/**
		echo "* maximum number of rows within a result set
		echo "* @var integer
		echo "*\/
		echo "$cfg['DB_MAX_ROWS'] = 500;
		echo "/**
		echo "* maximum length of http param name and value
		echo "* @var integer
		echo "*\/
		echo "$cfg['DB_MAX_LENGTH'] = 2056;
		echo "?>
		
	}*/

}
?>
