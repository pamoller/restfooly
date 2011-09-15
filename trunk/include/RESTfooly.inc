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
