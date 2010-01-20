<?php

$svn_info = '$Id: inc.version.php 171 2009-08-09 23:36:31Z cchristensen $';

preg_match('/\$\w+: [-_\w\d.]+ (\d+) (\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}).*\$/', $svn_info, $matches);

// just so I don't have to edit files
// that have no other changes but this
$GLOBALS['_VERSION'] = '0.8.0';
$GLOBALS['_UPDATED'] = strtotime($matches[2]);
$GLOBALS['_REVISION'] = (int) $matches[1];

// and some random text so I have something in this file to change
/*

Hello World !

*/

