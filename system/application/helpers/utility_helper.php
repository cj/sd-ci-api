<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

  if ( ! function_exists('one_dimensionalize'))
  {
    function one_dimensionalize( $ar, $field ) {
    	$_ret = array();
    	for ($i=0;$i<sizeof($ar);$i++) $_ret[]=$ar[$i][$field];
    	return $_ret;
    }
  }

  if ( ! function_exists('contains'))
  {
    function contains($str, $content){
        $ar_check = !is_array($str)?explode(',',$str):$str;
        foreach($ar_check AS $check) if(strtolower($check) == strtolower($content)) return true;
        return false;
    }
  }

  if ( ! function_exists('count_digit'))
  {
    function count_digit($number)
    {
        $digit = 0;
        do
        {
            $number /= 10;      //$number = $number / 10;
            $number = intval($number);
            $digit++;   
        }while($number!=0);
        return $digit;
    }
  }

  if ( ! function_exists('is_valid_url'))
  {
    function is_valid_url($url)
    {
        $url = @parse_url($url);
    
        if (!$url)
        {
            return false;
        }
    
        $url = array_map('trim', $url);
        $url['port'] = (!isset($url['port'])) ? 80 : (int)$url['port'];
        $path = (isset($url['path'])) ? $url['path'] : '';
    
        if ($path == '')
        {
            $path = '/';
        }
    
        $path .= (isset($url['query'])) ? "?$url[query]" : '';
    
        if (isset($url['host']) AND $url['host'] != gethostbyname($url['host']))
        {
            if (PHP_VERSION >= 5)
            {
                $headers = get_headers("$url[scheme]://$url[host]:$url[port]$path");
            }
            else
            {
                $fp = fsockopen($url['host'], $url['port'], $errno, $errstr, 30);
    
                if (!$fp)
                {
                    return false;
                }
                fputs($fp, "HEAD $path HTTP/1.1\r\nHost: $url[host]\r\n\r\n");
                $headers = fread($fp, 4096);
                fclose($fp);
            }
            $headers = (is_array($headers)) ? implode("\n", $headers) : $headers;
            return (bool)preg_match('#^HTTP/.*\s+[(200|301|302)]+\s#i', $headers);
        }
        return false;
    }
  }
  
  if ( ! function_exists('uuid'))
  {
    function uuid() 
    {
      
        $pr_bits = null;
        $fp = @fopen('/dev/urandom','rb');
        if ($fp !== false) {
            $pr_bits .= @fread($fp, 16);
            @fclose($fp);
        } else {
            // If /dev/urandom isn't available (eg: in non-unix systems), use mt_rand().
            $pr_bits = "";
            for($cnt=0; $cnt < 16; $cnt++){
                $pr_bits .= chr(mt_rand(0, 255));
            }
        }
      
        $time_low = bin2hex(substr($pr_bits,0, 4));
        $time_mid = bin2hex(substr($pr_bits,4, 2));
        $time_hi_and_version = bin2hex(substr($pr_bits,6, 2));
        $clock_seq_hi_and_reserved = bin2hex(substr($pr_bits,8, 2));
        $node = bin2hex(substr($pr_bits,10, 6));
      
        /**
         * Set the four most significant bits (bits 12 through 15) of the
         * time_hi_and_version field to the 4-bit version number from
         * Section 4.1.3.
         * @see http://tools.ietf.org/html/rfc4122#section-4.1.3
         */
        $time_hi_and_version = hexdec($time_hi_and_version);
        $time_hi_and_version = $time_hi_and_version >> 4;
        $time_hi_and_version = $time_hi_and_version | 0x4000;
      
        /**
         * Set the two most significant bits (bits 6 and 7) of the
         * clock_seq_hi_and_reserved to zero and one, respectively.
         */
        $clock_seq_hi_and_reserved = hexdec($clock_seq_hi_and_reserved);
        $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved >> 2;
        $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved | 0x8000;
      
        return sprintf('%08s-%04s-%04x-%04x-%012s',
            $time_low, $time_mid, $time_hi_and_version, $clock_seq_hi_and_reserved, $node);
    }
  }

  if ( ! function_exists('smartCopy'))
  {
    function smartCopy($source, $dest, $options=array('folderPermission'=>0777,'filePermission'=>0777)) 
      { 
          $result=false; 
  
          if (is_file($source)) { 
              if ($dest[strlen($dest)-1]=='/') { 
                  if (!file_exists($dest)) { 
                      cmfcDirectory::makeAll($dest,$options['folderPermission'],true); 
                  } 
                  $__dest=$dest."/".basename($source); 
              } else { 
                  $__dest=$dest; 
              } 
              $result=copy($source, $__dest); 
              chmod($__dest,$options['filePermission']); 
  
          } elseif(is_dir($source)) { 
              if ($dest[strlen($dest)-1]=='/') { 
                  if ($source[strlen($source)-1]=='/') { 
                      //Copy only contents 
                  } else { 
                      //Change parent itself and its contents 
                      $dest=$dest.basename($source); 
                      @mkdir($dest); 
                      chmod($dest,$options['filePermission']); 
                  } 
              } else { 
                  if ($source[strlen($source)-1]=='/') { 
                      //Copy parent directory with new name and all its content 
                      @mkdir($dest,$options['folderPermission']); 
                      chmod($dest,$options['filePermission']); 
                  } else { 
                      //Copy parent directory with new name and all its content 
                      @mkdir($dest,$options['folderPermission']); 
                      chmod($dest,$options['filePermission']); 
                  } 
              } 
  
              $dirHandle=opendir($source); 
              while($file=readdir($dirHandle)) 
              { 
                  if($file!="." && $file!="..") 
                  { 
                       if(!is_dir($source."/".$file)) { 
                          $__dest=$dest."/".$file; 
                      } else { 
                          $__dest=$dest."/".$file; 
                      } 
                      //echo "$source/$file ||| $__dest<br />"; 
                      $result=$this->smartCopy($source."/".$file, $__dest, $options); 
                  } 
              } 
              closedir($dirHandle); 
  
          } else { 
              $result=false; 
          } 
          return $result; 
      }
  }

  if ( ! function_exists('format_date'))
  {
    function format_date($format,$date)
    {
      switch($format){
        case 'TIME_24':
          $sFormat = 'G:i';
          break;
        case 'TIME_SHORT':
          $sFormat = 'g:ia T';
          break;
        case 'DATE_SHORT':
          $sFormat = 'm/d/Y';
          break;
        case 'DATE_LONG':
          $sFormat = '%B %e %Y';
          break;
        case 'DATE_TIME_SHORT':
          $sFormat = 'm/d/Y g:ia T';
          break;
        case 'DATE_TIME_FEED':
          $sFormat = 'm/d/Y g:ia T';
          break;
        case 'DATE_FEED':
          $sFormat = 'F J';
          break;
        default:
          $sFormat = '';	      
      }
    	return date($sFormat, strtotime($date));    
    }
  }

  if ( ! function_exists('phone_number'))
  {
    //Converts a phone number from 8888888888 to (888) 888 - 8888.
    function phone_number($sPhone){
        $sPhone = preg_replace("[^0-9]",'',$sPhone);
        if(strlen($sPhone) != 10) return(False);
        $sArea = substr($sPhone,0,3);
        $sPrefix = substr($sPhone,3,3);
        $sNumber = substr($sPhone,6,4);
        $sPhone = "(".$sArea.") ".$sPrefix."-".$sNumber;
        return($sPhone);
    }
  }

  if ( ! function_exists('strip_phone_number'))
  {
    function strip_phone_number($phoneNumer){
      return preg_replace("/\D/","",$phoneNumer);
    }
  }

  if ( ! function_exists('resize_image'))
  {
  	function resize_image($src_path, $dest_path, $width = 150, $quality = 80){
  
           // image src size
      list($width_orig, $height_orig, $image_type)  = getImageSize($src_path); 
  
      if($width_orig<$width) return; //if width is smaller than current width don't resize
  
      $height = (int) (($width / $width_orig) * $height_orig);
  
    //  print IMAGEMAGICK_CONVERT_PATH." ".$src_path." -quality ".$quality." -resize ".$width."x".$height." ".fixPath($dest_path); exit;
      exec(IMAGEMAGICK_CONVERT_PATH." ".$src_path." -quality ".$quality." -resize ".$width."x".$height." ".$dest_path);
  
    }
  }

  if ( ! function_exists('clean_file_name'))
  {
  	function clean_file_name($file_name){
  
      $replace="_";
      $pattern="/([[:alnum:]_\.-]*)/";
      
      return str_replace(str_split(preg_replace($pattern,$replace,$file_name)),$replace,$file_name);
  
    }
  }

  if ( ! function_exists('set_memcache'))
  {
    function set_memcache($name,$data,$length,$status = 'open'){
    	$CI =& get_instance();
    	$name = PRODUCTION_STATUS.'_'.$name; //add production status so test sites don't conflict with live memcache
    	switch($status){

    		case 'open':
    			$CI->objMemCache->set($name,$data, false, $length);
    			break;

    		case 'private':
    			if($CI->objLogin->get('id')) $CI->objMemCache->set($CI->objLogin->get('id')."_".$name, $data, false, $length);
    			else $CI->objMemCache->set(str_replace(".","_",$CI->input->ip_address())."_".$name, $data, false, $length);
    			break;

    	}
    }
  }

  if ( ! function_exists('get_memcache'))
  {
    function get_memcache($name,$status = 'open'){
    	$CI =& get_instance();
    	$name = PRODUCTION_STATUS.'_'.$name; //add production status so test sites don't conflict with live memcache		
    	switch($status){

    		case 'open':
    			return $CI->objMemCache->get($name);
    			break;

    		case 'private':
    			if($CI->objLogin->get('id')) return $CI->objMemCache->get($CI->objLogin->get('id')."_".$name);
    			else return $CI->objMemCache->get(str_replace(".","_",$CI->input->ip_address())."_".$name);
    			break;

    	}
    }
  }
  
  if ( ! function_exists('file_get_conditional_contents'))
  {
    function file_get_conditional_contents($szURL)
    {
        $pCurl = curl_init($szURL);
    
        curl_setopt($pCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($pCurl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($pCurl, CURLOPT_TIMEOUT, 10);
    
        $szContents = curl_exec($pCurl);
        $aInfo = curl_getinfo($pCurl);
    
        if($aInfo['http_code'] === 200)
        {
            return $szContents;
        }
    
        return false;
    }
  }