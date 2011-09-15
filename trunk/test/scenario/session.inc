<?php
/**
 * connect to database (path to sqlite database)
 * @var string
 */
$cfg['DB_CONNECT_STRING'] = 'include/restfooly.sqlite';
/**
 * cut off from request uri
 * @var string
 */
$cfg['HTTP_BASE_URI'] = '/resources/session';
/**
 * inlcude error object in response body
 * @var boolean
 */
$cfg['ERROR_VERBOSE'] = true;
/**
 * type of access control * @var integer
 */
$cfg['ACCESS_CONTROL'] = 3;
/**
 * maximum number of rows within a result set
 * @var integer
 */
$cfg['DB_MAX_ROWS'] = 1;
/**
 * maximum length of http param name and value
 * @var integer
 */
$cfg['DB_MAX_LENGTH'] = 30;
?>
