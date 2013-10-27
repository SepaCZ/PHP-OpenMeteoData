<?php

/*
opendap.class.php - A PHP class for retrieving OpenMeteoData forecast data.
2013 Nicolas BALDECK <nicolas.baldeck@openmeteodata.org>
This file is licenced under the DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE.
For Help and Documentation, see http://wiki.openmeteodata.org/wiki/opendap.class.php
For contributing or submit bugs, see http://github.com/OpenMeteoData/PHP-OpenMeteoData


I wrote it quickly for demonstrating the use of OpenMeteoData files.
It is not perfect, but it seems to be working.
OPeNDAP protocol is not fully implemented here. Feel free to contribute.
*/

class OPeNDAP {
  
  private $_url;
  private $_ck;
  private $_attributes=FALSE;
  private $_dimensions=FALSE;
  private $_types=FALSE;
  
  public function __construct ($url, $ck=false) {
    if (empty($url)) throw new Exception("URL is empty");
    $this->_url=$url;
    $this->_ck=$ck;
  }
  
  private function _ckUrl() {
    if ($this->_ck) {
      return '&_ck='.$this->_ck;
    } else {
      return '';
    }
  }
  
  public function getAttributes() {
    $this->_getDAS();
    return $this->_attributes;
  }
  
  
  public function getVariables() {
    $this->_getDAS();
    
    $variables = array();
    foreach ($this->_attributes as $var=>$atts) {
      $variables[]=$var;
    }
    return $variables;
  }
  
  private function _getDAS() {
    if ($this->_attributes) return;
    
    $url = $this->_url.'.das?'.$this->_ckUrl();
    
    $handle = fopen($url, 'r');
    if (!$handle) throw new Exception('Cannot open file :'.$url);

    $into_var=FALSE;
    $var='';

    $this->_attributes = array();

    
    $buffer = trim(fgets($handle, 1024));
    if ($buffer != 'Attributes {') throw new Exception('Bad DAS format');
    
    while (($buffer = fgets($handle, 1024)) !== false) {
      $buffer = trim($buffer);
      
      if ($buffer == '}') {
	$into_var=FALSE;
      } else if ($into_var) {
	preg_match('/^(Float32|String|Int32)\s([\w\d-]+)\s(.*);$/', $buffer, $matches);
	// TODO : handle attribute arrays. ie : Float32 standard_parallel 47.5, 47.5;
	if ($matches) {
	  switch ($matches[1]) {
	    case 'Float32':
	      $val = floatval($matches[3]);
	      break;
	    case 'Int32':
	      $val = intval($matches[3]);
	      break;
	    case 'String':
	      $val = stripslashes(substr($matches[3], 1, -1));
	      break;
	  }
	  
	  $this->_attributes[$var][$matches[2]] = $val;
	  
	} else {
	  trigger_error("unsupported DAS line :\n$buffer", E_USER_WARNING);
	}
      } else if (!$into_var && substr($buffer, -2) == ' {') {
	$var = substr($buffer, 0, -2);
	$into_var=TRUE;
	$this->_attributes[$var] = array();
      }
	
    }
    fclose($handle);

    ksort($this->_attributes);
    
    return $this->_attributes;
   
  }
  
  
  private function _getDDS() {
    if ($this->_dimensions && $this->_types) return;

    $url = $this->_url.'.dds?'.$this->_ckUrl();
    
    $handle = fopen($url, 'r');

    $into_grid=FALSE;
    $into_array=FALSE;
    $into_maps=FALSE;    
    
    
    $this->_dimensions = array();

    if (!$handle) throw new Exception('Cannot open file :'.$url);
    
    $buffer = trim(fgets($handle, 1024));
    if ($buffer != 'Dataset {') throw new Exception('Bad DDS format');
    
    while (($buffer = fgets($handle, 1024)) !== false) {
      $buffer = trim($buffer);
      
      if (($into_array || !$into_grid) && preg_match('^(?P<type>Float32|Int32)\s(?P<name>\w+)(?P<dims>\[.*\])?;^', $buffer, $matches)) {
	
	$tmp_dims=array();
	if (array_key_exists('dims', $matches)) {
	  preg_match_all('/\[(?P<name>\w+)\s=\s(?P<count>\d+)\]/', $matches['dims'], $matches2, PREG_SET_ORDER);
	  foreach ($matches2 as $dim) {
	    $tmp_dims[$dim['name']]=$dim['count'];
	  }
	}
	$this->_dimensions[$matches['name']]=$tmp_dims;
	$this->_types[$matches['name']]=$matches['type'];
	
      } else if ($into_grid) {
	if ($buffer == 'Array:') {
	  $into_array=TRUE;
	  $into_maps=FALSE;
	} else if ($buffer == 'Maps:') {
	  $into_array=FALSE;
	  $into_maps=TRUE;
	} else if (substr($buffer, 0, 1) == '}') {
	  $into_grid=FALSE;
	  $into_maps=FALSE;
	  $into_array=FALSE;
	}
      } else if ($buffer == 'Grid {') {
	$into_grid=TRUE;
      }
      
    }
    fclose($handle);
    
    ksort($this->_dimensions);
    ksort($this->_types);
    
  }
  
  public function getDimensions() {
    $this->_getDDS();
    return $this->_dimensions;
  }
  
  public function getTypes() {
    $this->_getDDS();
    return $this->_types;
  }
  
  public function getArray($var, $xsub=FALSE, $ysub=FALSE, $zsub=FALSE) {
    $subUrl="";
    
    $subs=array();
    
    if ($zsub) $subs[]=$zsub;
    if ($ysub) $subs[]=$ysub;
    if ($xsub) $subs[]=$xsub; // order matters
    
    foreach ($subs as $sub) {
      $size=sizeof($sub);
      if ($size==2) {
	$subUrl.="[$sub[0]:1:$sub[1]]";
      } else if ($size==3) {
	$subUrl.="[$sub[0]:$sub[1]:$sub[2]]";
      } else {
	throw new Exception('Bad subset array');
      }
    }
    
    $url = $this->_url.'.dods?'.$var.$subUrl.$this->_ckUrl();
    
    $handle = fopen($url, 'r');
    if (!$handle) throw new Exception('Cannot open file :'.$url);
    
    $buffer = trim(fgets($handle, 1024));
    if ($buffer != 'Dataset {') throw new Exception('Bad DODS format');
    
    $readline=TRUE;
    $type=FALSE;
    $dims=FALSE;
    
    while ($readline && ($buffer = fgets($handle, 1024)) !== false) {
      $buffer = trim($buffer);
      if ($buffer=='Data:') {
	$readline=FALSE;
      } else if (preg_match('^(?P<type>\w+)\s'.$var.'(?P<dims>\[.*\])?;^', $buffer, $matches)) {
	$type=$matches['type'];
	if (array_key_exists('dims', $matches)) {
	  preg_match_all('/\[(?P<name>\w+)\s=\s(?P<count>\d+)\]/', $matches['dims'], $matches2);
	  $dims=$matches2['count'];
	}
      }
    }
    
    // forward 8 bytes
    fread($handle, 8);
        
    $unpackfunc=FALSE;
    $size=0;
    
    switch ($type) {
      case "Int32":
	$unpackfunc='_unpack_int32';
	$size=4;
	break;
      case "Float32":
	$unpackfunc='_unpack_float32';
	$size=4;
	break;
      default:
	 throw new Exception('Type '.$type.' is not implemented');
    } 
    
    $ndims=count($dims);
    $data = array();
    
    // I choose to provide data as [x][y][z] arrays.
    // I know it's not efficient, but I want easy to use data for n00b users.
    // Power users will be smart enough for tuning this code in a more efficient way.
    
    switch ($ndims) {
      case 1:
	for ($x=0; $x<$dims[0]; $x++) {
	  $data[$x]=$this->$unpackfunc(fread($handle, $size));
	}
	break;
      case 2:
	for ($x=0; $x<$dims[1]; $x++) {
	  $data[$x]=array();
	}
	for ($y=0; $y<$dims[0]; $y++) {
	  for ($x=0; $x<$dims[1]; $x++) {
	    $data[$x][$y]=$this->$unpackfunc(fread($handle, $size));
	  }
	}
	break;
      case 3:
	for ($x=0; $x<$dims[2]; $x++) {
	  $data[$x]=array();
	  for ($y=0; $y<$dims[1]; $y++) {
	    $data[$x][$y]=array();
	  }
	}
	for ($z=0; $z<$dims[0]; $z++) {
	  for ($y=0; $y<$dims[1]; $y++) {
	    for ($x=0; $x<$dims[2]; $x++) {
	      $data[$x][$y][$z]=$this->$unpackfunc(fread($handle, $size));
	    }
	  }
	}
	break;
      default:
	throw new Exception('Only 1, 2 or 3 dimensional arrays are implemented');
    }
    fclose($handle);
    return $data;
  }
  
  private function _unpack_int32 ($buffer) {
    $val=unpack('i', strrev(substr($buffer, 0, 4)));
    // TODO: need to make sure it works on every platforms
    return $val[1];
  }
  
  private function _unpack_float32 ($buffer) {
    $val=unpack('f', strrev(substr($buffer, 0, 4)));
    // TODO: need to make sure it works on every platforms
    return $val[1];
  }
  
}