<?php

require("openmeteodata.class.php");

$OMD = new OpenMeteoData ();
$OMD->setContactEmail('dsqdsq@sqdq.com');

/*
$domains=$OMD->getDomains();
print_r($domains);
*/

$OMD->setDomain('eu12');


/*$runs=$OMD->getRuns();
print_r($runs);*/


$OMD->setRun('2013102712');

/*
$frames=$OMD->getFrames();
print_r($frames);
*/

/*
$vars = $OMD->getVariables();
print_r($vars);
*/

/*
$atts = $OMD->getAttributes();
print_r($atts);
*/

/*
$dims = $OMD->getDimensions();
print_r($dims);
*/

/*
$types = $OMD->getTypes();
print_r($types);
*/


/*
$mlevels=$OMD->getModelLevels();
print_r($mlevels);
*/

/*
$plevels=$OMD->getPressureLevels();
print_r($plevels);
*/

/*
$alevels=$OMD->getAltitudeLevels();
print_r($alevels);
*/

/*
$lat = 45;
$lon = 5;
$xy = $OMD->latlon_to_xy($lat, $lon);
print_r($xy);
*/

/*
// $OMD->getArray($var, $frame, [xStart,xInc,xStop],[yStart,yInc,yStop],[zStart,zInc,zstop]);
$data = $OMD->getArray('pblh', 1, array(300,305), array(300,306));
print_r($data);
*/

/*
// $OMD->getPoint($var, $frame, [x, y, z]);
$data = $OMD->getPoint('press', 1, array(45, 100, 6));
echo "$data\n";
*/
