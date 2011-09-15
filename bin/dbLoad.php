<?php
/**
 * dbLoad.php - load a schema into a sqlite database using RESTfooly
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
require_once('../include/RESTfooly.php');

// read params
$opt = getopt('s:d:p:r');

// load dataschema
if (isset($opt['s'])) {
	if (isset($opt['r'])) {
		ulink($opt['d']);
	}
	$rf = new RESTfooly(array('DB_CONNECT_STRING' => $opt['d']));
	$rf->dbLoad($opt['s']);	
} else if (isset($opt['p'])) {
	$rf = new RESTfooly(array());
	echo $rf->dbSchemaTemplate($opt['p']);
} else {
	echo "Usage: php dbLoad.php -p object | -s schema -d database [-r]\n";
	exit(1);
}

exit(0);
?>
