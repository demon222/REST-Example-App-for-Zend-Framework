<?php

/**
 * Guestbook controller
 *
 * In this example, we will build a simple guestbook style application. It is 
 * capable only of being "signed" and listing the previous entries.
 * 
 * @uses       Zend_Controller_Action
 * @package    QuickStart
 * @subpackage Controller
 */
class GuestbookController extends Zend_Controller_Action
{
    /**
     * The index, or landing, action will be concerned with listing the entries 
     * that already exist.
     *
     * Assuming the default route and default router, this action is dispatched 
     * via the following urls:
     * - /guestbook/
     * - /guestbook/index
     *
     * @return void
     */
    public function indexAction()
    {
        $guestbook = new Default_Model_Guestbook();
        $this->view->entries = $guestbook->fetchAll();
    }

    /**
     * The sign action is responsible for handling the "signing" of the 
     * guestbook. 
     *
     * Assuming the default route and default router, this action is dispatched 
     * via the following url:
     * - /guestbook/sign
     *
     * @return void
     */
    public function signAction()
    {
        $request = $this->getRequest();
        $form    = new Default_Form_Guestbook();

        // Check to see if this action has been POST'ed to.
        if ($this->getRequest()->isPost()) {
            
            // Now check to see if the form submitted exists, and
            // if the values passed in are valid for this form.
            if ($form->isValid($request->getPost())) {
                
                // Since we now know the form validated, we can now
                // start integrating that data sumitted via the form
                // into our model:
                $model = new Default_Model_Guestbook($form->getValues());
                $model->save();
                
                // Now that we have saved our model, lets url redirect
                // to a new location.
                // This is also considered a "redirect after post";
                // @see http://en.wikipedia.org/wiki/Post/Redirect/Get
                return $this->_helper->redirector('index');
            }
        }
        
        // Assign the form to the view
        $this->view->form = $form;
    }
}
