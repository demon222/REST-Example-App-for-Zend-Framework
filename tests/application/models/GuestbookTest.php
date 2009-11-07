<?php

require_once APPLICATION_PATH . '/models/GuestbookMapper.php';

/**
 * @group models
 */
class Model_GuestbookTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        include APPLICATION_PATH . '/../scripts/load.sqlite.php';
        $application = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
        $bootstrap   = $application->getBootstrap();
        $bootstrap->bootstrap('autoload');
        $bootstrap->bootstrap('db');

        $this->guestbook = new Model_Guestbook();
    }

    public function testIdIsNullByDefault()
    {
        $this->assertNull($this->guestbook->getId());
    }

    public function testEmailIsNullByDefault()
    {
        $this->assertNull($this->guestbook->getEmail());
    }

    public function testCommentIsNullByDefault()
    {
        $this->assertNull($this->guestbook->getComment());
    }

    public function testCreatedIsNullByDefault()
    {
        $this->assertNull($this->guestbook->getCreated());
    }

    public function testIdIsMutable()
    {
        $this->guestbook->setId(3);
        $this->assertEquals(3, $this->guestbook->getId());
    }

    public function testEmailIsMutable()
    {
        $this->guestbook->setEmail('zend@example.com');
        $this->assertEquals('zend@example.com', $this->guestbook->getEmail());
    }

    public function testCommentIsMutable()
    {
        $this->guestbook->setComment('testing');
        $this->assertEquals('testing', $this->guestbook->getComment());
    }

    public function testCreatedIsMutable()
    {
        $this->guestbook->setCreated('2009-04-24 15:47:00');
        $this->assertEquals('2009-04-24 15:47:00', $this->guestbook->getCreated());
    }

    public function testMapperLazyLoads()
    {
        $mapper = $this->guestbook->getMapper();
        $this->assertTrue($mapper instanceof Model_GuestbookMapper);
    }

    public function testMapperIsMutable()
    {
        $mapper = new Model_GuestbookMapper();
        $this->guestbook->setMapper($mapper);
        $test = $this->guestbook->getMapper();
        $this->assertSame($mapper, $test);
    }

    public function testSaveMutatesMapper()
    {
        $this->guestbook->setOptions(array(
            'id'      => 1,
            'email'   => 'zend@example.com',
            'comment' => 'foo',
            'mapper'  => new GuestbookTest_Mapper(),
        ));
        $this->guestbook->save();
        $this->assertTrue($this->guestbook->getMapper()->mutated);
    }

    public function testFindSetsGuestbookState()
    {
        $this->guestbook->setOptions(array(
            'id'      => 1,
            'email'   => 'zend@example.com',
            'comment' => 'foo',
            'mapper'  => new GuestbookTest_Mapper(),
        ));
        $this->guestbook->find(2);
        $this->assertEquals(2, $this->guestbook->getId());
    }

    public function testFetchAllReturnsArrayOfGuestbooks()
    {
        $this->guestbook->setOptions(array(
            'id'      => 1,
            'email'   => 'zend@example.com',
            'comment' => 'foo',
            'mapper'  => new GuestbookTest_Mapper(),
        ));
        $entries = $this->guestbook->fetchAll();

        $this->assertTrue(count($entries) > 0);

        foreach ($entries as $entry) {
            $this->assertTrue($entry instanceof Model_Guestbook);
        }
    }
}

class GuestbookTest_Mapper extends Model_GuestbookMapper
{
    public $mutated = false;

    public function save(Model_Guestbook $guestbook)
    {
        $this->mutated = true;
    }

    public function find($id, Model_Guestbook $guestbook)
    {
        $guestbook->setId($id);
    }

    public function fetchAll()
    {
        $entries = array();
        for ($i = 1; $i < 11; $i++) {
            $entry = new Model_Guestbook();
            $entry->setId($i)
                  ->setEmail('zend@example.com')
                  ->setComment('testing')
                  ->setCreated('2009-04-24 15:54:00')
                  ->setMapper($this);
            $entries[] = $entry;
        }
        return $entries;
    }
}
