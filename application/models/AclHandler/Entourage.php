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
     * @var PDO
     */
    protected $_dbHandler;

    protected function _initAclRules()
    {
        $acl = $this->getAcl();

        if (!$acl->has($this)) {
            $acl->addResource($this);
        }

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
     *       'DiscussionWithCommunity' => array(
     *           'entourage' => array(
     *               'entourageName' => 'Discussion',
     *               'entourageModel' => 'Discussion',
     *               'entourageIdKey' => 'id',
     *               'resourceIdKey' => 'discussion_id',
     *               'singleOnly' => true,
     *               'entourage' => 'Community',
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

            $resourceHandler = $this->_createAclHandler($name);

            $entourageSetParam = isset($resourceParam['entourage']) ? $resourceParam['entourage'] : null;

            unset($resourceParam['entourage']);

            // get the resource list
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
            $resourceHandler = $this->_createAclHandler($resourceHandler);
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
        throw new Rest_Model_MethodNotAllowedException('put', array('get'));
    }

    /**
     * @param array $id
     * @throws Rest_Model_NotFoundException, Zend_Acl_Exception
     */
    public function delete(array $id)
    {
        throw new Rest_Model_MethodNotAllowedException('delete', array('get'));
    }

    /**
     * @param array $prop
     * @return array
     * @throws Zend_Acl_Exception
     */
    public function post(array $prop)
    {
        throw new Rest_Model_MethodNotAllowedException('post', array('get'));
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

        if (empty($resourceList)) {
            return;
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
                if (null === $entourageParam) {
                    throw new Rest_Model_BadRequestException('entourage alias "' . $name . '" is not known');
                }
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

            // use entourage name if specified otherwise use the alias name
            $entourageName = isset($entourageParam['entourageName']) ? $entourageParam['entourageName'] : $name;
            unset($entourageParam['entourageName']); // wont be passed into entourage getList

            // if specified only return the first match for entourages that match, can make for a
            // cleaner api for some use cases
            $singleOnly = isset($entourageParam['singleOnly']) && $entourageParam['singleOnly'] && $entourageParam['singleOnly'] !== 'false' ? $entourageParam['singleOnly'] : false;
            unset($entourageParam['singleOnly']); // wont be passed into entourage getList

            $entourageHandler = $this->_createAclHandler($entourageModel);

            // get only the entourage resources needed for the resource
            $resourceJoinIdList = Util_Array::arrayFromKeyValuesOfSet($resourceIdKey, $resourceList);
            if (empty($resourceJoinIdList)) {
                throw new Rest_Model_BadRequestException('entourage alias "' . $name . '" specifies an invalid resourceIdKey "' . $resourceIdKey . '"');
            }

            // default, assume that the sub results will be more than the limit
            $resultWillBeLessThanLimit = false;

            if ($singleOnly && empty($entourageParam['groupBy'])) {
                $entourageParam['groupBy'] = $entourageIdKey;
                $resultWillBeLessThanLimit = true;
            }

            // TODO: figure out nature of sort versus condenseOn and if
            // condenseOn should be presented to user, either way some
            // checks and better implementation of condenseOn is needed
            if ($singleOnly && isset($entourageParam['sort']) && $entourageParam['sort']) {
                $entourageParam['condenseOn'] = $entourageParam['sort'];
            }

            if (empty($entourageParam['where'])) {
                $entourageParam['where'] = array();
            }

            // load the entourage items
            if ($resultWillBeLessThanLimit) {
                $entourageParam['where'] = array_merge($entourageParam['where'],
                    array(
                        // because there could be duplicate ids: array_unique and reindex with array_values
                        $entourageIdKey => array_values(array_unique($resourceJoinIdList))
                    )
                );

                $entourageList = $entourageHandler->getList($entourageParam);
            } else {
                $entourageList = array();
                foreach (array_values(array_unique($resourceJoinIdList)) as $resourceId) {
                    $entourageParam['where'][$entourageIdKey] = $resourceId;
                    $entourageList = array_merge($entourageList, $entourageHandler->getList($entourageParam));
                }
            }

            // attach the entourage items to the resource
            $entourageJoinIdList = Util_Array::arrayFromKeyValuesOfSet($entourageIdKey, $entourageList);
            foreach ($resourceList as &$resource) {
                $joinKeySet = array_keys($entourageJoinIdList, $resource[$resourceIdKey]);

                if ($singleOnly) {
                    $first = current($joinKeySet);
                    $resource[$entourageName] = $first !== false ? $entourageList[$first] : null;
                } else {
                    $resource[$entourageName] = array();
                    foreach ($joinKeySet as $joinKey) {
                        $resource[$entourageName][] = $entourageList[$joinKey];
                    }
                }
            }
        }
    }
}
