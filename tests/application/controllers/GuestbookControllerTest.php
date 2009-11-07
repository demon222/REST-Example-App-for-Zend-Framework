<?php

/**
 * @group controllers
 */
class GuestbookControllerTest extends Zend_Test_PHPUnit_ControllerTestCase
{

    public function setUp()
    {
        include APPLICATION_PATH . '/../scripts/load.sqlite.php';

        $application = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
        $this->bootstrap = array($application->getBootstrap(), 'bootstrap');
        return parent::setUp();
    }

    public function tearDown()
    {
        /* Tear Down Routine */
    }

    public function testIndexPageShouldListEntries()
    {
        $this->dispatch('/guestbook');
        $this->assertQueryCountMin('dl dt', 1);
    }

    public function testGetRequestToSignPageShouldPresentForm()
    {
        $this->dispatch('/guestbook/sign');
        $this->assertQuery('form');
    }

    public function testPostingRequestWithInvalidFormShouldRePresentForm()
    {
        $this->request->setMethod('post');
        $this->dispatch('/guestbook/sign');
        $this->assertQuery('form');
    }

    public function testPostingValidFormShouldResultInRedirectToIndexPage()
    {
        $this->dispatch('/guestbook/sign');
        $content = $this->response->getBody();
        if (!preg_match('/name="csrf" value="([^"]*)"/s', $content, $matches)) {
            $this->fail('Form is missing CSRF protection');
        }
        $csrf = $matches[1];
        $data = array(
            'email'   => 'zend@example.com',
            'comment' => 'Test comment from test suite',
            'csrf'    => $csrf,
        );
        $this->resetResponse();
        $this->request->setMethod('post')
                      ->setPost($data);
        $this->dispatch('/guestbook/sign');
        $this->assertRedirectTo('/guestbook');
    }
}
