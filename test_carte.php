<?php

require("openmeteodata.class.php");

$OMD = new OpenMeteoData ();
$OMD->setContactEmail('dsqdsq@sqdq.com');

$OMD->setDomain('eu12');

$runs=$OMD->getRuns();

$OMD->setRun($runs);

