<?php

/*
 * Introduces cancel action functionality. Allows predispatch determine that
 * the action doesn't need to be called and continues to execute postdispatch
 */
abstract class ZendPatch_Controller_Action extends Zend_Controller_Action
{

    // controls action execution, see dispatch method for more details
    protected $_cancelAction = false;

    /**
     * Controls execution of the intended dispatched action
     *
     * @param boolean $value
     * @return Rest_Controller_Action_Abstract
     */
    public function setCancelAction($value)
    {
        $this->_cancelAction = $value;
        return $this;
    }

    /**
     * Overwritten dispatch inorder to introduce cancel action functionality.
     *
     * Dispatch the requested action
     *
     * @param string $action Method name of action
     * @return void
     */
    public function dispatch($action)
    {
        // Notify helpers of action preDispatch state
        $this->_helper->notifyPreDispatch();

        $this->preDispatch();
        if ($this->getRequest()->isDispatched()) {
            if (null === $this->_classMethods) {
                $this->_classMethods = get_class_methods($this);
            }

            // THE FOLLOWING IF CONDITION HAS BEEN INTRODUCED FOR CLEAN USAGE
            // OF REST IN PRE AND POST DISPATCH, OTHERWISE DISPATCH IS SAME TO
            // Zend_Controller_ 'Action.php 16541 2009-07-07 06:59:03Z bkarwin'
            if (!$this->_cancelAction) {
                // preDispatch() didn't change the action, so we can continue
                if ($this->getInvokeArg('useCaseSensitiveActions') || in_array($action, $this->_classMethods)) {
                    if ($this->getInvokeArg('useCaseSensitiveActions')) {
                        trigger_error('Using case sensitive actions without word separators is deprecated; please do not rely on this "feature"');
                    }
                    $this->$action();
                } else {
                    $this->__call($action, array());
                }
            }
            $this->postDispatch();
        }

        // whats actually important here is that this action controller is
        // shutting down, regardless of dispatching; notify the helpers of this
        // state
        $this->_helper->notifyPostDispatch();
    }
}
