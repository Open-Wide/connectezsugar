<?php

class SugarConnector
{

    private $client;
    private $session;
    private $serverNamespace;
    private $login;
    private $password;

    function SugarConnector()
    {
        $ini = eZINI::instance("sugarcrm.ini");
        $serverUrl = $ini->variable("connexion", "ServerUrl");
        $serverPath = $ini->variable("connexion", "ServerPath");
        $this->serverNamespace = $ini->variable("connexion", "ServerNamespace");
        // @TODO : user et mdp pour login sont ecrites en clais dans le fichier sugarcrm.ini
		// chercher une autre façon de stockage plus securisé ?
        $this->login = $ini->variable("connexion", "login");
		$this->password = $ini->variable("connexion", "password");
        
        $this->client = new eZSOAPClient($serverUrl,$serverPath);
    }

    function login($login = null, $password = null)
    {
    	if(is_null($login))
    		$login = $this->login;
    	if(is_null($password))
    		$password = $this->password;
    		
        $auth_array = array( 
               'user_name' => $login,
               'password' => $password,
               'version' => '?'
        );

        $request = new eZSOAPRequest("login",$this->serverNamespace);
        $request->addParameter('user_auth',$auth_array);
        $request->addParameter('application_name','');
        $reponse = $this->client->send($request);
        $result  = $reponse->value();
        $this->session = $result['id'];
        return $this->session;
    }

    
	function get_entry_list($module,$query='',$order_by='',$offset='',$select_fields=array(),$max_results=999,$deleted=false)
    {
        $request = new eZSOAPRequest("get_entry_list",$this->serverNamespace);
        $request->addParameter('session',$this->session);
        $request->addParameter('module_name',$module);
        $request->addParameter('query',$query);
        $request->addParameter('order_by',$order_by);
        $request->addParameter('offset',$offset);
        $request->addParameter('select_fields',$select_fields);
        $request->addParameter('max_results',$max_results);
        $request->addParameter('deleted',$deleted);
        
        $reponse = $this->client->send($request);
        return $reponse->Value;
    }

    function get_entry($module,$id,$select_fields=array())
    {     
        $request = new eZSOAPRequest("get_entry", $this->serverNamespace);
        $request->addParameter('session',$this->session);
        $request->addParameter('module_name',$module);
        $request->addParameter('id',$id);
        $request->addParameter('select_fields',$select_fields);
        
        $reponse = $this->client->send($request);
        return $reponse->Value;
    } 

    function valid_contacts($login,$password) 
    {
       $query="canlogin_c=1 and password_c='".$password."' and contacts.id in (select eabr.bean_id from email_addr_bean_rel eabr join email_addresses ea on (ea.id = eabr.email_address_id) where eabr.deleted=0 and ea.email_address like '".$login."%')";
       $result=$this->get_entry_list('Contacts',$query);
       return $result->Value;
    }
    
    function get_module_fields($module_name)
    {
    	$request = new eZSOAPRequest("get_module_fields", $this->serverNamespace);
    	$request->addParameter('session',$this->session);
    	$request->addParameter('module_name',$module_name);
    	
    	$reponse = $this->client->send($request);
        return $reponse->Value;
    }
    
    function get_available_modules()
    {
    	$request = new eZSOAPRequest("get_available_modules", $this->serverNamespace);
    	$request->addParameter('session',$this->session);
    	
    	$reponse = $this->client->send($request);
        return $reponse->Value;
    }
}
?>
