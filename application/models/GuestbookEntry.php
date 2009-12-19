<?php

require_once('Rest/Model/Abstract.php');

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

    public function toArray()
    {
        return array(
            '_id' => $this->getId(),
            'email' => $this->getEmail(),
            'created' => $this->getCreated(),
            'comment' => $this->getComment(),
        );
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
     * Save the current entry
     * 
     * @return void
     */
    public function save()
    {
        $this->getMapper()->save($this);
    }

    /**
     * Delete the current entry
     *
     * @return void
     */
    public function delete()
    {
        $this->getMapper()->delete($this);
    }

    /**
     * Find an entry
     *
     * Resets entry state if matching id found.
     * 
     * @param  int $id 
     * @return Default_Model_GuestbookEntry
     */
    public function find($id)
    {
        $this->getMapper()->find($id, $this);
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
        if ($name == 'mapper') {
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
        if ($name == 'mapper') {
            throw Exception('Invalid property "' . $name . '" specified');
        }
        return parent::__get($name);
    }

}
