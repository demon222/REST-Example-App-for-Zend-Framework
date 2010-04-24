<?php
require_once('Rest/Model/AclHandler/StandardAbstract.php');

abstract class Rest_Model_AclHandler_SimpleTableMapAbstract
    extends Rest_Model_AclHandler_StandardAbstract
{
    /**
     * @var Zend_Db_Table_Abstract
     */
    protected $_dbTable;

    /**
     * @var string
     */
    protected $_resourceName;

    /**
     * @var string
     */
    protected $_defaultListWhere;

    /**
     * @var string
     */
    protected $_defaultListSort;

    /**
     * @var string
     */
    protected $_getListResourceSqlFragment;

    /**
     * @var integer
     */
    protected $_listMaxLength = 100;

    /**
     * @var number
     */
    protected $_listPermissionFilteredRate = 5;

    /**
     * @param array $params
     * @return array
     */
    public function getList(array $params = null)
    {
        $params = is_array($params) ? $params : array();

        if (isset($params['entourage'])) {
            $entourageHandler = new Default_Model_AclHandler_Entourage($this->getAcl(), $this->getAclContextUser());
            $data = $entourageHandler->getList(array($this->_resourceName => $params));
            return $data[$this->_resourceName];
        }

        if (isset($params['where'])) {
            // use default properties to search against if none are provided
            if (!is_array($params['where'])) {
                // no where terms specified, use the defaults at the 'or' level
                $defaultWhereStruct = array();
                foreach ($this->_defaultListWhere as $whereTerm) {
                    $defaultWhereStruct[] = array($whereTerm => $params['where']);
                }
                $params['where'] = array($defaultWhereStruct);
            }
        } else {
            $params['where'] = array();
        }

        if (!isset($params['sort']) || (!is_string($params['sort']) && !is_array($params['sort']))) {
            $params['sort'] = $this->_defaultListSort;
        }
        $params['sort'] = Util_Sql::generateSqlOrderBy($params['sort'], $this->getPropertyKeys());

        // expected that: 0 < limit <= _listMaxLength
        if (!isset($params['limit']) || 0 >= $params['limit'] || $this->_listMaxLength < $params['limit']) {
            $params['limit'] = $this->_listMaxLength;
        }
        $params['limit'] = (integer) $params['limit'];

        // group by
        if (!isset($params['groupBy']) || !in_array($params['groupBy'], $this->getPropertyKeys())) {
            $params['groupBy'] = null;
        }

        // order of elements before group by, determines which row gets used
        // by group by from within the group, the last in the group wins
        if (!isset($params['condenseOn']) || (!is_string($params['condenseOn']) && !is_array($params['condenseOn']))) {
            $params['condenseOn'] = $this->_defaultListSort;
        }
        $params['condenseOn'] = Util_Sql::generateSqlOrderBy($params['condenseOn'], $this->getPropertyKeys());

        // properties is expected to be an array of string property keys or a
        // string of space separated property keys
        //
        // array('id', 'discussion_id', 'comment', 'modified')
        //   - or -
        // 'id discussion_id comment modified'
        {
            if (!isset($params['properties'])) {
                $params['properties'] = $this->getPropertyKeys();
            }
            if (!is_array($params['properties'])) {
                $params['properties'] = explode(' ', $params['properties']);
            }
            $validatedProps = array_intersect($this->getPropertyKeys(), $params['properties']);
            if (count($validatedProps) != count($params['properties'])) {
                throw new Rest_Model_BadRequestException('[' . implode(', ', array_diff($params['properties'], $validatedProps)) . '] are not valid properties for ' . $this->_resourceName);
            }
        }

        // this is an optimization. Imposes a slight overhead performance hit,
        // but for queries where there is very restrictive dependency and a
        // large resource set being scanned, this optimization is very important
        if (0 < count($this->_permissionDependency)) {
            // go through all the dependies and make sure there are WHERE
            // clauses for them
            foreach ($this->_permissionDependency as $depResource => $depAssoc) {
                foreach ($depAssoc as $depId => $resourceId) {
                    // if there is a WHERE already for the resource id
                    // associated with the dependency, then can't optimize further
                    if (in_array($resourceId, array_keys($params['where']))) {
                        continue;
                    }

                    $parentResourceHandler = $this->_createAclHandler($depResource);
                    $list = Util_Array::arrayFromKeyValuesOfSet($depId, $parentResourceHandler->getList(array('limit' => $this->_listMaxLength)));

                    if (0 == count($list)) {
                        // none of the dependencies, done
                        return array();
                    }

                    if ($this->_listMaxLength == count($list)) {
                        // optimization isn't optimal in this situation abort
                        // for dependency association
                        continue;
                    }

                    $params['where'][$resourceId] = $list;
                }
            }
        }

        $whereSqlAndParam = Util_Sql::generateSqlWheresAndParams($params['where'], $this->getPropertyKeys());

        $limit = $params['limit'];
        $dbLimit = floor($limit * $this->_listPermissionFilteredRate);

        $cumulativeRowSet = array();
        $offset = 0;
        do {
            $sql = '';
            // do sub select in order to handle sub ordering in group and get
            // the row we want
            $sql .= (($params['groupBy'] && $params['condenseOn']) ? ' SELECT * FROM (' : '');

            // RESOURCE
            $sql .= $this->_getListResourceSqlFragment();

            $sql .= ''
                // ACL
                . $this->_getGenericAclListJoins()

                // ACL
                . ' WHERE ' . $this->_getGenericAclListWheres()

                // RESOURCE
                . ' AND ' . $whereSqlAndParam['sql']
                . ($params['groupBy'] ? (($params['condenseOn'] ? (' ORDER BY ' . implode(', ', $params['condenseOn']) . ')') : '') . ' GROUP BY ' . $params['groupBy']) : '')
                . ' ORDER BY ' . implode(', ', $params['sort'])
                . ' LIMIT ' . $dbLimit
                . ' OFFSET ' . $offset

                . '';
            $query = $this->_getDbHandler()->prepare($sql);
            $query->execute(array_merge($this->_getGenericAclListParams(), $whereSqlAndParam['param']));
            $rowSet = $query->fetchAll(PDO::FETCH_ASSOC);

            $countUnfiltered = count($rowSet);

            $this->_filterDependenciesNotAllowed($rowSet);

            $cumulativeRowSet += $rowSet;

            $countCumulativeFiltered = count($cumulativeRowSet);

            $offset += $dbLimit;
        } while ($countCumulativeFiltered < $limit && $countUnfiltered == $dbLimit);

        // ensure that only the desired properties are returned
        if (!empty($cumulativeRowSet) && count($params['properties']) != count($cumulativeRowSet[0])) {
            foreach ($cumulativeRowSet as &$row) {
                $row = array_intersect_key($row, array_flip($params['properties']));
            }
        }

        return array_slice($cumulativeRowSet, 0, $limit);
    }

    /**
     * @param array $id
     * @return array
     * @throws Rest_Model_NotFoundException
     */
    protected function _get(array $id)
    {
        $this->_assertValidId($id);

        $this->_getPrePersist($id);

        // set up array for db query
        $queryBy = array();
        foreach ($id as $idProp => $value) {
            $queryBy[$idProp . ' = ?'] = $value;
        }

        $result = $this->_getDbTable()->find($queryBy);

        if (0 == count($result)) {
            throw new Rest_Model_NotFoundException();
        }
        $item = $result->current()->toArray();

        $this->_getPostPersist($item);

        return $item;
    }

    /**
     * @param array $item
     */
    protected function _getPrePersist(array &$id) {}

    /**
     * @param array $item
     */
    protected function _getPostPersist(array &$item) {}

    /**
     * @param array $id
     * @param array $prop
     * @return array
     * @throws Rest_Model_NotFoundException
     */
    public function _put(array $id, array $prop = null)
    {
        $this->_assertValidId($id);

        // if a seperate $prop list is not provided, use the $id list
        if ($prop === null) {
            $prop = $id;
        }

        // ensure only values for know fields are coming in and that identity
        // keys are handled seperately. This disables renaming resources, might
        // be good, not sure yet
        $keys = array_diff($this->getPropertyKeys(), $this->getIdentityKeys());
        $item = array_intersect_key($prop, array_flip($keys));

        $this->_putPrePersist($id, $item);

        // set up array for db query
        $queryBy = array();
        foreach ($id as $idProp => $value) {
            $queryBy[$idProp . ' = ?'] = $value;
        }

        $updated = $this->_getDbTable()->update($item, $queryBy);

        // if it didn't exists, could create the resource at that id... but no
        if ($updated <= 0) {
            throw new Rest_Model_NotFoundException();
        }

        $this->_putPostPersist($item);

        return $item;
    }

    /**
     * @param array $item
     */
    protected function _putPrePersist(array &$id, array &$item) {}

    /**
     * @param array $item
     */
    protected function _putPostPersist(array &$item) {}

    /**
     * @param array $id
     * @throws Rest_Model_NotFoundException
     */
    public function _delete(array $id)
    {
        $this->_assertValidId($id);

        $this->_deletePrePersist($id);

        // set up array for db query
        $queryBy = array();
        foreach ($id as $idProp => $value) {
            $queryBy[$idProp . ' = ?'] = $value;
        }

        $deleted = $this->_getDbTable()->delete($queryBy);

        $this->_deletePostPersist($id);

        if ($deleted == 0) {
            throw new Rest_Model_NotFoundException();
        }
    }

    /**
     * @param array $id
     */
    protected function _deletePrePersist(array &$id) {}

    /**
     * @param array $id
     */
    protected function _deletePostPersist(array $id) {}

    /**
     * @param array $prop
     * @return array
     */
    public function _post(array $prop)
    {
        // ensure only values for know fields are coming in and that identity
        // keys are assigned by any autoincrement in db.
        $keys = array_diff($this->getPropertyKeys(), $this->getIdentityKeys());
        $item = array_intersect_key($prop, array_flip($keys));

        $this->_postPrePersist($item);

        $id = $this->_getDbTable()->insert($item);

        if ($id === null) {
            return Exception('Unable to post into databse, not sure why');
        }

        // mash the id of the posted item into the item data
        // the id will come back as a single value or an array, correct for that
        if (!is_array($id)) {
            // assumes that if a single value then 'id' is the correct property
            $id = array('id' => $id);
        }
        // ok, mashing time
        foreach ($id as $key => $value) {
            $item[$key] = $value;
        }

        $this->_postPostPersist($item);

        return $item;
    }

    /**
     * @param array $item
     */
    protected function _postPrePersist(array &$item) {}

    /**
     * @param array $item
     */
    protected function _postPostPersist(array &$item) {}

    /**
     * Get registered Zend_Db_Table instance, lazy load
     *
     * @return Zend_Db_Table_Abstract
     */
    abstract protected function _getDbTable();

    /**
     * @param array id
     * @throws Exception
     */
    protected function _assertValidId(array $id)
    {
        if (count($this->getIdentityKeys()) != count($id)
            || 0 < count(array_diff($this->getIdentityKeys(), array_keys($id)))
        ) {
            throw new Exception('invalid id property(ies) provided');
        }
    }

    /**
     * @return string
     */
    protected function _getGenericAclListJoins()
    {
        $nl = "\n";

        $staticPermissionsSql = '';
        foreach ($this->_staticPermissions as $role => $permissionSet) {
            foreach($permissionSet as $permission => $privilegeSet) {
                if (!is_array($privilegeSet) || (!in_array('get', $privilegeSet) && 0 != count($privilegeSet))) {
                    continue;
                }
                $staticPermissionsSql .= 'UNION SELECT NULL, "' . $role . '", "' . $permission . '"' . $nl;
            }
        }

        return ''
            . ' LEFT OUTER JOIN (' . $nl
            . '     SELECT COALESCE(rr.resource_id, p.resource_id) AS acl_resource_id, min(p.permission) AS acl_permission' . $nl
            . '     FROM resource_role AS rr' . $nl
            . '     INNER JOIN (' . $nl
            . '         SELECT resource_id, role, permission FROM permission' . $nl
            . '         WHERE resource = :generalResource' . $nl
            . '         AND privilege = "get"' . $nl
            . '         ' . $staticPermissionsSql
            . '     ) AS p ON (rr.role = p.role OR p.role = "default")' . $nl
            . '     WHERE rr.user_id = :userId' . $nl
            . '     AND rr.resource = :generalRoleResource' . $nl
            . '     GROUP BY acl_resource_id' . $nl
            . ' ) AS acl ON acl_resource_id = resource.id' . $nl
            . '';
    }

    /**
     * @return string
     */
    protected function _getGenericAclListWheres()
    {
        return ''
            . ' (acl_permission = "allow"' . ($this->isAllowed('get') ? ' OR acl_permission IS NULL' : '') . ')'
            . '';
    }

    /**
     * @return array
     */
    protected function _getGenericAclListParams()
    {
        // try various things to get the id out of the context user 'object'
        $user = $this->getAclContextUser();

        return array(
            ':userId' => $user['id'],
            ':generalResource' => $this->getResourceId(),
            ':generalRoleResource' => $this->getRoleResourceId(),
        );
    }
}
