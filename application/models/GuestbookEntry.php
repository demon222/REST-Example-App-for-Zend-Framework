<?php

require_once('Rest/Model/Abstract.php');
require_once('Rest/Model/NotFoundException.php');

/**
 * GuestbookEntry model
 *
 * Utilizes the Data Mapper pattern to persist data. Represents a single
 * guestbook entry.
 *
 * @uses       Default_Model_GuestbookMapper
 * @package    QuickStart
 * @subpackage Model
 */
class Default_Model_GuestbookEntry extends Rest_Model_Abstract
{
    /**
     * @var string
     */
    protected $_comment;

    /**
     * @var string
     */
    protected $_created;

    /**
     * @var string
     */
    protected $_email;

    /**
     * @var int
     */
    protected $_id;

    /**
     * @var Default_Model_GuestbookEntryMapper
     */
    protected $_mapper;

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'id' => $this->getId(),
            'email' => $this->getEmail(),
            'created' => $this->getCreated(),
            'comment' => $this->getComment(),
        );
    }

    /**
     * @return array
     */
    public function getIdentityKeys()
    {
        return array('id');
    }

    /**
     * Set comment
     *
     * @param  string $text
     * @return Default_Model_Guestbook
     */
    public function setComment($text)
    {
        $this->_comment = (string) $text;
        return $this;
    }

    /**
     * Get comment
     *
     * @return null|string
     */
    public function getComment()
    {
        return $this->_comment;
    }

    /**
     * Set email
     *
     * @param  string $email
     * @return Default_Model_Guestbook
     */
    public function setEmail($email)
    {
        $this->_email = (string) $email;
        return $this;
    }

    /**
     * Get email
     *
     * @return null|string
     */
    public function getEmail()
    {
        return $this->_email;
    }

    /**
     * Set created timestamp
     *
     * @param  string $ts
     * @return Default_Model_Guestbook
     */
    public function setCreated($ts)
    {
        $this->_created = $ts;
        return $this;
    }

    /**
     * Get entry timestamp
     *
     * @return string
     */
    public function getCreated()
    {
        return $this->_created;
    }

    /**
     * Set entry id
     *
     * @param  int $id
     * @return Default_Model_Guestbook
     */
    public function setId($id)
    {
        $this->_id = (int) $id;
        return $this;
    }

    /**
     * Retrieve entry id
     *
     * @return null|int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Set data mapper
     *
     * @param  mixed $mapper
     * @return Default_Model_GuestbookEntry
     */
    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    /**
     * Get data mapper
     *
     * Lazy loads Default_Model_GuestbookEntryMapper instance if no mapper registered.
     *
     * @return Default_Model_GuestbookEntryMapper
     */
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Default_Model_GuestbookEntryMapper());
        }
        return $this->_mapper;
    }

    /**
     * Put the current entry
     *
     * @return Default_Model_GuestbookEntry
     */
    public function put()
    {
        if ($this->getAcl() && !$this->isAllowed('put')) {
            throw Zend_Acl_Exception('put for ' . $this->getResourceId() . ' is not allowed');
        }

        $resourceFound = $this->getMapper()->put($this);

        if (!$resourceFound) {
            throw new Rest_Model_NotFoundException();
        }
    }

    /**
     * Post the current entry
     *
     * @return Default_Model_GuestbookEntry
     */
    public function post()
    {
        if ($this->getAcl() && !$this->isAllowed('post')) {
            throw Zend_Acl_Exception('post for ' . $this->getResourceId() . ' is not allowed');
        }

        $this->getMapper()->post($this);
    }

    /**
     * Delete the current entry
     *
     * @return void
     */
    public function delete()
    {
        if ($this->getAcl() && !$this->isAllowed('delete')) {
            throw Zend_Acl_Exception('delete for ' . $this->getResourceId() . ' is not allowed');
        }

        $resourceFound = $this->getMapper()->delete($this);
        
        if (!$resourceFound) {
            throw new Rest_Model_NotFoundException();
        }
    }

    /**
     * Find an entry
     *
     * Resets entry state if matching id found.
     *
     * @param  int $id
     * @return Default_Model_GuestbookEntry
     */
    public function get()
    {
        if ($this->getAcl() && !$this->isAllowed('get')) {
            throw Zend_Acl_Exception('get for ' . $this->getResourceId() . ' is not allowed');
        }

        $resourceFound = $this->getMapper()->get($this);

        if (!$resourceFound) {
            throw new Rest_Model_NotFoundException();
        }

        return $this;
    }

    /**
     * Fetch all entries
     *
     * @return array
     */
    public function fetchAll()
    {
        return $this->getMapper()->fetchAll();
    }

    /**
     * Overloading: allow property access
     *
     * @param  string $name
     * @param  mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        if (in_array($name, array('mapper'))) {
            throw Exception('Invalid property "' . $name . '" specified');
        }
        parent::__set($name, $value);
    }

    /**
     * Overloading: allow property access
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (in_array($name, array('mapper'))) {
            throw Exception('Invalid property "' . $name . '" specified');
        }
        return parent::__get($name);
    }
}
