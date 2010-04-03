<?php

/*
 * Aiming to populate, community, discussion, entry and user with all forums
 * from Zend Framework's Forums
 */
require_once('simple_html_dom.php');

class Scraper
{
    /**
     * @var PDO
     */
    private $_dbCon;

    /**
     * @var string
     */
    private $_communityIndex;

    function __construct($dbCon, $url)
    {
        $this->_dbCon = $dbCon;
        $this->_feedIndex = $url;

        $statement = $this->_dbCon->prepare('SELECT * FROM entry ORDER BY modified DESC LIMIT 1');
        $statement->execute();
        $mostRecentEntry = $statement->fetch();
        $this->_lastCheck = $mostRecentEntry ? $mostRecentEntry['modified'] : null;

    }

    public function start()
    {
        $this->scrapeFeedIndex($this->_feedIndex);
    }

    public function scrapeFeedIndex($url)
    {
        $this->reportHttpActivity($url);
        $html = file_get_html($url);

        if (null === $html->find('.page', 0)) {
            $this->scrapeFeed($html);
            return;
        }

        $siblingPageUrl = $html->find('.page a', 0)->href;
        $html->clear();
        do {
            $this->reportHttpActivity($siblingPageUrl);
            $html = file_get_html($siblingPageUrl);

            $entryDate = $html->find('.main-row', 0)->find('.avatar-table script text', -1)->plaintext;
            // Nabble hold the date in a timestamp of milliseconds. Because
            // this number is exceeding MAX_INT I'm cutting off the last 3
            // digits and only having a precision of seconds.
            $entryDate = preg_replace('/[^0-9]*([0-9]*)[0-9]{3}[^0-9]*/', '\\1', $entryDate);
            $entryDate = date('Y-m-d H:i:s', (integer) $entryDate);

            if ($this->_lastCheck > $entryDate) {
                break;
            }
            echo 'going back to ' . $this->_lastCheck . ', currently at ' . $entryDate . PHP_EOL;

            $sibling = $html->find('.current-page', 0)->next_sibling();

            if (null === $sibling) {
                break;
            }

            // to speed things up in the rewind process going to try and jump some pages
            $sibling = (null !== $sibling->next_sibling()->find('a', 0)) ? $sibling->next_sibling() : $sibling;
            $sibling = (null !== $sibling->next_sibling()->find('a', 0)) ? $sibling->next_sibling() : $sibling;

            $siblingPageUrl = $sibling->find('a', 0)->href;
            $html->clear();
        } while (true);

        do {
            $this->reportHttpActivity($siblingPageUrl);
            $html = file_get_html($siblingPageUrl);

            $entryDate = $html->find('.main-row', 0)->find('.avatar-table script text', -1)->plaintext;
            $entryDate = preg_replace('/[^0-9]*([0-9]*)[0-9]{3}[^0-9]*/', '\\1', $entryDate);
            $entryDate = date('Y-m-d H:i:s', (integer) $entryDate);

            echo $entryDate . PHP_EOL;
            if ($this->_lastCheck <= $entryDate) {
                $this->scrapeFeed($html);
            }

            $sibling = $html->find('.current-page', 0)->prev_sibling();

            if (null === $sibling) {
                break;
            }
            $siblingPageUrl = $sibling->find('a', 0)->href;
            $html->clear();
        } while (true);
    }

    public function scrapeFeed($url)
    {
        if ($url instanceof simple_html_dom_node || $url instanceof simple_html_dom) {
            $html = $url;
        } else {
            $this->reportHttpActivity($url);
            $html = file_get_html($url);
        }

        // check the first one on the page, if already in DB, go to next page
        $discussionList = array();
        foreach ($html->find('.main-row') as $dTag) {
            $discussionList[] = $dTag;
        }
        $discussionList = array_reverse($discussionList);
        foreach ($discussionList as $dTag) {
            $dlTag = $dTag->find('td[style=width:100%;padding-left:.3em;border:none] a', 0);
            $discussionTitle = trim($dlTag->plaintext);
            $discussionUrl = $dlTag->href;
            $entryDate = $dTag->find('.avatar-table script text', -1)->plaintext;
            $entryDate = preg_replace('/[^0-9]*([0-9]*)[0-9]{3}[^0-9]*/', '\\1', $entryDate);
            $entryDate = date('Y-m-d H:i:s', (integer) $entryDate);
            $communityTitle = trim($dTag->find('a text', -1)->plaintext);
            $userUsername = trim($dTag->find('td[style=width:100%;padding-left:.3em;border:none] text', -1)->plaintext);

            $communityId = $this->getCommunityId($communityTitle);

            $discussionId = $this->getDiscussionId($communityId, $discussionTitle);

            if ($this->_lastCheck <= $entryDate) {
                $this->scrapeDiscussionIndex($discussionUrl, $communityId, $discussionId);
            }
        }
    }

    public function scrapeDiscussionIndex($url, $communityId = null, $discussionId = null)
    {
        $this->reportHttpActivity($url);
        $html = file_get_html($url);

        if (null === $html->find('.page', 0)) {
            $this->scrapeDiscussion($html, $communityId, $discussionId);
            return;
        }

        $siblingPageUrl = $html->find('.page a', 0)->href;
        $html->clear();
        do {
            $this->reportHttpActivity($siblingPageUrl);
            $html = file_get_html($siblingPageUrl);

            $this->scrapeDiscussion($html, $communityId, $discussionId);

            $sibling = $html->find('.current-page', 0)->next_sibling();

            if (null === $sibling) {
                break;
            }
            $siblingPageUrl = $sibling->find('a', 0)->href;
            $html->clear();
        } while (true);
    }

    public function scrapeDiscussion($url, $communityId = null, $discussionId = null)
    {
        if ($url instanceof simple_html_dom_node || $url instanceof simple_html_dom) {
            $html = $url;
        } else {
            $this->reportHttpActivity($url);
            $html = file_get_html($url);
        }

        // check the first one on the page, if already in DB, go to next page
        $communityTitle = trim($html->find('.breadcrumbs a text', -1)->plaintext);
        $discussionTitle = trim($html->find('h1 text', 0)->plaintext);

        if (null === $communityId) {
            $communityId = $this->getCommunityId($communityTitle);
        }

        if (null === $discussionId) {
            $discussionId = $this->getDiscussionId($communityId, $discussionTitle);
        }

        foreach ($html->find('.ul-threaded .classic-table') as $entry) {
            $userUsername = trim($entry->find('.column-left .author-link a text', 0)->plaintext);
            $entryDate = $entry->find('.post-date script text', 0)->plaintext;
            $entryDate = preg_replace('/[^0-9]*([0-9]*)[0-9]{3}[^0-9]*/', '\\1', $entryDate);
            $entryDate = date('Y-m-d H:i:s', (integer) $entryDate);
            $entryComment = $entry->find('.message-text', 0)->innertext();
            $entryComment = trim(preg_replace('/<script.*?<\\/script>/s', '', $entryComment));

            $userId = $this->getUserId($userUsername);

            $entryId = $this->getEntryId($discussionId, $entryComment, $userId, $entryDate);
        }
    }

    /**
     * @param string $username
     * @return integer
     */
    public function getCommunityId($title)
    {
        // get user id
        $statement = $this->_dbCon->prepare('SELECT * FROM community WHERE title = ?');
        $statement->execute(array($title));
        $resource = $statement->fetch();
        if (false === $resource) {
            $insertStatement = $this->_dbCon->prepare(
                'INSERT INTO community (title) VALUES (?)'
            );
            $insertStatement->execute(array($title));
            $id = $this->_dbCon->lastInsertId();
        } else {
            $id = $resource['id'];
        }
        return $id;
    }

    /**
     * @param string $title
     * @return integer
     */
    public function getDiscussionId($communityId, $title)
    {
        // get user id
        $statement = $this->_dbCon->prepare('SELECT * FROM discussion WHERE community_id = ? AND title = ?');
        $statement->execute(array($communityId, $title));
        $resource = $statement->fetch();
        if (false === $resource) {
            $insertStatement = $this->_dbCon->prepare(
                'INSERT INTO discussion (community_id, title) VALUES (?, ?)'
            );
            $insertStatement->execute(array($communityId, $title));
            $id = $this->_dbCon->lastInsertId();
        } else {
            $id = $resource['id'];
        }
        return $id;
    }

    /**
     * @param string $username
     * @return integer
     */
    public function getUserId($username)
    {
        // get user id
        $statement = $this->_dbCon->prepare('SELECT * FROM user WHERE username = ?');
        $statement->execute(array($username));
        $resource = $statement->fetch();
        if (false === $resource) {
            $insertStatement = $this->_dbCon->prepare(
                'INSERT INTO user (username) VALUES (?)'
            );
            $insertStatement->execute(array($username));
            $id = $this->_dbCon->lastInsertId();
        } else {
            $id = $resource['id'];
        }
        return $id;
    }

    public function getEntryId($discussionId, $comment, $creatorUserId, $modified)
    {
        // get user id
        $statement = $this->_dbCon->prepare('SELECT * FROM entry WHERE discussion_id = ? AND comment = ? AND creator_user_id = ? AND modified = ?');
        $statement->execute(array($discussionId, $comment, $creatorUserId, $modified));
        $resource = $statement->fetch();
        if (false === $resource) {
            $insertStatement = $this->_dbCon->prepare(
                'INSERT INTO entry (discussion_id, comment, creator_user_id, modified) VALUES (?, ?, ?, ?)'
            );
            $insertStatement->execute(array($discussionId, $comment, $creatorUserId, $modified));
            $id = $this->_dbCon->lastInsertId();
        } else {
            $id = $resource['id'];
        }
        return $id;
    }

    public function reportHttpActivity($url)
    {
        usleep(1000000);
        echo $url . PHP_EOL;
    }
}

date_default_timezone_set('America/New_York');

// Initialize the application and bootstrap the database adapter
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', 'development');
require_once 'Zend/Application.php';
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);
$bootstrap = $application->getBootstrap();
$bootstrap->bootstrap('db');
$dbAdapter = $bootstrap->getResource('db');

$scraper = new Scraper($dbAdapter->getConnection(), 'http://n4.nabble.com/Zend-Framework-Community-f634137.html');
$scraper->getUserId('Alex');

$scraper->start();

// generally speaking, this script will be run from the command line
return true;
