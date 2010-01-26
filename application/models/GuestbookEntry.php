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
    public function getResourcesTree() {
        return array(
            $this->getAclObjectName() => array(
                'id',
                'email',
                'created',
                'comment',
            ),
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
     * Put the current entry
     *
     * @return Default_Model_GuestbookEntry
     */
    public function put()
    {
        if ($this->getAcl()) {
            if (!$this->getAcl()->isAllowed($this->getAclRole(), $this->getAclObjectName(), 'put')) {
                throw Exception('put for ' . $this->getAclObjectName() . ' is not allowed');
            }
            if ($this->getComment() !== null && !$this->getAcl()->isAllowed($this->getAclRole(), $this->getAclObjectName() . '-comment', 'put')) {
                throw Exception('put for ' . $this->getAclObjectName() . '-comment' . ' is not allowed');
            }
            if ($this->getEmail() !== null && !$this->getAcl()->isAllowed($this->getAclRole(), $this->getAclObjectName() . '-email', 'put')) {
                throw Exception('put for ' . $this->getAclObjectName() . '-email' . ' is not allowed');
            }
            if ($this->getCreated() !== null && !$this->getAcl()->isAllowed($this->getAclRole(), $this->getAclObjectName() . '-created', 'put')) {
                throw Exception('put for ' . $this->getAclObjectName() . '-created' . ' is not allowed');
            }
        }

        $this->getMapper()->put($this);
    }

    /**
     * Post the current entry
     *
     * @return Default_Model_GuestbookEntry
     */
    public function post()
    {
        if ($this->getAcl()) {
            if (!$this->getAcl()->isAllowed($this->getAclRole(), $this->getAclObjectName(), 'post')) {
                throw Exception('post for ' . $this->getAclObjectName() . ' is not allowed');
            }
            if ($this->getComment() !== null && !$this->getAcl()->isAllowed($this->getAclRole(), $this->getAclObjectName() . '-comment', 'post')) {
                throw Exception('post for ' . $this->getAclObjectName() . '-comment' . ' is not allowed');
            }
            if ($this->getEmail() !== null && !$this->getAcl()->isAllowed($this->getAclRole(), $this->getAclObjectName() . '-email', 'post')) {
                throw Exception('post for ' . $this->getAclObjectName() . '-email' . ' is not allowed');
            }
            if ($this->getCreated() !== null && !$this->getAcl()->isAllowed($this->getAclRole(), $this->getAclObjectName() . '-created', 'post')) {
                throw Exception('post for ' . $this->getAclObjectName() . '-created' . ' is not allowed');
            }
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
        if ($this->getAcl()) {
            if (!$this->getAcl()->isAllowed($this->getAclRole(), $this->getAclObjectName(), 'delete')) {
                throw Exception('delete for ' . $this->getAclObjectName() . ' is not allowed');
            }
        }

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
    public function get()
    {
        $this->getMapper()->get($this);
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
