<?php
require_once('Rest/Model/AclHandler/Interface.php');
require_once('Rest/Model/AclHandler/Abstract.php');
require_once('Rest/Model/MethodNotAllowedException.php');
require_once('Rest/Model/BadRequestException.php');
require_once('Util/Array.php');

class Default_Model_AclHandler_Entourage
    extends Rest_Model_AclHandler_Abstract
{
    /**
     * @var Default_Model_Handler_Entourage
     */
    protected $_modelHandler;

    /**
     * @var PDO
     */
    protected $_dbHandler;

    protected function _initAclRules()
    {
        $acl = $this->getAcl();

        $acl->addResource($this);

        if (!$acl->hasRole('default')) {
            $acl->addRole('default');
        }

        $acl->allow('default', $this, array('get', 'post'));
        $acl->deny('default', $this, array('put', 'delete'));
    }

    /**
     * Used mainly to ensure that the required keys have been passed to
     * controllers that inturn implement model handlers
     *
     * @return array
     */
    public static function getIdentityKeys()
    {
        return array();
    }

    /**
     * Used mainly for testing property requests, where clauses and the like
     * @return array
     */
    public static function getPropertyKeys()
    {
        return array();
    }

    /**
     *   $params = array(
     *       'User' => array(
     *           'entourage' => array(
     *               'Entry' => array(
     *                   'entourageModel' => 'Entry',
     *                   'entourageIdKey' => 'creator_user_id',
     *                   'resourceIdKey' => 'id',
     *               ),
     *           ),
     *       ),
     *       'Entry' => array(
     *           'entourage' => array(
     *               'Creator' => array(
     *                   'entourageModel' => 'User',
     *                   'entourageIdKey' => 'id',
     *                   'resourceIdKey' => 'creator_user_id',
     *                   'singleOnly' => true,
     *               ),
     *           ),
     *       ),
     *   );
     *
     *
     * @param array $params
     * @return array
     */
    public function getList(array $params = null)
    {
        // validate that the needed parameters have been passed
        if (!is_array($params) || !count($params)) {
            throw new Rest_Model_BadRequestException('must provide a set of source resources for attaching entourage resources');
        }

        $data = array();

        foreach($params as $name => $resourceParam) {
            $resourceName = 'Default_Model_AclHandler_' . $name;

            // need to supress warnings that class_exists produces if the class
            // doesn't exist. Incredibly stupid design that this function
            // produces warning when being used as designed. Not sure if these
            // supressed warnings are showing up in some log. Stupid, stupid.
            // try/catch attempts around it don't help.
            if (!@class_exists($resourceName)) {
                throw new Rest_Model_BadRequestException('resource "' . $name . '" does not exist');
            }

            $resourceHandler = new $resourceName($this->getAcl(), $this->getAclContextUser());

            $entourageSetParam = isset($resourceParam['entourage']) ? $resourceParam['entourage'] : null;

            // get the resource list
            unset($resourceParam['entourage']);
            $resourceList = $resourceHandler->getList($resourceParam);

            if (null !== $entourageSetParam) {
                $this->_entouragePopulate($entourageSetParam, $resourceList, $resourceHandler);
            }

            $data[$name] = $resourceList;
        }

        return $data;
    }

    /**
     * @param array $id
     * @return array
     * @throws Rest_Model_NotFoundException, Zend_Acl_Exception
     */
    public function get(array $id, array $params = null)
    {
        if (null === $params || !is_array($params) || empty($params)) {
            throw new Rest_Model_BadRequestException('must provide a source resource for attaching entourage resources');
        }

        // take just the first param, any additional ones will be ignored
        $resourceHandler = current($params);
        $entourageSetParam = next($params);

        if (!($resourceHandler instanceof Rest_Model_AclHandler_Interface)) {
            // create the full resource name
            $resourceName = 'Default_Model_AclHandler_' . $resourceHandler;

            // see angry comment above about the stupidity of this function
            if (!@class_exists($resourceName)) {
                throw new Rest_Model_BadRequestException('resource "' . $resourceHandler . '" does not exist');
            }

            $resourceHandler = new $resourceName($this->getAcl(), $this->getAclContextUser());
        }

        // get the resource
        $resource = $resourceHandler->get($id);

        // attach an entourage resources that are specified
        if (null !== $entourageSetParam) {
            $resourceList = array($resource);
            $this->_entouragePopulate($entourageSetParam, $resourceList, $resourceHandler);
            $resource = $resourceList[0];
        }

        return $resource;
    }

    /**
     * @param array $id
     * @param array $prop
     * @return array
     * @throws Rest_Model_NotFoundException, Zend_Acl_Exception
     */
    public function put(array $id, array $prop = null)
    {
        throw new Rest_Model_MethodNotAllowedException(array('get'));
    }

    /**
     * @param array $id
     * @throws Rest_Model_NotFoundException, Zend_Acl_Exception
     */
    public function delete(array $id)
    {
        throw new Rest_Model_MethodNotAllowedException(array('get'));
    }

    /**
     * @param array $prop
     * @return array
     * @throws Zend_Acl_Exception
     */
    public function post(array $prop)
    {
        throw new Rest_Model_MethodNotAllowedException(array('get'));
    }

    /**
     * @return PDO
     */
    protected function _getDbHandler()
    {
        if (null === $this->_dbHandler) {
            $config = Zend_Registry::getInstance()->get('config');
            $this->_dbHandler = new PDO('sqlite:' . $config['resources']['db']['params']['dbname']);
        }
        return $this->_dbHandler;
    }

    /**
     * @return Default_Model_Handler_Entry
     */
    protected function _getModelHandler()
    {
        if ($this->_modelHandler === null) {
            $this->_modelHandler = new Default_Model_Handler_Entry();
        }
        return $this->_modelHandler;
    }

    /**
     *
     * @param mixed $entourageSetParam
     * @param array $resourceList
     * @param Rest_Model_EntourageImplementer_Interface
     */
    protected function _entouragePopulate($entourageSetParam, &$resourceList, $resourceHandler)
    {
        // attach to the resource all the entourage resources
        if (empty($entourageSetParam)) {
            throw new Rest_Model_BadRequestException('entourage resources not provided');
        }

        // entourage wasn't passed as an array, get it set up
        if (!is_array($entourageSetParam)) {
            $entourageSetParam = array($entourageSetParam);
        }

        foreach ($entourageSetParam as $name => $entourageParam) {
            // get the entourage set and attach the values to the matching
            // items in the resource list

            // if entourage item wasn't passed as key/value pair, load from value
            if (is_int($name)) {
                $name = $entourageParam;
                $entourageParam = true;
            }

            // if entourage item wasn't expanded then get it from resource
            if (!is_array($entourageParam)) {
                $entourageParam = $resourceHandler->expandEntourageAlias($name);
            }

            // validate the input param
            if (!isset($entourageParam['resourceIdKey'])) {
                throw new Rest_Model_BadRequestException('entourage alias "' . $name . '" does not specify a resourceIdKey');
            }
            if (!isset($entourageParam['entourageIdKey'])) {
                throw new Rest_Model_BadRequestException('entourage alias "' . $name . '" does not specify a entourageIdKey');
            }
            if (!isset($entourageParam['entourageModel'])) {
                throw new Rest_Model_BadRequestException('entourage alias "' . $name . '" does not specify a entourageModel');
            }

            $resourceIdKey = $entourageParam['resourceIdKey'];
            unset($entourageParam['resourceIdKey']); // wont be passed into entourage getList
            $entourageIdKey = $entourageParam['entourageIdKey'];
            unset($entourageParam['entourageIdKey']); // wont be passed into entourage getList
            $entourageModel = $entourageParam['entourageModel'];
            unset($entourageParam['entourageModel']); // wont be passed into entourage getList

            // if specified only return the first match for entourages that match, can make for a
            // cleaner api for using this
            $singleOnly = isset($entourageParam['singleOnly']) && $entourageParam['singleOnly'] ? $entourageParam['singleOnly'] : false;
            unset($entourageParam['singleOnly']); // wont be passed into entourage getList

            $entourageResource = 'Default_Model_AclHandler_' . $entourageModel;

            // see angry comment above about the stupidity of this function
            if (!@class_exists($entourageResource)) {
                throw new Rest_Model_BadRequestException($entourageModel . ' resource for entourage alias "' . $name . '" does not exist');
            }

            $entourageHandler = new $entourageResource($this->getAcl(), $this->getAclContextUser());

            // get only the entourage resources needed for the resource
            $resourceJoinIdList = Util_Array::arrayFromKeyValuesOfSet($resourceIdKey, $resourceList);
            if (empty($resourceJoinIdList)) {
                throw new Rest_Model_BadRequestException('entourage alias "' . $name . '" specifies an invalid resourceIdKey "' . $resourceIdKey . '"');
            }

            $entourageParam['where'] = array_merge(isset($entourageParam['where']) ? $entourageParam['where'] : array(),
                array(
                    // because there could be duplicate ids: array_unique and reindex with array_values
                    $entourageIdKey => array_values(array_unique($resourceJoinIdList))
                )
            );
            $entourageList = $entourageHandler->getList($entourageParam);

            //
            $entourageJoinIdList = Util_Array::arrayFromKeyValuesOfSet($entourageIdKey, $entourageList);
            foreach ($resourceList as &$resource) {
                $joinKeySet = array_keys($entourageJoinIdList, $resource[$resourceIdKey]);

                if ($singleOnly) {
                    $first = current($joinKeySet);
                    $resource[$name] = $first !== false ? $entourageList[$first] : null;
                } else {
                    $resource[$name] = array();
                    foreach ($joinKeySet as $joinKey) {
                        $resource[$name][] = $entourageList[$joinKey];
                    }
                }
            }
        }
    }

}
