<?php

include_once( 'extension/connectezsugar/scripts/genericfunctions.php' );

class SugarConnector
{
	const LOGFILE = "var/log/SugarConnector";
	
	private static $definition = array();
	private static $properties_list;
	private static $inidata_list;
	private static $query_standard_return;
	
    private $client;
    private static $session = FALSE;
    private $serverNamespace;
    private $serverUrl;
    private $login;
    private $password;
	protected $logger;
    
	
	public static function definition()
	{
		// inidata_list ***
		$inidata_list = array(	'serverUrl'			=> array( 'block' => "connexion", 'var' => "ServerUrl" ),
								'$serverPath'		=> array( 'block' => "connexion", 'var' => "ServerPath" ),
								'serverNamespace'	=> array( 'block' => "connexion", 'var' => "ServerNamespace" ),
								'login'				=> array( 'block' => "connexion", 'var' => "login" ),
								'password'			=> array( 'block' => "connexion", 'var' => "password" )
							); 
		self::$inidata_list = $inidata_list;
		
		// properties_list ***
		$properties_list = array(	'client',
									'session',
									'serverNamespace',
									'login',
									'password',
									'logger'
								);
		self::$properties_list = $properties_list;
		
		// query_standard_return
		$query_standard_return = array( 'get_available_modules' => array( 'data' => 'modules' ),
										'get_module_fields' 	=> array( 'data' => 'module_fields' ),
										'get_entry_list' 		=> array( 'data' => 'entry_list' ),
										'sync_get_relationships' => array( 'data' => 'entry_list' ),
										'set_entry' 			=> array( 'data' => 'id' ),
										'get_relationships'		=> array( 'data' => 'ids' ),
										'get_entry'	=> array( 	'data' 				=> array( 'entry_list',0,'name_value_list'),
																'checkForWarning' 	=> array( 	'check'		=> array( 	'who' => 'field_list',
																														'what' => 'count==0' ),
																								'where'		=> array( 'entry_list',0,'name_value_list',0,'name'),
																								'warning'	=> array('entry_list',0,'name_value_list',0),
																							),
															),
										
									);
		self::$query_standard_return = $query_standard_return;
		
		
		// tableau complet *******
		$definition = array('properties_list' => $properties_list,
							//'parameters_per_function' => $parameters_per_function,
							'inidata_list' => $inidata_list
							);
		self::$definition = $definition;
										
		return $definition;
	}

	
	/*
     * prend en parametre un tableau $array à creuser
     * en passant par le chemin indiqué par le tableau $way
     * $way doit être un tableau ou les elements sont, dans l'ordre, les niveau(nom de clé) à descendre dans le tableau $array
     * 
     * @param $array array
     * @param $way array
     */
    public static function arrayDig($array,$way)
    {
    	$loop = $array;
		for( $i=0; $i<count($way); $i++ )
		{
			$loop = $loop[$way[$i]];
		}
		
		$result = $loop;
		
		return $result;
    }
	
	
	/*
	 * CONSTRUCTEUR
	 */
    function SugarConnector()
    {
    	self::definition();
    	
        $ini = eZINI::instance("sugarcrm.ini");
        $this->serverUrl = $ini->variable("connexion", "ServerUrl");
        $serverPath = $ini->variable("connexion", "ServerPath");
        $this->serverNamespace = $ini->variable("connexion", "ServerNamespace");
        // @TODO : user et mdp pour login sont ecrites en clair dans le fichier sugarcrm.ini
		// chercher une autre façon de stockage plus securisé ?
        $this->login = $ini->variable("connexion", "login");
		$this->password = $ini->variable("connexion", "password");
        
        $this->client = new eZSOAPClient($this->serverUrl, $serverPath);
        
        $this->logger = owLogger::CreateForAdd(self::LOGFILE . date("d-m-Y") . ".log");
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
    	if ( self::$session === FALSE ) { 
    		echo 'SugarConnector login - ' . $this->serverUrl . PHP_EOL;
    		$this->logger->writeTimedString('Connexion à ' . $this->serverUrl);
    		
	    	if(is_null($login))
	    		$login = $this->login;
	    	if(is_null($password))
	    		$password = $this->password;   		
	        $auth_array = array( 
	               'user_name' => $login,
	               'password' => md5( $password ),
	               'version' => '?'
	        );
	        $request = new eZSOAPRequest("login",$this->serverNamespace);
	        $request->addParameter('user_auth',$auth_array);
	        $request->addParameter('application_name','');
	        $reponse = $this->client->send($request);
	        $result  = $reponse->value();
	        
			if( $this->checkForErrors($result,"login") )
				return false;
			
	        self::$session = $result['id'];
    	}
        return self::$session;
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
    				//evd($response_value);
    				$warning = "checkForErrors(\$response_value,$queryname) : \$response_value['error'] pas trouvé ! verifier le tableau envoyé à la mèthode !";
    				$this->logger->writeTimedString($warning);
    			}
    		}
    		else
    		{
    			$errorDetails = $this->getResponseErrorDetails($response_value);
    			$this->logger->writeTimedString($errorDetails);
    		}
    		
    		// return "ERROR" ????
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
    
    
    protected function findWarning($result, $warninginfos)
    {
    	$where = $warninginfos['where'];
    	
    	if( is_array($where) )
			$wheredata = self::arrayDig($result,$where);
		else
			$wheredata = $result[$where];
			
		if( $wheredata == 'warning' )
			return self::arrayDig( $result, $warninginfos['warning'] );
		else
			return false;
    }
    
    
	protected function checkForWarning($result, $warninginfos)
    {
    	$who = $warninginfos['check']['who'];
    	$what = $warninginfos['check']['what'];
    	
    	switch( $what )
    	{
    		case "count==0" :
    			if( count($result[$who]) == 0 )
    				$warning = $this->findWarning($result, $warninginfos);
    			else
    				$warning = false;
    			break;
    			
    		default :
    			$warning = false;
    			break;
    	}

    	return $warning;
		
    }
    
    
    protected function writeWarningInError($result, $warning)
    {
    	$result['error'] = array('number' => "99", 'name' => $warning['name'], 'description' => $warning['value']);
    	
    	return $result;
    }
    
    
	protected function standardQueryReturn($result, $queryname)
    {
    	if( !isset(self::$query_standard_return[$queryname]) )
    	{
    		$alert = "ALERTE : standardQueryReturn() : $queryname non trouvé dans self::\$query_standard_return !!!";
    		$this->logger->writeTimedString($alert);
    		return $result;
    	}
    	
    	$queryinfos = self::$query_standard_return[$queryname];
    	
    	if( $this->checkForErrors($result, $queryname) )
			return array('error' => $result['error']); // return "ERROR" ????
		
			
		if( isset($queryinfos['checkForWarning']) )
		{
			$warning = $this->checkForWarning($result, $queryinfos['checkForWarning']);
			if( $warning !== false )
			{
				$result = $this->writeWarningInError($result,$warning);
				$this->checkForErrors($result, $queryname);
				return array('error' => $result['error']);
			}
		}

		if( is_array($queryinfos['data']) )
			$resultdata = self::arrayDig($result,$queryinfos['data']);
		elseif( is_string($queryinfos['data']) && $queryinfos['data'] == "" )
			$resultdata = $result;
		else
			$resultdata = $result[$queryinfos['data']];
			
		return array( 'data' => $resultdata);
    }
    
    
	function get_entry_list($module, $select_fields=array(), $offset='', $max_results=9999, $query='', $order_by='', $deleted=false)
    {
    	//mvd(array($module,$select_fields));
    	
        $request = new eZSOAPRequest("get_entry_list",$this->serverNamespace);
        $request->addParameter('session',self::$session);
        $request->addParameter('module_name',$module);
        $request->addParameter('query',$query);
        $request->addParameter('order_by',$order_by);
        $request->addParameter('offset',$offset);
        $request->addParameter('select_fields',$select_fields);
        $request->addParameter('max_results',$max_results);
        $request->addParameter('deleted',$deleted);
        
        $reponse = $this->client->send($request);
        $result = $reponse->Value; //evd($reponse);
        
        return $this->standardQueryReturn($result, "get_entry_list");
    }

    function get_entry($module,$id,$select_fields=array())
    {
    	// si $select_fields est definie on rajoute le champs warning
    	// autrement on ne voit pas l'erreur !
    	if( count($select_fields) > 0 )
			$select_fields[] = "warning";
    	
        $request = new eZSOAPRequest("get_entry", $this->serverNamespace);
        $request->addParameter('session',self::$session);
        $request->addParameter('module_name',$module);
        $request->addParameter('id',$id);
        $request->addParameter('select_fields',$select_fields);
        
        $reponse = $this->client->send($request);
        $result = $reponse->Value;
        
        return $this->standardQueryReturn($result, "get_entry");
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
    	$request->addParameter('session',self::$session);
    	$request->addParameter('module_name',$module_name);
    	
    	$reponse = $this->client->send($request);
        $result =  $reponse->Value;
        
        return $this->standardQueryReturn($result, "get_module_fields");

    }
    
    function get_available_modules()
    {
    	$request = new eZSOAPRequest("get_available_modules", $this->serverNamespace);
    	$request->addParameter('session',self::$session);
    	
    	$reponse = $this->client->send($request);
        $result = $reponse->Value;
        
        return $this->standardQueryReturn($result, "get_available_modules");
    }
    
	function get_relationships($module, $id, $related_module, $related_module_query = '') 
    {    	
        $request = new eZSOAPRequest("get_relationships", $this->serverNamespace);
        $request->addParameter('session',self::$session);
        $request->addParameter('module_name',$module);
        $request->addParameter('module_id',$id);
        $request->addParameter('related_module',$related_module);
        $request->addParameter('related_module_query',$related_module_query);
        
        $reponse = $this->client->send($request);
        $result = $reponse->Value;
        
        return $this->standardQueryReturn($result, "get_relationships");
    }
    
    /*
     * Sens eZ => SugarCRM
     */
    
    public function set_entry( $module_name, $id, $fields ) {
    	$fields = array_merge(
    		array( 'id' => $id ), 
    		$fields 
    	);
	    $params = array(
		    'module_name'     => $module_name,
		    'name_value_list' => $this->name_value_list( $fields ),
	    );
	    
	    //throw new \Exception( 'Je ne veux pas travailler, signé le client SOAP' );
	    $request = new eZSOAPRequest( 'set_entry', $this->serverNamespace );
	    $request->addParameter( 'session', self::$session );
	    $request->addParameter( 'module_name', $params['module_name'] );
	    $request->addParameter( 'name_value_list', $params['name_value_list'] );

	    $reponse = $this->client->send( $request );
	    $result  = $reponse->Value;
	    $this->logger->writeTimedString($result);
    	
	    return $this->standardQueryReturn($result, 'set_entry');
    }

	private function name_value_list( $fields ) {
		$name_value_list = array( );
		foreach ( $fields as $name => $value ) {
			$name_value_list[ ] = array( 'name' => $name, 'value' => str_replace( '\'', '&#039;', $value ) );
		}
		return $name_value_list;
	}

	private function name_value_lists( $entries ) {
		$name_value_lists = array( );
		foreach ( $entries as $fields ) {
			$name_value_lists[ ] = $this->name_value_list( $fields );
		}
		return $name_value_lists;
	}
	
	public function sync_get_relationships( $module_name, $related_module, $from_date, $to_date, $offset, $max_results, $deleted ) {
		$request = new eZSOAPRequest( 'sync_get_relationships', $this->serverNamespace );
		$request->addParameter( 'session', self::$session );
		$request->addParameter( 'module_name', $module_name );
		$request->addParameter( 'related_module', $related_module );
		$request->addParameter( 'from_date', $from_date );
		$request->addParameter( 'to_date', $to_date );
		$request->addParameter( 'offset', $offset);
		$request->addParameter( 'max_results', $max_results );
		$request->addParameter( 'deleted', $deleted );
		
		$reponse = $this->client->send( $request );
		$result  = $reponse->Value;
		
		return $this->standardQueryReturn( $result, 'sync_get_relationships' );
	}
}
?>
