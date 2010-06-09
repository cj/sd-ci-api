<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
  TODO: Create permissioning for POST and PUT
  
  create a global function like has_permissions_post_put that you can overide
  in any model to create more complex permissioning. Check user level, then if they have access
  do a GET to check if they have access to that claim. If superuser don't do give give them access
  straight away.
*/
class MY_Model extends Model {

  public $selectFields = array();
  public $results = '';

  public $currentTable = 'FALSE';
  
  public $php_excel;
  public $excel_added_rows = 0;
  public $letters = array("A","B","C","D","E","F","G","H","I","J","K","L","M",
  "N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
  
  public $option = array();
  public $request;
  
  public $data = array();
  public $original_data = array();
  public $_do_save = TRUE;
  protected $database;
  
	function __construct()
	{
    $this->CI =& get_instance();
    $this->request();
	}
	
	public function filterFields($field, $for_query = FALSE, $reverse = FALSE)
	{
		$newField = '';
		switch($field) {
			/*
			CJFN default
			*/
			default:
        if(!$reverse AND $for_query){
          $tableFields = implode(",",$this->fields);
          if(contains($tableFields,$field)) $this->selectFields[] = "$this->database.$this->table.$field AS $field";
        } 
        else if (!$reverse) $newField = $field; 
        else $newField = "$this->database.$this->table.$field";
        break;
		}
		return $newField;
	}
	
	function is_dirty(){
	  //if no original data it's definitely dirty
	  if(!count($this->original_data)) return true;
	  
	  //check to see if any of the original data has been changed
	  foreach($this->original_data as $key=>$val){
	    if($val!=$this->get($key)) return true;
	  }
	  return false;
	}
	
  function validate($rules = false, $onlyfields = false, $prefix = false){
    
    if(isset($this->validation)){
     $this->load->library('form_validation');
     
	   //if(!$prefix) $prefix = $this->table;
     if(!$rules) $rules = $this->validation;

	  /**
	  * modified foreach loop to replace $this->validation with $rules
	  * i.e. if passed to the function then passed rules should be used.
	  */
     foreach($rules as $validation){
       if($onlyfields && !in_array($validation['field'],$onlyfields)) continue; //don't validate fields that aren't in onlyfield if used
       //if($prefix) $validation['field'] = $prefix.'_'.$validation['field'];
       $prefixedRules[] = $validation;
     }
     $this->form_validation->set_rules($prefixedRules);
     $this->form_validation->set_error_delimiters('','');
     if ($this->form_validation->run() === FALSE){
       $this->errors = array();
       foreach($rules as $validation){
         $error_field = $validation['field'];
         $error_message = $this->form_validation->error($error_field);
         
         if($error_message) $this->errors[($prefix?$prefix."_":'').$this->filterFields($error_field)] = $error_message;
       }

       return FALSE;
     }
     return TRUE;
	   /*
     $arValidation = $this->validation;
	   foreach ($arValidation as $validation)
	   {
	     $this->form_validation->set_rules($rules);
	   }
	   */
	  } else {
	    return TRUE;
	  }
  }
  
  function set_fields(){
    $fields = $this->db->field_data($this->table);

		// Store only the field names and ensure validation list includes all fields
		foreach ($fields as $field)
		{
			// Populate fields array
			$this->fields[] = $field->name;

		}
  }
  public function clear(){
    $this->data = array();
    return $this;
  }
  public function reset(){
    $this->data = array();
    $this->original_data = array();    
    return $this;
  }
  public function clear_request(){
    $_REQUEST = array();
    return $this;
  }
  function save_session($session_name)
	{
	  $this->session->set_userdata($session_name,$this->data);
	}
	
  function restore_session($session_name)
	{
    $this->data = $this->session->userdata($session_name);
	}
	
  function get_form($prefix = FALSE, $do_validation = TRUE,$custom_validation = FALSE)
  {
    $ar_data = array();

    foreach($this->option as $key => $value)
    {
      if(!$prefix || preg_match("/".$prefix."_/", $key))
      {
        if($prefix) $key = preg_replace("/".$prefix."_/", "", $key, 1);
        $filtered_key = $this->filterFields($key,FALSE,TRUE);
        if(is_string($filtered_key)) $key = $filtered_key;
        if($key != 'id') $ar_data[end(explode(".",$key))] = $value;
        if($do_validation) if($key != 'id') @$_POST[end(explode(".",$key))] = $value;
      }
    }
    
    if($do_validation)
    {
      $is_valid = $this->validate($custom_validation?$custom_validation:FALSE,FALSE,$prefix);
    } else $is_valid = TRUE;
    if($is_valid){      
      foreach($ar_data as $key => $value){
        if($key != 'id') $this->set(end(explode(".",$key)),$value);
      }
    }
  
    return $is_valid;
  }
  
	function set( $sproperty, $svalue ) {
		if (in_array($sproperty,$this->fields)) {
			//Added the isset check in below line as for the comparison it should be set already
			if(isset($this->data[$sproperty]) AND ($this->data[$sproperty]!=$svalue)) $this->dirty = true;
			return ($this->data[$sproperty] = $svalue);
		} else {
      //throw error
      return false;
		}
	}
	function get( $sproperty ){
		switch ($sproperty) {
			default:
				if (isset($this->data[$sproperty])) {
					return $this->data[$sproperty];
				} else {
          //throw error
          return false;
				}
				break;
		}
  }
  function save($data = null, $id = null)
  {
  
  	if ($data)
  	{
  		$this->data = $data;
  	}

    $this->pre_save();
    
    if($this->_do_save AND $this->is_dirty())
    {
      if($this->data){
    	  foreach ($this->data as $key => $value)
    	  {
    	  	if (array_search($key, $this->fields) === FALSE)
    	  	{
    	  		unset($this->data[$key]);
    	  	}
    	  	if ($value=='' && $value!==0)
    	  	{
    	  		$this->data[$key] = NULL;
    	  	}
    	  }

    	  if ($id != null)
    	  {
    	  	//$this->id = $id;
    	  	$this->data['id'] = $id;
    	  }

    	  //$id = $this->id;
    	  $id = $this->get('id');

    	  if ($this->get('id') !== null && $this->get('id') !== false)
    	  {
    	    if(!$this->is_dirty()) return;
    	  	//$this->db->where($this->primaryKey, $id);
    	  	$this->data['date_time_modified'] = date('Y-m-d H:i:s');
    	    $this->data['modified_by'] = $this->CI->objLogin->get('id');
    	  	$this->db->where('id', $id);
    	  	$this->db->update($this->database.'.'.$this->table, $this->data);

    	  	//$this->__affectedRows = $this->db->affected_rows();
    	  }
    	  else
    	  {
    	    $this->data['date_time_created'] = date('Y-m-d H:i:s');
    	    if(!isset($this->data['created_by']) || !$this->data['created_by']) $this->data['created_by'] = $this->CI->objLogin->get('id');
    	    //need to support legacy systems that use dtcreated
    	    if(in_array('dtcreated',$this->fields)){
      	    $this->data['dtcreated'] = date('Y-m-d H:i:s');
    	    }
    	    
    	  	$this->db->insert($this->database.'.'.$this->table, $this->data);

    	  		//$this->__insertID = $this->db->insert_id();
    	  		//return $this->__insertID;
          $this->data['id'] = $this->db->insert_id();
    	  }
    }
  
  	  return $this;
    }
  }
  
  function load_data($id = null, $fields = null, $filter = FALSE)
	{
		if ($id !== null)
		{
			//$this->id = $id;
  		$this->data['id'] = $id;
		}

		$id = $this->data['id'];
    
		if ($this->data['id'] !== null && $this->data['id'] !== false)
		{
			$this->data = $this->find($this->database.'.'.$this->table . '.id = ' . $id, $fields, NULL,0,NULL,TRUE);
			$this->original_data = $this->data;
			
			if($filter){
			  foreach($this->data as $key => $value){
          $filtered_key = $this->filterFields($key,FALSE);
  	      if($filtered_key != $key){
  	        unset($this->data[$key]);
  	        $this->data[$filtered_key] = $value;
  	        unset($this->original_data[$key]);
  	        $this->original_data[$filtered_key] = $value;
  	      }
  	    }
			}
			
			return $this->data;
		}
		else
		{
			return false;
		}
	}
	
	function findAll($conditions = NULL, $fields = '*', $order = NULL, $start = 0, $limit = NULL, $filter = FALSE)
	{
		if ($conditions != NULL)
		{
			$this->db->where($conditions);
		}

		if ($fields != NULL)
		{
			$this->db->select($fields);
		}

		if ($order != NULL)
		{
			$this->db->orderby($order);
		}

		if ($limit != NULL)
		{
			$this->db->limit($limit, $start);
		}

		$query = $this->db->get($this->database.'.'.$this->table);
		//$this->__numRows = $query->num_rows();

		//return ($this->returnArray) ? $query->result_array() : $query->result();
		
		return $query->result_array();
	}
	
	function find($conditions = NULL, $fields = '*', $order = 'id ASC')
	{
		$data = $this->findAll($conditions, $fields, $order, 0, 1);

		if ($data)
		{
			return $data[0];
		}
		else
		{
			return false;
		}
	}
	
  function url_base64_decode($str){
    return strtr(base64_decode($str),
      array(
        '+' => '.',
        '=' => '-',
        '/' => '~'
      )
    );
  }

  public function where($fields,$extra = FALSE){
    foreach($fields AS $field){
      if(isset($this->option[$field])){
        $value = $this->option[$field];
        $field = $this->filterFields($field,FALSE,TRUE);
        if($field=='id')
          $this->db->where("$this->database.$this->table.$field",$value);
        else
          if($field) $this->db->where($field,$value);
      }
    }
  }
  
  public function or_where($fields,$extra = FALSE){
    foreach($fields AS $field){
      if(isset($this->option[$field]) AND $this->option[$field]){
        $value = $this->option[$field];
        $field = str_replace('_or_where','',$field);
        $field = $this->filterFields($field,FALSE,TRUE);
        $this->db->or_where($field,$value);
      }
    }
  }
  
  public function where_not($fields,$extra = FALSE){
    foreach($fields AS $field){
      if(isset($this->option[$field])){
        $value = $this->option[$field];
        $field = str_replace('_not','',$field);
        $field = $this->filterFields($field,FALSE,TRUE);
        if($field=='id')
          $this->db->where("$this->database.$this->table.$field !=",$value);
        else
          if($field) $this->db->where("$field !=",$value);
      }
    }
  }
  
  public function range($fields,$extra = FALSE){
    foreach($fields AS $field){
      if(isset($this->option[$field]) AND $this->option[$field]){
        $value = explode('|',$this->option[$field]);
        $from = $value[0];
        $to = $value[1];
        $field = str_replace('_range','',$field);
        $field = $this->filterFields($field,FALSE,TRUE);
        $this->db->where("$field >=",$from);
        $this->db->where("$field <=",$to);
      }
    }
  }
  
  public function where_not_in($fields,$extra = FALSE){
    foreach($fields AS $field){
      if(isset($this->option[$field]) AND $this->option[$field]){
        $value = explode('|',$this->option[$field]);
        $field = str_replace('_not_in','',$field);
        $field = str_replace('_not','',$field);
        $field = $this->filterFields($field,FALSE,TRUE);
        $this->db->where_not_in("$field",$value);
      }
    }
  }

  public function where_null($fields,$extra = FALSE){
    foreach($fields AS $field){
      if(isset($this->option[$field])){
        $value = $this->option[$field];
        $field = str_replace('_null','',$field);        
        $field = $this->filterFields($field,FALSE,TRUE);
        $this->db->where($field.' IS NULL');        
      }
    }
  }

  public function where_not_null($fields,$extra = FALSE){
    foreach($fields AS $field){
      if(isset($this->option[$field])){
        $value = $this->option[$field];
        $field = str_replace('_not_null','',$field);        
        $field = $this->filterFields($field,FALSE,TRUE);
        $this->db->where($field.' IS NOT NULL');        
      }
    }
  }
  
  public function like($fields,$extra = FALSE){
    foreach($fields AS $field){
      if(isset($this->option[$field]) AND $this->option[$field]){
        $value = explode('|',$this->option[$field]);
        $field = str_replace('_like','',$field);
        $field = $this->filterFields($field,FALSE,TRUE);
        $this->db->like($field,$value[0],(isset($value[1])?$value[1]:'both'));
      }
    }
  }

  public function or_like($fields,$extra = FALSE){
    foreach($fields AS $field){
      if(isset($this->option[$field]) AND $this->option[$field]){
        $value = explode('|',$this->option[$field]);
        $field = str_replace('_or_like','',$field);
        $field = $this->filterFields($field,FALSE,TRUE);
        $this->db->or_like($field,$value[0],(isset($value[1])?$value[1]:'both'));
      }
    }
  }

  
  public function where_in($fields,$extra = FALSE){
    foreach($fields AS $field){
      if(isset($this->option[$field]) AND $this->option[$field]){
        $value = explode('|',$this->option[$field]);
        $field = str_replace('_in','',$field);
        $field = $this->filterFields($field,FALSE,TRUE);
        $this->db->where_in("$field",$value);
      }
    }
  }
  public function search(){
    return $this;
  }
  
  public function finalize(){
    return $this;
  }

  function permissions(){
    return $this;
  }
  public function do_joins(){
    return $this;
  }
  public function pre_save(){
    return $this;
  }
  
  public function request($ar_native = FALSE,$clear = FALSE)
  {
    if($clear) $this->option = array();
    foreach(($ar_native?$ar_native:$_REQUEST) as $key => $value)
    {
      if(!contains("PHPSESSID,__utmz,__utma,APE_Cookie",$key)) $this->option[$key] = $value;
    }
  }
  
  public function custom(){
    $this->permissions();
    if(isset($this->option['fields'])){ 
      $ar_fields = explode(' ',trim($this->option['fields']));
      foreach($ar_fields AS $field) {
        $this->filterFields($field,TRUE);
      }
    }
    else $this->filterFields('defaults',TRUE);
        
    $this->search();

    $this->do_joins();

		if(isset($this->option['group'])){
    	$ar_group = explode(',',$this->option['group']);
    	$this->db->group_by($ar_group);
    }
    
    return $this;
  }

	public function run_api(){
    if(isset($this->option['start']) || isset($this->option['start'])){
      $this->custom();
      $this->selectFields[] = "$this->database.$this->table.id AS id";
  		$this->total_count = count($this->db->select(array_unique($this->selectFields),FALSE)->get("$this->database.$this->table")->result_array());
    }
		
		$this->custom();
		if(isset($this->option['order']) || isset($this->option['order'])){
    	$ar_order = explode(',',$this->option['order']);
    	foreach($ar_order as $order){
    	  if(isset($this->option['dir'])) $this->db->order_by("$order", strtoupper($this->option['dir']));
    	  else $this->db->order_by(trim($order));
    	}  				  
    }

    //set max to 500
    if(isset($this->option['limit']) && $this->option['limit']>500) $this->option['limit'] = 500;

		if(isset($this->option['limit']) && isset($this->option['start'])) $this->db->limit($this->option['limit'],$this->option['start']);
		elseif(isset($this->option['limit'])) $this->db->limit($this->option['limit']);
    $this->selectFields[] = "$this->database.$this->table.id AS id";
		$this->db->select(array_unique($this->selectFields),FALSE);
		$query = $this->db->get($this->database.".".$this->table);
		$this->results = $query->result_array();
		
		if(!isset($this->option['start'])){
  		$this->total_count = count($this->results);
    }
	  
	  $this->finalize();
	  
	  // debug
	  if(isset($_REQUEST['debug'])) print_r($this->db->queries);
	  
		return $this->results;
		
		
	}

	function remove($id = null)
	{
		if ($id != null)
		{
			$this->data['id'] = $id;
		}

		$id = $this->data['id'];

		if ($this->data['id'] !== null && $this->data['id'] !== false)
		{
			if ($this->db->delete($this->table, array('id' => $id)))
			{
				$this->data['id'] = null;
				$this->data = array();

				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}
}
// END Model Class
