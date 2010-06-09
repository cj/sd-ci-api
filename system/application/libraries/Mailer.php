<?

/** PHPExcel root directory */
if (!defined('PHPMAILER_ROOT')) {
	define('PHPMAILER_ROOT', dirname(__FILE__) . '/');
}

/** PHPExcel_Cell */
require_once PHPMAILER_ROOT . 'PHPMailer/class.phpmailer.php';

class Mailer extends PHPMailer {
    // Set default variables for all new objects
    var $Host     = MAILER_HOST;
    var $Mailer   = MAILER_TYPE;   
                          // Alternative to IsSMTP()
    var $site_name = "";
    var $homepage_url = "";
    var $app_url = "";
    
    var $system_type = "";

    function __construct($type='default')
    {
      $this->CI =& get_instance();
      $this->IsHTML(true);
      
      $this->system_type = $type;
      
      $this->init();

    }
    
    public function __set($field, $value) {
      $this->$field = $value;
      if($field == 'Body')
      {
        $this->CI->load->module_model("sd_api","email_log_model",'log');

        if(!empty($this->to) AND isset($this->to[0])) $this->CI->log->set("to",implode(', ',$this->to[0]));
        if(!empty($this->cc) AND isset($this->cc[0])) $this->CI->log->set("cc",implode(', ',$this->cc[0]));
        if(!empty($this->bcc) AND isset($this->bcc[0])) $this->CI->log->set("bcc",implode(', ',$this->bcc[0]));
        $this->CI->log->set("system",strtoupper($this->system_type));
        $this->CI->log->set("subject",$this->Subject);
        $this->CI->log->set("body",$this->Body);
        $this->CI->log->set("date_time_created",date("Y-m-d H:i:s"));
        $this->CI->log->set("created_by",isset($this->CI->objLogin)?$this->CI->objLogin->get('id'):5000);

        $this->CI->log->save();
      }
    }
    
    function init()
    {
      //clear out all previous info if any
      $this->ClearAllRecipients();
      $this->ClearAttachments();
      $this->ClearCustomHeaders();
      
      $type = $this->system_type;
      if($type=='default'){
        $this->init_acd();
      }
      $this->Sender = $this->From;
    }
    
    function init_default()
    {
      $this->site_name = "Software Devs, LLC";
      $this->homepage_url = "http://www.softwaredevs.com";
      $this->app_url = "http://api.softwaredevs.com";
      
      $this->From = "api@softwaredevs.com";
      $this->FromName = "SD"; 
    }
    
    public function AddAddress($address, $name = '') {
  	  if(PRODUCTION_STATUS!='LIVE'){
  	    $address = DEV_EMAIL;
  	  } else {
  	    //if live we need to bcc this account for monitoring
  	    //$this->bcc('alertslive@autoclaimsdirect.com');
  	  }      
      parent::AddAddress($address, $name);
    }

    public function AddCC($address, $name = '') {
  	  if(PRODUCTION_STATUS!='LIVE'){
  	    $address = DEV_EMAIL;
  	  } else {
  	    //if live we need to bcc this account for monitoring
  	    //$this->bcc('alertslive@autoclaimsdirect.com');
  	  }      
      parent::AddCC($address, $name);
    }
    
    public function Send() {
      //do send
      $success = parent::Send();
      //re-initialize
      $this->init();
      
      $this->CI->log->set('date_time_sent', date("Y-m-d H:i:s"));
      $this->CI->log->save();
      
      return $success;
    }
    
    // Replace the default error_handler
    function error_handler($msg) {
        exit;
    }

}
?>