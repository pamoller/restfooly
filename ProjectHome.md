# RESTfooly #

RESTfooly is a simple HTTP handler written in PHP. It writes, read,
modify or delete custom objects using REST bindings. Use RESTfooly to
prototype or implement Web services. By default custom objects are stored in
a SQLite database. Customize RESTfooly to store objects in any manner, even
in noSQL databases. RESTfooly serves several types of access management,
including a clever session based access mechanism.
If user based authentication is needed - it can be be done as well. RESTfooly
is published under the GNU Public License V3.

## Installation ##

RESTfooly needs a HTTP Server and PHP. To install RESTfooly do:


```
$ wget https://restfooly.googlecode.com/files/restfooly-0.1.tar.gz
$ tar xzvf restfooly-0.1.tar.gz
```

If using Apache HTTP Server mod\_rewrite must be enabled. To configure the Apache HTTP Server, when PHP is used as module, place the following to the end of your httpd.conf or inside a virtual host configuration anywhere.


```
Alias /resources/ "C:/Users/pa/Documents/www/restfooly/"
<Directory "C:/Users/pa/Documents/www/restfooly">
  AllowOverride All
  AddType application/x-httpd-php .php
</Directory>
```

Keep in mind the rewrite rule in .htaccess-file within RESTfoolys base dir. The rule maps all requests to the handler.


```
RewriteEngine on 
RewriteRule ^.*$  handler.php [L]

```

## Test ##

RESTfooly includes unit tests. To execute the tests [simpletest](http://www.simpletest.org) is needed. To download simpletest and execute the tests do:


```
$ cd test
$ wget http://sourceforge.net/projects/simpletest/files/simpletest/simpletest_1.1/simpletest_1.1alpha3.tar.gz/download
$ tar xzvf simpletest_1.1alpha3.tar.gz 
$ php test.php
```

Note: The unit tests try to reach RESTfooly at http://127.0.0.1:80/resources.

## Configuration ##

RESTfooly is configured by inc/RESTfooly.inc. The documentation of the configuration paramters is given in this file. The following shows the default settings (server defaults).


```
<?php
/**
 * connect to database (path to sqlite database)
 * @var string
 */
$cfg['DB_CONNECT_STRING'] = 'include/RESTfooly.sqlite';
/**
 * remove this absolute(!) path from requested path within HTTP header
 * @var string
 */
$cfg['HTTP_BASE_URI'] = '/resources';
/**
 * inlcude error object into response body or not
 * @var boolean
 */
$cfg['ERROR_VERBOSE'] = true;
/**
 * type of access control for any client
 *
 * * 0 create, read, update or delete any object (None)
 * * 1 read any object (Read Only)
 * * 2 read or create any object (Read Write)
 * * 3 read or create any object update or delete those created in this session (Session Based)
 * * 4 custom based access control (Custom)
 *
 * NOTE: The Session Based access control is more appropriate than the Read Write setting.
 * It allows clients to correct their own input until session end. 
 *
 * @var integer
 */
$cfg['ACCESS_CONTROL'] = 0;
/**
 * maximum number of rows within a result set
 * @var integer
 */
$cfg['DB_MAX_ROWS'] = 500;
/**
 * maximum length of http param name and value
 * @var integer
 */
$cfg['DB_MAX_LENGTH'] = 2056;
?>

```

## Dataschema ##

RESTfooly stores custom objects within a custom table within a SQLite database. Therefore a data schema must be created. The following example shows the given bindings for SQL data schemas.


```
-- table name is plural!! (objects)
CREATE TABLE annotations (
  -- primary key is singular, suffix "_id" and integer!! (object_id)
  annotation_id integer primary key,
  classid integer not null,
  author varchar(64) not null,
  url varchar(512) not null,
  annotation text not null,
  -- optional automatically set on creation!
  created text,
  -- optional automatically set on update!
  updated text
);
```

To load the data schema into a sqlite database do:


```
$ cd bin
$ php dbLoad.php -s schema.sqlite -d ../include/RESTfooly.sqlite
```

To create a RESTfooly-conform SQL data schema for pumkin anyway do:


```
$ php dbLoad.php -p annotation
CREATE TABLE annotations (
  annotation_id integer primary key,
  created text,
  updated text
);
```

## Interface ##

RESTfooly covers some aspects of RESTful web services. There is a configurable base URI, a given Internet Media Type, JSON, and a set of operations using HTTP-Methods. RESTfoolys protocol may not hypermedia driven, as [suspected](http://roy.gbiv.com/untangled/2008/rest-apis-must-be-hypertext-driven), but it is useful in my opinion. The following interface description is given by HTTP-requests and responses. The JSON strings are formatted to increase the readability.

Use HTTP GET-method to query members (objects) of a collection (with a given url)


```
GET /resources/annotations?url=http://www.example.org/article2 HTTP/1.1
Host: 127.0.0.1
Connection: Close

HTTP/1.1 200 OK
Content-Length: 215
Connection: close
Content-Type: application/json; charset="utf8"

{
  "status":"ok",
  "data":
    [
      {
        "annotation_id":"2",
        "classid":"1",
        "author":"user",
        "url":"http:\/\/www.example.org\/article2",
        "annotation":"this is an odanary comment to this article",
        "created":"2011-09-08",
        "updated":null
      }
    ]
}
```

Use HTTP GET-method to query a member of a collection with annotation\_id = 1


```
GET /resources/annotations/1 HTTP/1.1
Host: 127.0.0.1
Connection: Close

HTTP/1.1 200 OK
Content-Length: 221
Connection: close
Content-Type: application/json; charset="utf8"

{
  "status":"ok",
  "data":
    [
      {
        "annotation_id":"1",
        "classid":"2",
        "author":"you",
        "url":"http:\/\/www.example.com\/article",
        "annotation":"this is no odanary comment to this article",
        "created":"2011-09-08",
        "updated":"2011-09-08"
      }
    ]
}
```

Use HTTP POST-method to create a new member


```
POST /resources/annotations HTTP/1.1
Host: 127.0.0.1
Content-Type: application/x-www-form-urlencoded
Content-Length: 110
Connection: Close

classid=1&author=user&url=http://www.example.org/article&annotation=this is an odanary comment to this article

HTTP/1.1 201 Created
Content-Length: 42
Connection: close
Content-Type: application/json; charset="utf8"

{
  "status":"ok",
  "data":
    {
      "annotation_id":3
    }
}
```

Use HTTP PUT-method to update the member with annotation\_id = 1


```
PUT /resources/annotations/1 HTTP/1.1
Host: 127.0.0.1
Content-Type: application/x-www-form-urlencoded
Content-Length: 109
Connection: Close

classid=2&author=you&url=http://www.example.com/article&annotation=this is no odanary comment to this article

HTTP/1.1 200 OK
Content-Length: 46
Connection: close
Content-Type: application/json; charset="utf8"

{
  "status":"ok",
  "data":
    {
      "annotation_id":"'1'"
    }
}
```

Use HTTP DELETE-method to  delete member with annotation\_id = 2


```
DELETE /resources/annotations/2 HTTP/1.1
Host: 127.0.0.1
Connection: Close

HTTP/1.1 200 OK
Content-Length: 44s
Connection: close
Content-Type: application/json; charset="utf8"

{
  "status":"ok",
  "data":
    {
      "annotation_id":"2"
    }
}
```

## Tutorial ##

Setting ACCESS\_CONTROL = 4 enables Custom Access Control. With this setting a User Authentication can be implemented.


```
<?php

require_once("RESTfooly.php");

class rfAuthen extends RESTfooly {

  /**
   *  overwrite custom access control method
   */
  protected function httpAccessControlCustom() {
    // start session
    session_start();
    // user is loged in
    if (isset($_SESSION["login"])) {

      return;
    }
    // user tries login
    else if (isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"]) && $this->dbLoginValid(["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"])) {
      $_SESSION["login"] = true;
		
      return;
    }

    // authentication required
    header('WWW-Authenticate: Basic realm="RESTfooly"');
    header("HTTP/1.0 401 Unauthorized");
    exit(1);
  }
  
  /**
   * check for login
   * @param string $login
   * @param string $passwd
   */
  protected function dbLoginValid($login, $passwd) {
    
    return isset($this->dbGetOne("SELECT login FROM accounts WHERE login=%s AND passwd=%s", $this->dbEscape($login), $this->dbEscape(md5($passwd)))))?true:false;
  }
}?>
```

## Roadmap ##

RESTfooly shall include a pageing mechanism. The media type of responses shall be extended to more than JSON. HTML-representation shall be generated on the fly by XSLT. RESTfooly shall become a RESTful webservice. The default database shall become sqlite3. RESTfooly may extensible for JSON-Requests.
