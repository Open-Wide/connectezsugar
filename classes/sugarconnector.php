<?php

include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );

class SugarConnector
{
	const LOGFILE = "var/log/SugarConnector.log";
	
    private $client;
    private $session;
    private $serverNamespace;
    private $login;
    private $password;
	private $logger;
    
	
	/*
	 * CONSTRUCTEUR
	 */
    function SugarConnector()
    {
        $ini = eZINI::instance("sugarcrm.ini");
        $serverUrl = $ini->variable("connexion", "ServerUrl");
        $serverPath = $ini->variable("connexion", "ServerPath");
        $this->serverNamespace = $ini->variable("connexion", "ServerNamespace");
        // @TODO : user et mdp pour login sont ecrites en clair dans le fichier sugarcrm.ini
		// chercher une autre façon de stockage plus securisé ?
        $this->login = $ini->variable("connexion", "login");
		$this->password = $ini->variable("connexion", "password");
        
        $this->client = new eZSOAPClient($serverUrl,$serverPath);
        
        $this->logger = owLogger::CreateForAdd(self::LOGFILE);
    }

    
	public function lastLogContent($asString = false)
	{
		return $this->logger->getLogContentFromCurrentStartTime($asString);
	}
    
	
    /*
     * login to sugar
     * si login et password sont omis utilise login/password du fichier sugarcrm.ini 
     * 
     * @param $login string
     * @param $password string
     * @return $session_id string OR FALSE si il y a une erreur
     */
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
        
		if( $this->checkForErrors($result,"login") )
			return false;
		
        $this->session = $result['id'];
        return $this->session;
    }

    /*
     * checkForErrors verifie si il y a des erreurs dans la reponse de la reqête SOAP
     * il faut lui envoyer le tableau de type eZSOAPResponse->Value
     * si il y a des erreur return TRUE et écris dans le log
     * si il n'y a pas d'erreurs return FALSE
     * 
     * @param $response_value array
     * @param $queryname string
     * @return boolean
     */
	protected function checkForErrors($response_value, $queryname)
    {
    	$errorNumber = (int)$this->getResponseErrorNumber($response_value);
    	
    	if($errorNumber != 0)
    	{
    		if($errorNumber === -1)
    		{
    			if( $response_value === false && $queryname === "login")
    			{
    				$error = "login() renvoie false ! Verifier les parametres de connexion à SUGAR";
    				$this->logger->writeTimedString($error);
    			}
    			else
    			{
    				$warning = "checkForErrors(\$response_value,$queryname) : \$response_value['error'] pas trouvé ! verifier le tableau envoyé à la mèthode !";
    				$this->logger->writeTimedString($warning);
    			}
    		}
    		else
    		{
    			$errorDetails = $this->getResponseErrorDetails($response_value);
    			$this->logger->writeTimedString($errorDetails);
    		}
    		
    		return true;
    	}
    	
    	return false;
    }
    
    /*
     * @return error_number
     */
    protected function getResponseErrorNumber($response_value)
    {
    	if( !is_array($response_value) || !isset($response_value['error']) )
    		return -1;
    		
    	return $response_value['error']['number'];
    }
    
    /*
     * @return "error_name : error_description"
     */
    protected function getResponseErrorDetails($response_value)
    {
    	if( !is_array($response_value) || !isset($response_value['error']) )
    		return -1;
    		
    	$errorDetails = $response_value['error']['name'] . " : " . $response_value['error']['description'];
    	
    	return $errorDetails;
    }
    
    
	protected function standardFunctionReturn($result, $queryname, $dataname)
    {
    	if( $this->checkForErrors($result, $queryname) )
			return array('error' => $result['error']);
			
		return array( 'data' => $result[$dataname]);
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

    function get_entry($module,$id,$select_fields=array(), $warning=false)
    {     
    	
        $request = new eZSOAPRequest("get_entry", $this->serverNamespace);
        $request->addParameter('session',$this->session);
        $request->addParameter('module_name',$module);
        $request->addParameter('id',$id);
        $request->addParameter('select_fields',$select_fields);
        
        $reponse = $this->client->send($request);
        $result = $reponse->Value; 
        
        //return $result;
        
        $name_value_list = $result['entry_list'][0]['name_value_list'];
        
        //return $name_value_list;
        
        if( count($result['field_list']) == 0 and !$warning )
        {
        	$checkWarning = $this->checkWarning($module,$id);
        	
        	if( $checkWarning !== false )
        		return $this->standardFunctionReturn($checkWarning, "get_entry", "data");
        }
        
        
        return $this->standardFunctionReturn($result, "get_entry", "entry_list");
    }
    
    function checkWarning($module,$id)
    {
    	$checkWarning = $this->get_entry($module, $id, array('warning'), true);
    	$name_value_list = $checkWarning['data'][0]['name_value_list'];
       	if( count($name_value_list) > 0 )
       	{
     		$warning = $name_value_list[0];
     		
     		$checkWarning['error'] = array('number' => "99", 'name' => $warning['name'], 'description' => $warning['value']);
       		
       		return $checkWarning;
       	}	
       	else
       		return false;
       		
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
        $result =  $reponse->Value;
        
        return $this->standardFunctionReturn($result, "get_module_fields", "module_fields");

    }
    
    function get_available_modules()
    {
    	$request = new eZSOAPRequest("get_available_modules", $this->serverNamespace);
    	$request->addParameter('session',$this->session);
    	
    	$reponse = $this->client->send($request);
        $result = $reponse->Value;
        
        return $this->standardFunctionReturn($result, "get_available_modules", "modules");
    }
    
    
}
?>
