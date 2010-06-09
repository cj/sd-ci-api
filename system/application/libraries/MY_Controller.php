<?php

class MY_Controller extends Controller 
{

  function __construct()
  {
    parent::__construct();
  }

  private function _checkLogin($username = '',$password = FALSE)
  {
    /**
     * EXAMPLE OF AUTH
     $this->load->module_model('common','User_Model','user');
     return $this->user->authenticate($username,$password,$this->key);
     */
    
    /**
     * EXAMPLE authenticate function in the user model
     function authenticate($username,$password,$key = FALSE,$key_user_id = FALSE){

       if($username || $key === TRUE)
       {
         $_POST['username'] = $username;

         if($this->validate($onlyfields = array('username'))){
           if(!$key_user_id) $this->db->where('username', $username);
           else $this->db->where('id', $key_user_id);
           $this->db->where('(disabled=0 or disabled is null)');
           if($password) $this->db->where('password', $password);
           $this->db->limit(1);
           $query = $this->db->get('user');
           $row = $query->row_array();
           if($row){
             $this->data = $row;
             $this->save_session('login');
           }
           return $row?$row:false;
           //if we have a user load the user
           //  
           //  $this->save_session('login');
     	  	//	$this->CI->load->model('User_Model','objLogin');
     	  	//	$this->CI->objLogin->restore_session('login');
           //  
           //  return TRUE;
         }
       }
       elseif($key)
       {
         $this->db->where('auth_key', $key);
         $this->db->where('(disabled=0 or disabled is null)');
         $this->db->limit(1);
         $query = $this->db->get('user');
         $row = $query->row_array();
         if($row){
           $this->data = $row;
           $this->save_session('login');
         }
         return $row?$row:false;
       }
       return FALSE;
     }

     function gen_key($user_id)
     {
       $this->db->where('id', $user_id)
                ->update($this->table, array("auth_key"=>uuid()));
     }
     */
  }
  
  private function _afterLoginSuccess()
  {
    /**
     * EXAMPLE
     $this->load->module_model('common','User_Model','objLogin');
     $this->objLogin->restore_session('login');
     
     if(isset($_REQUEST['login']) AND !$this->key)
     {
       $this->objLogin->gen_key($this->objLogin->get("id"));
       $this->objLogin->load_data($this->objLogin->get("id"));
        $this->objLogin->save_session('login');
        $this->objLogin->restore_session('login');
     }
     
     $this->objMemCache = new Memcache;
     $this->objMemCache->addServer(MEMCACHED_IP1,11211);
     $this->objMemCache->addServer(MEMCACHED_IP2,11211);
     */
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

}

?>