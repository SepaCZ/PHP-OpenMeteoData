<?php

/*
openmeteodata.class.php - A PHP class for easy use of OpenMeteoData forecast data.
2013 Nicolas BALDECK <nicolas.baldeck@openmeteodata.org>
This file is licenced under the DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE.
For Help and Documentation, see http://wiki.openmeteodata.org/wiki/openmeteodata.class.php
For contributing or submit bugs, see http://github.com/OpenMeteoData/PHP-OpenMeteoData
*/

require('opendap.class.php');

class OpenMeteoData {


  private $_ck=FALSE;
  private $_cacheDir="./cache";
  private $_domain=FALSE;
  private $_run=FALSE;
  private $_files=array();
  private $_proj=FALSE;
  
  
  public function setContactEmail ($email) {
  
    $emailsCache=array();
    $cacheFile=$this->_cacheDir."/contactkey.txt";
    
    if (file_exists($cacheFile)) {
      $json=file_get_contents($cacheFile);
      $emailsCache=json_decode($json, TRUE);
    }
    
    if (array_key_exists($email, $emailsCache)) {
      $this->_ck=$emailsCache[$email];
    } else {
      $response=file_get_contents('https://api.omd.li/getcontactkey?email='.urlencode($email));
      if ($response === false || substr($response, 0, 12) != 'contact_key=') return;
      $this->_ck=trim(substr($response, 12));
      $emailsCache[$email]=$this->_ck;
      $json=json_encode($emailsCache);
      if (file_put_contents($cacheFile, $json) === FALSE) {
	throw new Exception("Cannot write to cache file : $cacheFile");
      }
    }
  
  }
  
  private function _ckUrl() {
    if ($this->_ck) {
      return '&_ck='.$this->_ck;
    } else {
      return '';
    }
  }
  
  private function _noCacheUrl() {
    return '&_'.dechex(time());
  }
  
  public function getDomains () {
    $response=file_get_contents('http://api.omd.li/domains/list?'.$this->_ckUrl().$this->_noCacheUrl());
    if ($response === false) {
      throw new Exception("Cannot get domains list");
    }
    return json_decode($response, TRUE);
  }
  
  public function setDomain ($domain) {
    $this->_domain=$domain;
  }

  public function getRuns () {
    $url='http://api.omd.li/runs/list?';
    if ($this->_domain) {
     $url.='domain='.urlencode($this->_domain);
    }
    $url.=$this->_ckUrl().$this->_noCacheUrl();
    $response=file_get_contents($url);
    if ($response === false) {
      throw new Exception("Cannot get runs list");
    }
    return json_decode($response, TRUE);
  }

  public function setRun ($run) {
    $this->_run=$run;
  }
  
  public function getFrames() {
    $this->_checkRunDomain();
    
    $url='http://api.omd.li/files/list?';
    $url.= 'domain='.urlencode($this->_domain);
    $url.= '&run='.urlencode($this->_run);
    $url.=$this->_ckUrl().$this->_noCacheUrl();
    $response=file_get_contents($url);
    if ($response === false) {
      throw new Exception("Cannot get runs list");
    }
    $files=json_decode($response, TRUE);
    
    $frames=array();
    
    date_default_timezone_set('UTC');
    
    $run_year=substr($this->_run, 0, 4);
    $run_month=substr($this->_run, 4, 2);
    $run_day=substr($this->_run, 6, 2);
    $run_hour=substr($this->_run, 8, 2);
    $run_time=mktime ($run_hour, 0, 0, $run_month, $run_day, $run_year);
    
    $pattern='/[\w]+-pp_\d{10}_(?P<frame>\d+)\.nc/';
    foreach($files as $filename) {
      if (preg_match($pattern, $filename, $matches) == 1) {
      
	$frame=array();
	
	$n=$matches['frame'];
	
	$time=$run_time+$n*3600; //TODO: check how many time between frames.
	
	$frame['n']=$n;
	$frame['file']=$filename;
	$frame['time']=date("Y-m-d H:i:s e", $time);
	
	$frames[$n]= $frame;
      }
    }
    ksort($frames);
    return $frames;
  }
  
  private function _checkRunDomain() {
    if (!$this->_domain) throw new Exception("Domain is not set");
    if (!$this->_run) throw new Exception("Run is not set");
  }
  
  
  private function _openFile($frame) {
    if (!array_key_exists($frame, $this->_files)) {
      $this->_files[$frame]=new OPeNDAP($this->_getFileUrl($frame), $this->_ck);
    }
  }
  
  private function _getFileUrl ($frame=0) {
    $this->_checkRunDomain();
    return 'http://dap.omd.li/'.$this->_domain.'-pp_'.$this->_run.'_'.$frame.'.nc';
  }
  
  public function getVariables () {
    $this->_openFile(0);
    return $this->_files[0]->getVariables();
  }
  
  public function getAttributes ($frame=0) {
    $this->_openFile($frame);
    return $this->_files[$frame]->getAttributes();
  }
  
  public function getDimensions () {
    $this->_openFile(0);
    return $this->_files[0]->getDimensions();
  }
  
  public function getTypes () {
    $this->_openFile(0);
    return $this->_files[0]->getTypes();
  }
  
  public function getModelLevels () {
    $this->_openFile(0);
    return $this->_files[0]->getArray('model_level');
  }
  
  public function getPressureLevels () {
    $this->_openFile(0);
    return $this->_files[0]->getArray('press_level');
  }
  
  public function getAltitudeLevels () {
    $this->_openFile(0);
    return $this->_files[0]->getArray('alti_level');
  }
  
  public function getArray ($var, $frame, $xsub=FALSE, $ysub=FALSE, $zsub=FALSE) {
    $this->_openFile($frame);
    return $this->_files[$frame]->getArray($var, $xsub, $ysub, $zsub);
  }
  
  public function getPoint ($var, $frame, $coords) {
    $this->_openFile($frame);
    $ncoords=count($coords);
    switch ($ncoords) {
      case 1:
	$val = $this->_files[$frame]->getArray($var, array($coords[0],$coords[0]+1));
	return $val[0];
	break;
      case 2:
	$val = $this->_files[$frame]->getArray($var, array($coords[0],$coords[0]+1), array($coords[1],$coords[1]+1));
	return $val[0][0];
	break;
      case 3:
	$val = $this->_files[$frame]->getArray($var, array($coords[0],$coords[0]+1), array($coords[1],$coords[1]+1), array($coords[2],$coords[2]+1));
	return $val[0][0][0];
	break;
      default:
	throw new Exception("Bad x, y, z argument");
    }
  }
  
  public function latlon_to_xy ($lat, $lon) {
    // from http://www.mmm.ucar.edu/wrf/src/read_wrf_nc.f
    $this->_init_proj();
    $p = $this->_proj;
    
    //! Compute deltalon between known longitude and standard lon and ensure
    //! it is not in the cut zone
    $deltalon = $lon - $p['longitude_of_central_meridian'];
    if ($deltalon > 180.) {
      $deltalon = $deltalon - 360.;
    } else if ($deltalon < -180.) {
      $deltalon = $deltalon + 360.;
    }
    //! Radius to desired point
    $rm = $p['rebydx'] * $p['ctl1r']/$p['cone'] * pow(
	  (tan((90.*$p['hemi']-$lat)*$p['rad_per_deg']/2.) /
	  tan((90.*$p['hemi']-$p['standard_parallel_1'])*$p['rad_per_deg']/2.)), $p['cone']);

    $arg = $p['cone']*($deltalon*$p['rad_per_deg']);
    $i = $p['polei'] + $p['hemi'] * $rm * sin($arg);
    $j = $p['polej'] - $rm * cos($arg);
    
    //! Finally, if we are in the southern hemisphere, flip the i/j
    //! values to a coordinate system where (1,1) is the SW corner
    //! (what we assume) which is different than the original NCEP
    //! algorithms which used the NE corner as the origin in the 
    //! southern hemisphere (left-hand vs. right-hand coordinate?)
    $i = $p['hemi'] * $i;
    $j = $p['hemi'] * $j;
    
    //! check if we are on the grid
    $xerr=0;
    $yerr=0;
    
    if ($i<0) {
      $xerr=$i*$p['grid_dx'];
      $i=0;
    } else if ($i >= $p['grid_nx']) {
      $xerr=($i-$p['grid_nx'])*$p['grid_dx'];
      $i=$p['grid_nx']-1;
    } else {
      $iround=round($i);
      $xerr=($i-$iround)*$p['grid_dx'];
      $i=$iround;
    }
    
    if ($j<0) {
      $yerr=$j*$p['grid_dy'];
      $j=0;
    } else if ($j >= $p['grid_ny']) {
      $yerr=($j-$p['grid_ny'])*$p['grid_dy'];
      $j=$p['grid_ny']-1;
    } else {
      $jround=round($j);
      $yerr=($j-$jround)*$p['grid_dy'];
      $j=$jround;
    }
    
    $xerr=round($xerr);
    $yerr=round($yerr);
    
    return array('x'=>$i, 'y'=>$j, 'x_error'=>$xerr, 'y_error'=>$yerr);
  }
  
  private function _init_proj () {
    if ($this->_proj) return;
    
    $this->_openFile(0);
    $atts = $this->getAttributes();
    
    if (!array_key_exists('Lambert_Conformal', $atts)) {
      throw new Exception("Only Lambert_Conformal_Conic projection is implemented");
    }
    
    $p=$atts['Lambert_Conformal'];
    
    // since the attributes array issue is not fixed, we have to cheat :
    // (this is OK for eu12 domain)
    $p['standard_parallel_1'] = $p['standard_parallel_2'] = $p['standard_parallel'];
    
    if (abs($p['standard_parallel_2']) > 90) {
      $p['standard_parallel_2']=$p['standard_parallel_1'];
    }
    
    $p['rad_per_deg'] = M_PI/180.;
    
    //! Earth radius divided by dx
    $p['rebydx'] = $p['earth_radius'] / $p['grid_dx'];
    
    //! 1 for NH, -1 for SH
    $p['hemi']=1.0;
    if ( $p['standard_parallel_1'] < 0.0 ) $p['hemi'] = -1.0;
    
    //! Cone factor for LC projections
    if (abs($p['standard_parallel_1']-$p['standard_parallel_2']) > 0.1) {
      $p['cone']=(log(cos($p['standard_parallel_1']*$p['rad_per_deg']))-            
      log(cos($p['standard_parallel_2']*$p['rad_per_deg']))) /          
      (log(tan((90.-abs($p['standard_parallel_1']))*$p['rad_per_deg']*0.5 ))- 
      log(tan((90.-abs($p['standard_parallel_2']))*$p['rad_per_deg']*0.5 )) );
    } else {
      $p['cone'] = sin(abs($p['standard_parallel_1'])*$p['rad_per_deg'] );
    }
    
    //! X/Y location of known lon/lat
    $knowni   =   0; 
    $knownj   =   0; 
    
    //! Compute longitude differences and ensure we stay out of the forbidden "cut zone"
    
    $deltalon1 = $p['sw_corner_lon'] - $p['longitude_of_central_meridian'];
    if ($deltalon1 > 180.) {
      $deltalon1 = $deltalon1 - 360.;
    } else if ($deltalon1 < -180.) {
      $deltalon1 = $deltalon1 + 360.;
    }
    
    //! Convert truelat1 to radian and compute COS for later use
    $tl1r = $p['standard_parallel_1'] * $p['rad_per_deg'];
    $p['ctl1r'] = cos($tl1r);
    
    //! Compute the radius to our known lower-left (SW) corner
    $rsw = $p['rebydx'] * $p['ctl1r'] / $p['cone'] * pow(
	      (tan((90.*$p['hemi']-$p['sw_corner_lat'])*$p['rad_per_deg']/2.) / 
		tan((90.*$p['hemi']-$p['standard_parallel_1'])*$p['rad_per_deg']/2.)), $p['cone']);
    
    //! Find pole point
    $arg = $p['cone']*($deltalon1*$p['rad_per_deg']);
    $p['polei'] = $p['hemi']*$knowni - $p['hemi'] * $rsw * sin($arg);
    $p['polej'] = $p['hemi']*$knownj + $rsw * cos($arg);
    
    
    $this->_proj=$p;
  }
  
}