<?php
/*

This scripts plot a simple map from OpenMeteoData forecasts.

*/


// --- get data from OpenMeteoData -------------------------
require("openmeteodata.class.php");


$OMD = new OpenMeteoData ();
$OMD->setContactEmail('dsqdsq@sqdq.com');

$OMD->setDomain('eu12');


$runs=$OMD->getRuns();
//print_r($runs);
//$OMD->setRun('2013102800');

$OMD->setRun($runs[1]);


//$frames=$OMD->getFrames();
//print_r($frames);

$frame=5; // frame number = 0 to 72 hours for currents eu12 runs
$variable='temp2m'; // make sure you choose a 2D array.


$data = $OMD->getArray($variable, $frame);


$atts = $OMD->getAttributes($frame);

$legend_name  = $atts[$variable]['long_name'];
$legend_units = $atts[$variable]['units'];
$legend_frame = $atts['NC_GLOBAL']['frame'];
$legend_run   = $atts['NC_GLOBAL']['run'];


// ------ create the image -------------------------------

$width=count($data);
$height=count($data[0]);


$im = imagecreate($width, $height)
    or die("Cannot Initialize new GD image stream");
    
// ------ set the colors --------------------------------

$palette=array(
  //    val                           R    G    B
  array(0,    imagecolorallocate($im, 0,   0,   255) ),
  array(2.5,  imagecolorallocate($im, 0,   85,  255) ),
  array(5,    imagecolorallocate($im, 0,   170, 255) ),
  array(7.5,  imagecolorallocate($im, 0,   255, 255) ),
  array(10,   imagecolorallocate($im, 3,   230, 175) ),
  array(12.5, imagecolorallocate($im, 5,   204, 95 ) ),
  array(15,   imagecolorallocate($im, 8,   179, 15 ) ),
  array(17.5, imagecolorallocate($im, 88,  204, 10 ) ),
  array(20,   imagecolorallocate($im, 168, 228, 5  ) ),
  array(22.5, imagecolorallocate($im, 248, 253, 0  ) ),
  array(25,   imagecolorallocate($im, 255, 228, 0  ) ),
  array(27.5, imagecolorallocate($im, 255, 198, 0  ) ),
  array(30,   imagecolorallocate($im, 255, 168, 0  ) ),
  array(32.5, imagecolorallocate($im, 255, 115, 0  ) ),
  array(35,   imagecolorallocate($im, 255, 60,  0  ) ),
  array(37.5, imagecolorallocate($im, 255, 5,   0  ) ),
  array(40,   imagecolorallocate($im, 204, 0,   41 ) )
  );



function getColor($val, $palette) {
  $ncolors = count($palette)-1;
  $i=0;
  for (; $i<$ncolors; $i++) {
    if($val < $palette[$i+1][0]) break;
  }
  return $palette[$i][1];
}


// ------ draw the map --------------------------------

for ($y=0; $y<$height; $y++) {
  for ($x=0; $x<$width; $x++) {
    $pixel = getColor($data[$x][$y], $palette);
    imagesetpixel($im, $x, $height-1-$y, $pixel);
    // $height-1 because img coordinates are reverse.
  }
}

// ------ add countries borders --------------------------------
$countries_im = imagecreatefrompng('countries.png');
$countries_color=imagecolorallocate($im, 0, 0 ,0);

for ($y=0; $y<$height; $y++) {
  for ($x=0; $x<$width; $x++) {
    if (imagecolorat($countries_im, $x, $y) == 0) { // black x000000
      imagesetpixel($im, $x, $y, $countries_color);
    }
  }
}

imagedestroy($countries_im);

// ------ add legend -----------------------


$textcolor = imagecolorallocate($im, 255, 255, 255);
$backcolor = imagecolorallocate($im, 0, 0, 0);

imagefilledrectangle($im, 0, 0, 200, 50, $backcolor);

imagestring($im, 2, 4, 0, $legend_name, $textcolor);
imagestring($im, 2, 4, 12, "valid $legend_frame", $textcolor);
imagestring($im, 2, 4, 24, "run   $legend_run", $textcolor);
imagestring($im, 2, 4, 36, "data  ODC-By openmeteodata.org", $textcolor);

// ---- write the file -----

imagepng($im, 'test_map.png');
//imagejpeg(im, 'test_map.jpg');
imagedestroy($im);