<?php
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
     * @param array $params
     * @return array
     */
    public function getList(array $params = null)
    {
        // validate that the needed parameters have been passed

        $params = array(
            'User' => array(
                'entourage' => array(
                    'Entry' => array(
                        'resourceIdKey' => 'id',
                        'entourageIdKey' => 'creator_user_id',
                        'representAs' => 'Entry',
                    ),
                ),
            ),
            'Entry' => array(
                'entourage' => array(
                    'User' => array(
                        'resourceIdKey' => 'creator_user_id',
                        'entourageIdKey' => 'id',
                        'representAs' => 'Creator',
                        'singleOnly' => true,
                    ),
                ),
            ),
        );
        if (!is_array($params)) {
            throw new Rest_Model_BadRequestException('must provide a set of source resources for attaching entourage resources');
        }

        $data = array();

        foreach($params as $rName => $resourceParam) {
            $resourceName = 'Default_Model_AclHandler_' . $rName;

            // need to supress warnings that class_exists produces if the class
            // doesn't exist. Incredibly stupid design that this function
            // produces warning when being used as designed. Not sure if these
            // supressed warnings are showing up in some log. Stupid, stupid.
            // try/catch attempts around it don't help.
            if (!@class_exists($resourceName)) {
                throw new Rest_Model_BadRequestException('resource "' . $rName . '" does not exist');
            }

            $resourceHandler = new $resourceName($this->getAcl(), $this->getAclContextUser());

            $entourageSetParam = isset($resourceParam['entourage']) ? $resourceParam['entourage'] : null;

            // get the resource list
            unset($resourceParam['entourage']);
            $resourceList = $resourceHandler->getList($resourceParam);

            if (null !== $entourageSetParam) {
                $this->_entouragePopulate($entourageSetParam, $resourceList);
            }

            $data[$rName] = $resourceList;
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
        if (null === $params) {
            throw new Rest_Model_BadRequestException('must provide a source resource for attaching entourage resources');
        }

        // take just the first param, any additional ones will be ignored
        list($rName, $resourceParam) = each($params);

        // create the full resource name
        $resourceName = 'Default_Model_AclHandler_' . $rName;

        // see angry comment above about the stupidity of this function
        if (!@class_exists($resourceName)) {
            throw new Rest_Model_BadRequestException('resource "' . $rName . '" does not exist');
        }

        $resourceHandler = new $resourceName($this->getAcl(), $this->getAclContextUser());

        $entourageSetParam = isset($resourceParam['entourage']) ? $resourceParam['entourage'] : null;

        // get the resource
        $resource = $resourceHandler->get($id);

        // attach an entourage resources that are specified
        if (null !== $entourageSetParam) {
            $resourceList = array($resource);
            $this->_entouragePopulate($entourageSetParam, $resourceList);
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
     * @param array $entourageSetParam
     * @param array $resourceList
     */
    protected function _entouragePopulate(array $entourageSetParam, &$resourceList)
    {
        // attach to the resource all the entourage resources
        if (!is_array($entourageSetParam)) {
            throw new Rest_Model_BadRequestException('entourage resources not provided');
        }

        foreach ($entourageSetParam as $eName => $entourageParam) {
            // get the entourage set and attach the values to the matching
            // items in the resource list

            $entourageName = 'Default_Model_AclHandler_' . $eName;

            // see angry comment above about the stupidity of this function
            if (!@class_exists($entourageName)) {
                throw new Rest_Model_BadRequestException('entourage resource "' . $eName . '" does not exist');
            }

            $entourageHandler = new $entourageName($this->getAcl(), $this->getAclContextUser());

            // validate the input param
            if (!isset($entourageParam['resourceIdKey'])) {
                throw new Rest_Model_BadRequestException('entourage resource "' . $eName . '" does not specify a resourceIdKey (for "' . $rName . '")');
            }
            if (!isset($entourageParam['entourageIdKey'])) {
                throw new Rest_Model_BadRequestException('entourage resource "' . $eName . '" does not specify a entourageIdKey (for "' . $rName . '")');
            }
            if (!isset($entourageParam['representAs'])) {
                throw new Rest_Model_BadRequestException('entourage resource "' . $eName . '" does not specify a representAs (for "' . $rName . '")');
            }

            // get only the entourage resources needed for the resource
            $resourceJoinIdList = Util_Array::arrayFromKeyValuesOfSet($entourageParam['resourceIdKey'], $resourceList);
            $entourageList = $entourageHandler->getList(array(
                'where' => array(
                    // because the could be duplicate ids: array_unique and reindex with array_values
                    $entourageParam['entourageIdKey'] => array_values(array_unique($resourceJoinIdList))
                )
            ));

            // if specified only return the first match for entourages that match, can make for a
            // cleaner api for using this
            $singleOnly = isset($entourageParam['singleOnly']) && $entourageParam['singleOnly'] ? $entourageParam['singleOnly'] : false;

            //
            $entourageJoinIdList = Util_Array::arrayFromKeyValuesOfSet($entourageParam['entourageIdKey'], $entourageList);
            foreach ($resourceList as &$resource) {
                $joinKeySet = array_keys($entourageJoinIdList, $resource[$entourageParam['resourceIdKey']]);

                if ($singleOnly) {
                    $first = current($joinKeySet);
                    $resource[$entourageParam['representAs']] = $first !== false ? $entourageList[$first] : null;
                } else {
                    $resource[$entourageParam['representAs']] = array();
                    foreach ($joinKeySet as $joinKey) {
                        $resource[$entourageParam['representAs']][] = $entourageList[$joinKey];
                    }
                }
            }
        }
    }

}
