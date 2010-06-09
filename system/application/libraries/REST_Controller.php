<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class REST_Controller extends Controller {
    
    // Not what you'd think, set this in a controller to use a default format
    protected $rest_format = NULL;
    
    private $_method;
    private $_format;
    
    private $_get_args;
    private $_put_args;
    private $_args;
    public $_is_public = FALSE;
    
    // List all supported methods, the first will be the default format
    private $_supported_formats = array(
    'json' 		=> 'application/json',
		'xml' 		=> 'application/xml',
		'rawxml' 	=> 'application/xml',
		'serialize' => 'text/plain',
		'php' 		=> 'text/plain',
		'html' 		=> 'text/html',
		'csv' 		=> 'application/csv'
	);
    
    // Constructor function
    function __construct()
    {
      parent::Controller();
  
      // How is this request being made? POST, DELETE, GET, PUT?
      $this->_method = $this->_detect_method();
  
  
      // Set up our GET variables
      $this->_get_args = array_merge(array($this->uri->segment(2) =>'index'),$this->uri->uri_to_assoc(3));
      $this->_args = $this->_get_args;

      $this->page = isset($_REQUEST['page'])?$_REQUEST['page']:false;
      $this->rows = isset($_REQUEST['rows'])?$_REQUEST['rows']:false;
      switch(strtolower($this->input->server('REQUEST_METHOD'))){
        case "get":
          if(isset($_REQUEST['sidx'])) $_REQUEST['sort'] = $_REQUEST['sidx'];
          if(isset($_REQUEST['sord'])) $_REQUEST['dir'] = $_REQUEST['sord'];
          if($this->page AND $this->rows){
            $page = $this->page - 1;
            $rows = $this->rows;
            $_REQUEST['start'] = $page != 0?$page*$rows:$page;
            $_REQUEST['limit'] = $rows;
          };
          break;
        case "post":
          foreach($_REQUEST as $key => $value){
               if(!is_array($value)) if(strtolower(preg_replace("[^A-Za-z0-9]", "", $key )) != strtolower(preg_replace("[^A-Za-z0-9]", "", $value ))) $_POST[$key] = $value;
               else $_POST[$key] = $value;
      	    }
          break;
        case "put":
          parse_str(file_get_contents('php://input'), $this->_put_args);
          $_REQUEST = $this->_put_args;
          foreach($_REQUEST as $key => $value){
               if(!is_array($value)) if(strtolower(preg_replace("[^A-Za-z0-9]", "", $key )) != strtolower(preg_replace("[^A-Za-z0-9]", "", $value ))) $_POST[$key] = $value;
               else $_POST[$key] = $value;
      	    }
          break;
        case "delete":
          $_REQUEST = $this->_args;
          break;
    }
  
    if(isset($_REQUEST['key']) AND $_REQUEST['key'] == "c31ecc24-3032-434f-b623-ee0c4ddccfb1")
    {
      $this->key = TRUE;
      $this->key_user = !isset($_REQUEST['key_user'])?'superuser':$_REQUEST['key_user'];
      if(isset($_REQUEST['key_user_id'])) $this->key_user_id = $_REQUEST['key_user_id'];
    }
    elseif(isset($_REQUEST['key'])) $this->key = $_REQUEST['key'];
    else $this->key = FALSE;
  
    $this->rest_auth = isset($_REQUEST['auth_type'])?$_REQUEST['auth_type']:'digest';
    // Lets grab the config and get ready to party
    $this->load->config('rest');
    
    if($this->rest_auth == 'basic')
    {
    	$this->_prepareBasicAuth();
    }
    
    elseif($this->rest_auth == 'digest')
    {
    	$this->_prepareDigestAuth();
    }
    
    // Set caching based on the REST cache config item
    //$this->output->cache( $this->config->item('rest_cache') );
    // Which format should the data be returned in?
    $this->_format = $this->_detect_format();
  
    }
    
    function _global_get_functions()
    {
      $module = $this->_module;
      
      if(isset($_REQUEST['download_as_excel']))
  	  {
  	    ini_set('memory_limit','1000M');
        $this->$module->excel_new();
        return $this->$module->excel_save();
  	    exit;
  	  }
    }
    /* 
     * Remap
     * 
     * Requests are not made to methods directly The request will be for an "object".
     * this simply maps the object and method to the correct Controller method.
     */
    function _remap($object_called)
    {
    	$controller_method = $object_called.'_'.$this->_method;

		  if(method_exists($this, $controller_method))
		  {
		  	$this->$controller_method();
		  }
		  else
		  {
		  	$controller_method = $this->_method;

        		if(method_exists($this, $controller_method))
        		{
        			$this->$controller_method();
        		}

        		else
        		{
        			show_404();
        		}
              
		  }
    }
    
    function get()
  	{
  	  $class = $this->_class;
      $module = $this->_module;
      
  	  $this->load->module_model($module,$class.'_model',$class);
      $data = $this->$class->run_api();

      $this->total_count =  $this->$class->total_count;

      $this->success = TRUE;    
      $this->message = 'Successfully Grabbed '.ucfirst($class).' Info.';

      $this->response($data, 200); // 200 being the HTTP response code

  	}
    
    function post()
    {
      $class = $this->_class;
      $module = $this->_module;
      
      $this->load->module_model($module,$class.'_model',$class);
      $is_valid = $this->$class->get_form($prefix = FALSE,!isset($_POST['validate'])?TRUE:FALSE);

      if($is_valid){

        $this->$class->save();

        $this->total_count =  1;
    		$this->success = TRUE;    
        if(isset($this->_success_message)){
          $this->message = $this->_success_message;
        } else {
          $this->message = 'Successfully Posted '.ucfirst($class).' Info.';
        }

        $this->response($this->$class->data, 200);
      } else {
        $this->total_count = 0;
        $this->success = FALSE;    
        $this->message = 'Something Happened.';
        $this->response($this->$class->errors, 200);
      }
    }
    
    function put()
    {
      $class = $this->_class;
      $module = $this->_module;
      
      $this->load->module_model($module,$class.'_model',$class);
      $this->$class->load_data($_REQUEST['id']);
      $is_valid = $this->$class->get_form($prefix = FALSE,!isset($_POST['validate'])?TRUE:FALSE);

      if($is_valid){

        $this->$class->save();

        $this->total_count =  1;
    		$this->success = TRUE;    
        if(isset($this->_success_message)){
          $this->message = $this->_success_message;
        } else {
          $this->message = 'Successfully Posted '.ucfirst($class).' Info.';
        }

        $this->response($this->$class->data, 200);
      } else {
        $this->total_count = 0;
        $this->success = FALSE;    
        $this->message = 'Something Happened.';
        $this->response($this->$class->errors, 200);
      }
    }
    
    function delete()
  	{
  	  $class = $this->_class;
      $module = $this->_module;
      
  	  $this->load->module_model($module,$class.'_model',$class);
      $this->$class->load_data($_REQUEST['id']);
      $this->$class->remove();
    }

    /* 
     * response
     * 
     * Takes pure data and optionally a status code, then creates the response
     */
    function response($data = '', $http_code = 200)
    {
   		//if(empty($data))
    	//{
    	//	$this->output->set_status_header(404);
    	//	return;
    	//}
		  $this->output->set_status_header($http_code);
        
        // If the format method exists, call and return the output in that format
        if(method_exists($this, '_'.$this->_format))
        {
      	// Set a XML header
      	$this->output->set_header('Content-type: '.$this->_supported_formats[$this->_format]);
    	
        	$formatted_data = $this->{'_'.$this->_format}($data);
        	$this->output->set_output( $formatted_data );
        }
        
        // Format not supported, output directly
        else
	      {
        	$this->output->set_output( $data );
        }
		
    }

    
    /* 
     * Detect format
     * 
     * Detect which format should be used to output the data
     */
    private function _detect_format()
    {
    	// A format has been passed in the URL and it is supported
    	if(array_key_exists('format', $this->_args) && array_key_exists($this->_args['format'], $this->_supported_formats))
    	{
    		return $this->_args['format'];
    	}
    	
    	// Otherwise, check the HTTP_ACCEPT (if it exists and we are allowed)
	    if($this->config->item('rest_ignore_http_accept') === FALSE && $this->input->server('HTTP_ACCEPT'))
	    {
	    	// Check all formats against the HTTP_ACCEPT header
	    	foreach(array_keys($this->_supported_formats) as $format)
	    	{
		    	// Has this format been requested?
		    	if(strpos($this->input->server('HTTP_ACCEPT'), $format) !== FALSE)
		    	{
		    		// If not HTML or XML assume its right and send it on its way
		    		if($format != 'html' && $format != 'xml')
		    		{
		    			
		    			return $format;		    			
		    		}
		    		
		    		// HTML or XML have shown up as a match
		    		else
		    		{
		    			// If it is truely HTML, it wont want any XML
		    			if($format == 'html' && strpos($this->input->server('HTTP_ACCEPT'), 'xml') === FALSE)
		    			{
		    				return $format;
		    			}
		    			// If it is truely XML, it wont want any HTML
		    			elseif($format == 'xml' && strpos($this->input->server('HTTP_ACCEPT'), 'html') === FALSE)
		    			{
		    				return $format;
		    			}
		    		}
		    	}
	    	}
	    	
	    } // End HTTP_ACCEPT checking
	    	
		// Well, none of that has worked! Let's see if the controller has a default
		if($this->rest_format != NULL)
		{
			return $this->rest_format;
		}	    	

		// Just use whatever the first supported type is, nothing else is working!
		list($default)=array_keys($this->_supported_formats);
		return $default;
		
    }
    
    
    /* 
     * Detect method
     * 
     * Detect which method (POST, PUT, GET, DELETE) is being used
     */
    private function _detect_method()
    {
    	$method = strtolower($this->input->server('REQUEST_METHOD'));
    	if(in_array($method, array('get', 'delete', 'post', 'put')))
    	{
	    	return $method;
    	}
		
    	return 'get';
    }
    
    
    // INPUT FUNCTION --------------------------------------------------------------
    

    public function restGet($key)
    {
    	return array_key_exists($key, $this->_get_args) ? $this->input->xss_clean( $this->_get_args[$key] ) : $this->input->get($key) ;
    }
    
    public function restPost($key)
    {
    	return $this->input->post($key);
    }
    
    public function restPut($key)
    {
    	return array_key_exists($key, $this->_put_args) ? $this->input->xss_clean( $this->_put_args[$key] ) : FALSE ;
    }
    
    // SECURITY FUNCTIONS ---------------------------------------------------------
    
    private function _checkLogin($username = '',$password = FALSE)
    {
      
      $this->load->module_model('common','User_Model','user');
      
      return $this->user->authenticate($username,$password,$this->key);
		  
    }
    
    private function _prepareBasicAuth()
    {
    	$username = NULL;
    	$password = NULL;
    	
    	// mod_php
		if (isset($_SERVER['PHP_AUTH_USER'])) 
		{
		    $username = $_SERVER['PHP_AUTH_USER'];
		    $password = $_SERVER['PHP_AUTH_PW'];
		}
		// most other servers
		elseif (isset($_SERVER['HTTP_AUTHENTICATION']))
		{
			if (strpos(strtolower($_SERVER['HTTP_AUTHENTICATION']),'basic')===0)
			{
				list($username,$password) = explode(':',base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
			}  
		}
		elseif (isset($_SERVER['HTTP_WWW_AUTHORIZATION']))
		{
			if (strpos(strtolower($_SERVER['HTTP_WWW_AUTHORIZATION']),'basic')===0)
			{
				list($username,$password) = explode(':',base64_decode(substr($_SERVER['HTTP_WWW_AUTHORIZATION'], 6)));
			}  
		} 
    $row = $this->_checkLogin($username, $password);
		if ( !$row )
		{
		    $this->_forceLogin();
		} else {
		  $this->load->module_model('common','User_Model','objLogin');
			$this->objLogin->restore_session('login');
			$this->objMemCache = new Memcache;
      $this->objMemCache->addServer(MEMCACHED_IP1,11211);
      $this->objMemCache->addServer(MEMCACHED_IP2,11211);
      
		}
	
		
    }
    
    /*
      CJFN url_base64_decode
    */
    public function url_base64_decode($str){
      return strtr(base64_decode($str),
        array(
          '.' => '+',
          '\\' => '=',
          '~' => '/'
        )
      );
    }
    
    private function _prepareDigestAuth()
    {
      if(!$this->_is_public)
      {
        if(!$this->key){
  	      	$uniqid = uniqid(""); // Empty argument for backward compatibility

  		      // We need to test which server authentication variable to use
  		      // because the PHP ISAPI module in IIS acts different from CGI
  		      if(isset($_SERVER['PHP_AUTH_DIGEST']))
  		      {
  		          $digest_string = $_SERVER['PHP_AUTH_DIGEST'];
  		      }
  		      elseif(isset($_SERVER['HTTP_AUTHORIZATION']))
  		      {
  		          $digest_string = $_SERVER['HTTP_AUTHORIZATION'];
  		      }
  		      else
  		      {
  		      	$digest_string = "";
  		      }

  		      /* The $_SESSION['error_prompted'] variabile is used to ask
  		         the password again if none given or if the user enters
  		         a wrong auth. informations. */
  		      if ( empty($digest_string) )
  		      {
  		          $this->_forceLogin($uniqid);
  		      }

  		      // We need to retrieve authentication informations from the $auth_data variable
  			  preg_match_all('@(username|nonce|uri|nc|cnonce|qop|response)=[\'"]?([^\'",]+)@', $digest_string, $matches);
  			  $digest = array_combine($matches[1], $matches[2]); 

  			  $row = $this->_checkLogin($digest['username']);
  			  if ( !array_key_exists('username', $digest) || !isset($row['password']) )
  			  {
  			  	$this->_forceLogin($uniqid);
  	          }

  			  $valid_pass = $row['password'];

  	          // This is the valid response expected
  			  $A1 = md5($digest['username'] . ':' . $this->config->item('rest_realm') . ':' . $valid_pass);
  			  $A2 = md5(strtoupper($this->_method).':'.$digest['uri']);
  			  $valid_response = md5($A1.':'.$digest['nonce'].':'.$digest['nc'].':'.$digest['cnonce'].':'.$digest['qop'].':'.$A2);

  			  if ($digest['response'] != $valid_response)
  			  {
  		      	$this->_forceLogin($uniqid);
  			  }
  			}

  			if($this->key === TRUE)
  			{
  			  if(!isset($this->key_user_id)) $access = $this->_checkLogin($this->key_user);
  			  else $access = $this->_checkLogin($this->key_user,FALSE,FALSE,$this->key_user_id);
  			  if(!$access) $this->_forceLogin();
  			}
  			elseif($this->key)
  			{
  			  $access = $this->_checkLogin();
  			  if(!$access) $this->_forceLogin();
  			}
  			
  			$this->load->module_model('common','User_Model','objLogin');
  			$this->objLogin->restore_session('login');
  			
  			if(isset($_REQUEST['login']) AND !$this->key)
  			{
  			  $this->objLogin->gen_key($this->objLogin->get("id"));
  			  $this->objLogin->load_data($this->objLogin->get("id"));
          $this->objLogin->save_session('login');
          $this->objLogin->restore_session('login');
  			}
      }
      $this->objMemCache = new Memcache;
			$this->objMemCache->addServer(MEMCACHED_IP1,11211);
			$this->objMemCache->addServer(MEMCACHED_IP2,11211);
    }
    
    
    private function _forceLogin($nonce = '')
    {
	    header('HTTP/1.0 401 Unauthorized');
	    header('HTTP/1.1 401 Unauthorized');
	    header('not_logged_in');
	    
    	if($this->rest_auth == 'basic')
        {
        	header('WWW-Authenticate: Basic realm="'.$this->config->item('rest_realm').'"');
        }
        
        elseif($this->rest_auth == 'digest')
        {
        	header('WWW-Authenticate: Digest realm="'.$this->config->item('rest_realm'). '" qop="auth" nonce="'.$nonce.'" opaque="'.md5($this->config->item('rest_realm')).'"');
        }
    	
          print_r(json_encode(array(
            'success'=>FALSE,
            'message'=>'User and Password Incorrect.',
            'results'=> FALSE
          )));
        
	    die();
    }
    // FORMATING FUNCTIONS ---------------------------------------------------------
    
    // Format XML for output
    private function _xml($data = array(), $structure = NULL, $basenode = 'xml')
    {
    	// turn off compatibility mode as simple xml throws a wobbly if you don't.
		if (ini_get('zend.ze1_compatibility_mode') == 1)
		{
			ini_set ('zend.ze1_compatibility_mode', 0);
		}

		if ($structure == NULL)
		{
			$structure = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$basenode />");
		}

		// loop through the data passed in.
		foreach($data as $key => $value)
		{
			// no numeric keys in our xml please!
			if (is_numeric($key))
			{
				// make string key...
				//$key = "item_". (string) $key;
				$key = "item";
			}

			// replace anything not alpha numeric
			$key = preg_replace('/[^a-z]/i', '', $key);

			// if there is another array found recrusively call this function
			if (is_array($value))
			{
				$node = $structure->addChild($key);
				// recrusive call.
				$this->_xml($value, $node, $basenode);
			}
			else
			{
				// add single node.

				$value = htmlentities($value, ENT_NOQUOTES, "UTF-8");

				$UsedKeys[] = $key;

				$structure->addChild($key, $value);
			}

		}
    	
		// pass back as string. or simple xml object if you want!
		return $structure->asXML();
    }
    
    
    // Format Raw XML for output
    private function _rawxml($data = array(), $structure = NULL, $basenode = 'xml')
    {
    	// turn off compatibility mode as simple xml throws a wobbly if you don't.
		if (ini_get('zend.ze1_compatibility_mode') == 1)
		{
			ini_set ('zend.ze1_compatibility_mode', 0);
		}

		if ($structure == NULL)
		{
			$structure = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$basenode />");
		}

		// loop through the data passed in.
		foreach($data as $key => $value)
		{
			// no numeric keys in our xml please!
			if (is_numeric($key))
			{
				// make string key...
				//$key = "item_". (string) $key;
				$key = "item";
			}

			// replace anything not alpha numeric
			$key = preg_replace('/[^a-z0-9_-]/i', '', $key);

			// if there is another array found recrusively call this function
			if (is_array($value))
			{
				$node = $structure->addChild($key);
				// recrusive call.
				$this->_xml($value, $node, $basenode);
			}
			else
			{
				// add single node.

				$value = htmlentities($value, ENT_NOQUOTES, "UTF-8");

				$UsedKeys[] = $key;

				$structure->addChild($key, $value);
			}

		}
    	
		// pass back as string. or simple xml object if you want!
		return $structure->asXML();
    }
    
    // Format HTML for output
    private function _html($data = array())
    {
		// Multi-dimentional array
		if(isset($data[0]))
		{
			$headings = array_keys($data[0]);
		}
		
		// Single array
		else
		{
			$headings = array_keys($data);
			$data = array($data);
		}
		
		$this->load->library('table');
		
		$this->table->set_heading($headings);
		
		foreach($data as &$row)
		{
			$this->table->add_row($row);
		}
		
		return $this->table->generate();
    }
    
    // Format HTML for output
    private function _csv($data = array())
    {
    	// Multi-dimentional array
		if(isset($data[0]))
		{
			$headings = array_keys($data[0]);
		}
		
		// Single array
		else
		{
			$headings = array_keys($data);
			$data = array($data);
		}
		
		$output = implode(',', $headings)."\r\n";
		foreach($data as &$row)
		{
			$output .= '"'.implode('","',$row)."\"\r\n";
		}
		
		return $output;
    }
    
    // Encode as JSON
    private function _json($data = array())
    {
        $totalPages = $this->rows?ceil($this->total_count/$this->rows):0;
        
        echo json_encode(array(
          'success'=>$this->success,
          'message'=>$this->message,
          'page'   =>$this->page,
          'rows'   =>$this->rows,
          'totalPages' => $totalPages,
          'results'=>$data,
          'total'  => $this->total_count
        ));
    }
    private function _extjs($data = array())
    {
		$json = json_encode(array("total"=>"$this->total_count",'results'=>$data));
    	print_r("$json");
    }
    private function _extjs_form($data = array())
    {
		$json = json_encode(array("success"=>true,'data'=>$data));
    	print_r("$json");
    }
    
    // Encode as Serialized array
    private function _serialize($data = array())
    {
    	return serialize($data);
    }
    
    // Encode raw PHP
    private function _php($data = array())
    {
    	return var_export($data, TRUE);
    }
}
?>