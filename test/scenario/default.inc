<?php
/**
 * connect to database (path to sqlite database)
 * @var string
 */
$cfg['DB_CONNECT_STRING'] = 'include/restfooly.sqlite';
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
