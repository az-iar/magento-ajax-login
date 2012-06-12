<?php
class Hasrimy_Ilogin_IndexController extends Mage_Core_Controller_Front_Action {
	
	public function indexAction (){
		if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $this->_redirect('customer/account');
            return;
        }
		$this->loadLayout();
        $this->renderLayout();
	}
 
	public function dologinAction (){
	
		$session = $this->_getSession();
		$customer = $this->_getCustomerSession();
		
		if ($customer->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }
		
		if (Mage::app()->getRequest()->isPost()) {
            $login = Mage::app()->getRequest()->getPost();
            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $customer->login($login['username'], $login['password']);
					/*
                    if ($session->getCustomer()->getIsJustConfirmed()) {
                        $this->_welcomeCustomer($session->getCustomer(), true);
                    }
					*/
                } catch (Mage_Core_Exception $e) {
                    switch ($e->getCode()) {
                        case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                            $value = Mage::helper('customer')->getEmailConfirmationUrl($login['username']);
                            $message = Mage::helper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
                            break;
                        case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                            $message = $e->getMessage();
                            break;
                        default:
                            $message = $e->getMessage();
                    }
                    $session->addError($message);
                    $session->setUsername($login['username']);
                } catch (Exception $e) {
                    // Mage::logException($e); // PA DSS violation: this exception log can disclose customer password
                }
            } else {
                $session->addError($this->__('Login and password are required.'));
            }
        }
		
		$this->_redirect('ilogin');
    }
	
	public function registerAction(){
	
	}
	
	public function doregisterAction(){
	
		$session = $this->_getSession();
		$customerSession = $this->_getCustomerSession();
		
		if ($customerSession->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }
		
		if (Mage::app()->getRequest()->isPost()) {
            $input = Mage::app()->getRequest()->getPost();
						
            if (!empty($input['email']) && !empty($input['password'])) {
				$errors = array();
				
				//check password length
				if( trim(strlen($input['password'])) < 6 ){
					$session->addError($this->__('Password must be at least 6 characters in length.'));
					$errors[] = 'Password must be at least 6 characters in length.';
				}
				
				try {
					if (!$customer = Mage::registry('current_customer')) {
						$customer = Mage::getModel('customer/customer');
					}
					$customer	->setId(null)
					->setSkipConfirmationIfEmail($input['email'])
					->setFirstname('Firstname')
					->setLastname('Lastname')
					->setEmail($input['email'])
					->setPassword($input['password'])
					->setConfirmation($input['password']);
					
					$customerErrors = $customer->validate();
                    if (is_array($customerErrors)) {
                        $errors = array_merge($customerErrors, $errors);
                    }
					
					$validationResult = count($errors) == 0;
					
					if(true === $validationResult) {
						$customer->save();
						
						$customer->sendNewAccountEmail(
                            'confirmation',
                            $session->getBeforeAuthUrl(),
                            Mage::app()->getStore()->getId()
                        );
						
						$session->addSuccess('You have succesfully created an account');
						
					}
					
				} catch (Mage_Core_Exception $e) {
					//$session->setCustomerFormData($this->getRequest()->getPost());
					if ($e->getCode() === Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS) {
						$url = Mage::getUrl('customer/account/forgotpassword');
						$message = $this->__('There is already an account with this email address. If you are sure that it is your email address, <a href="%s">click here</a> to get your password and access your account.', $url);
						$session->setEscapeMessages(false);
					} else {
						$message = $e->getMessage();
					}
					$session->addError($message);
				} catch (Exception $e) {
					$session->setCustomerFormData($this->getRequest()->getPost())
						->addException($e, $this->__('Cannot save the customer.'));
				}
			
			} else {
				$session->addError($this->__('Email address and password are required.'));
			}
		}
					
		$this->_redirect('ilogin');			
	}
	
	public function registersuccess(){
		$this->loadLayout();
        $this->renderLayout();
	}
	
	protected function _getSession()
    {
        return Mage::getSingleton('core/session');
    }
	
	private function _getCustomerSession()
	{
		return Mage::getSingleton('customer/session');
	}
	
}