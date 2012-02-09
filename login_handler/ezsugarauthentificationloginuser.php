<?php

require_once 'kernel/classes/datatypes/ezuser/ezusersetting.php' ;
require_once 'kernel/classes/datatypes/ezuser/ezuser.php' ;
require_once 'lib/ezutils/classes/ezini.php' ;
require_once 'extension/connectezsugar/classes/sugarconnector.php';

/**
 * Classe eZSugarAuthentificationLoginUser
 *
 * Login handler pour l'authentification dans le contexte de la Sugar
 *
 * @category Extension
 * @package  SugarAuthentification
 * @author   Christophe Brun <christophe.brun@openwide.fr>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://www.openwide.fr
 */
 
class eZSugarAuthentificationLoginUser extends eZUser
{
    /**
     * Constructeur
     *
     * @return N/A
     */
    function eZSugarAuthentificationLoginUser()
    {
    }

    /**
     * Fonction getUserPlacement
     *
     * Retourne l'indentifiant principal du groupe 
     *
     * @param string $groupName Nom du groupe
     *
     * @return int mainNodeID Indentifiation du noeud principal
     */
    public static function getUserPlacement($groupName)
    {  
        $ini = eZINI::instance();
        $userGroupClassID = $ini->variable("UserSettings", "UserGroupClassID");
        $userGroupClientsName = $groupName ;
        $conditions = array('contentclass_id' => $userGroupClassID, 
                            'name'            => $userGroupClientsName);
        $userGroups = eZContentObject::fetchList(true, $conditions);

        if (empty($userGroups)) {  
            eZDebug::writeError('Unable to find user group "'.$groupName.'"!');
            return false;
        } else {  
            $clientGroupObject = $userGroups[0];
            eZDebug::writeError('Id du group assigne : '.$clientGroupObject->mainNodeID());
            return $clientGroupObject->mainNodeID();
        }
    }

    /**
     * Fonction assignNodeToContentObject
     *
     * Multipublication du compte utilisateur dans les groupes
     *
     * @param string $groupName Nom du groupe
     * @param int    $objectID  TODO
     * @param bool   $is_main   TODO
     *
     * @return TODO
     */
    public static function assignNodeToContentObject($groupName, $objectID, $isMain=false)
    {  
        $defaultUserPlacement = self::getUserPlacement($groupName);
        $contentObject = eZContentObject::fetch($objectID);
        $newNode = $contentObject->addLocation($defaultUserPlacement, true);
        $newNode->updateSubTreePath();
        $newNode->setAttribute('contentobject_is_published', 1);
        $newNode->sync();
    }

    /**
     * Fonction setGroupToUser
     *
     * Association d'un groupe a un utilisateur
     *
     * @param string $userGroupName Nom du groupe
     * @param object $user          TODO
     *
     * @return true
     */
    public static function setGroupToUser($userGroupName, $user)
    {
        self::assignNodeToContentObject($userGroupName, $user->id());
        return true;
    }

    /**
     * Fonction removeAllGroups
     *
     * Supprime le groupes de l'utilisateur (demultipublication)
     *   a l'exception du groupe par défaut
     *
     * @param object $user TODO
     *
     * @return TODO
     */
    public static function removeAllGroups($user) 
    {
        $ini = eZINI::instance("sugarauthentification.ini");
        $mappingGroups  = $ini->variable("MappingRoles", "MappingGroups");
        $listGroupsId = array();
        foreach ($mappingGroups as $group) {
            $idGroup=self::getUserPlacement($group);
            $listGroupsId[]=$idGroup;
        }
        $contentObject = eZContentObject::fetch($user->id());
        $assignedNodesList = $contentObject->assignedNodes();
        foreach ($assignedNodesList as $node) {
            $parentNodeID = $node->attribute('parent_node_id');
            if (in_array($parentNodeID, $listGroupsId)) {
                $node->removeThis();
            }
        }
        $contentObjectID = $user->attribute('contentobject_id');
        eZContentCacheManager::clearObjectViewCache($contentObjectID, true);
    }

    /*
    */
    /**
     * Fonction parseQuery
     *
     * Fonction qui découpage les paramètres de l'url pour les retourner sous forme de tableau
     *
     * @param string $var TODO
     *
     * @return int TODO
     */
    function parseQuery($var)
    {
        //$var  = parse_url($var, PHP_URL_QUERY);
        $var  = html_entity_decode($var);
        $var  = explode('&', $var);
        $arr  = array();

        foreach ($var as $val) {    
            $x          = explode('=', $val);
            $arr[$x[0]] = $x[1];
        }
        unset($val, $x, $var);
        return $arr;
    } 

    /**
     * Fonction getHeader
     *
     * TODO
     *
     * @param string $header TODO
     *
     * @return int TODO
     */
    function getHeader($header)
    {
        $retVal = array();
        $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
        foreach ($fields as $field) {
            if (preg_match('/([^:]+): (.+)/m', $field, $match)) {
                $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
                if (isset($retVal[$match[1]])) {
                    $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
                } else {
                    $retVal[$match[1]] = trim($match[2]);
                }
            }
        }
        return $retVal;
    }

    /**
     * Fonction createUser
     *
     * TODO
     *
     * @param string $login     Login de l'utilisateur
     * @param string $password  TODO
     * @param array  $user_data TODO
     *
     * @return TODO
     */
    public static function createUser($login, $password, $userData)
    {
        $ini = eZINI::instance();
        $userClassID = $ini->variable("UserSettings", "UserClassID");
        $userClass = eZContentClass::fetch($userClassID);
        $userCreatorID = $ini->variable("UserSettings", "UserCreatorID");
        $defaultSectionID = $ini->variable("UserSettings", "DefaultSectionID");
        $contentObject = $userClass->instantiate($userCreatorID, $defaultSectionID);
        $contentObject->store();

        $objectID = $contentObject->attribute('id');
        self::assignNodeToContentObject('UserGroupClientsName', $objectID, true);

        $version = $contentObject->version(1);
        $version->setAttribute('modified', time());
        $version->setAttribute('status', eZContentObjectVersion::STATUS_DRAFT);
        $version->store();

        $contentObjectAttributes = $version->contentObjectAttributes();
        foreach ($contentObjectAttributes as $attribute) {
            if (in_array(
                $attribute->attribute('contentclass_attribute_identifier'), 
                array('first_name', 'last_name')
            )) {
                $attribute->setAttribute('data_text', $userData['NOM']);
                $attribute->store();
            }
        }

        $operationResult = eZOperationHandler::execute(
            'content', 'publish', 
            array('object_id' => $objectID, 'version' => 1)
        );
        $user = eZUser::instance($objectID);
        $user->setAttribute('login', $login);
        self::setEmailAndPassword($user, $login, $password, $userData);

        self::loginSucceeded($user);
        return $user;
    }

    /**
     * Fonction setEmailAndPassword
     *
     * TODO
     *
     * @param object $user      Utilisateur
     * @param string $login     Login de l'utilisateur
     * @param string $password  TODO
     * @param array  $user_data TODO
     *
     * @return int mainNodeID Indentifiation du noeud principal
     */
    public static function setEmailAndPassword($user, $login, $password, $userData)
    {
        $email = 'GSM_' . $userData['BASE'] . '_' . $user->id() . '@sugar-noreply.fr';
        $user->setAttribute('email', $email);
        $hashType = eZUser::hashType();
        $hash = eZUser::createHash($login, $password, eZUser::site(), $hashType);
        $user->setAttribute('password_hash', $hash);
        $user->setAttribute('password_hash_type', $hashType);
        $user->store();
    }



    /**
     * Fonction loginUser
     *
     * Logs in the user if applied username and password is
     * valid. The userID is returned if succesful, false if not.
     *
     * @param string $login               Login de l'utilisateur
     * @param string $password            TODO
     * @param bool   $authenticationMatch TODO
     *
     * @return int mainNodeID Indentifiation du noeud principal
     */
    static function loginUser( $login, $password, $authenticationMatch = false )
    {
        // validation de l'authentification vers sugar
        $sugarConnector=new SugarConnector();
        $connection=$sugarConnector->login('admin','admin');
        $result=$sugarConnector->valid_contacts($login,$password);;
        if ($result['result_count']>0) {
            // Recuperation des infos du CRM
            $sugarID=$result['entry_list'][0]['id'];
            foreach ($result['entry_list'][0]['name_value_list'] as $fields) 
            {
                switch($fields['name']) {
                    case 'first_name':
                        $first_name = $fields['value'];
                        break;
                    case 'last_name':
                        $last_name = $fields['value'];
                        break;
                }
            }
            $user = eZUser::fetchByName( $login );

            $createNewUser = ( is_object($user) ) ? false : true;

            if ( $createNewUser ) {
                $attributes=array( 'user_account' => $login.'|'.$login.'|'.md5($password).'|md5_password|1',
                                  'first_name'    => $first_name,
                                  'sugarid'       => $sugarID,
                                  'last_name'     => $last_name);
                // TODO : groups id 110 a récupérer du settings
                $paramsCreate= array( 'creator_id'       => 14,
                                      'parent_node_id'   => 110,
                                      'class_identifier' => 'contacts',
                                      'attributes'       => $attributes);
                $user = eZContentFunctions::createAndPublishObject( $paramsCreate );
                //eZContentObject::clearCache( array( $userID ) );
                if (empty($user))
                {
                  throw new Exception('An error occurred when trying to create the user : "'.$login.'".');
                }
                $user = eZUser::fetchByName( $login );
            }
            $userID = $user->attribute( 'contentobject_id' );
            eZUser::updateLastVisit( $userID );
            eZUser::setCurrentlyLoggedInUser($user, $userID);
            return $user;
        } else {
            return false;
        }
    }
}
 
?>

