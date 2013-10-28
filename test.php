<?php

require("openmeteodata.class.php");

$OMD = new OpenMeteoData ();

/*
We advise you to provide a contact email.
This is not mandatory, but it can be really usefull
in case we get a problem with your requests.
That way, we will be able to contact you instead of
blocking your IP in a dumb way.
Your email will be kept secure, and is never transmitted
to someone else
We will email you only in case of a problem we need to solve.
*/
$OMD->setContactEmail('dsqdsq@sqdq.com');



/*
$domains=$OMD->getDomains();
print_r($domains);
*/

$OMD->setDomain('eu12');


$runs=$OMD->getRuns();
print_r($runs);


//$OMD->setRun('201310212');
$OMD->setRun($runs[0]);

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
