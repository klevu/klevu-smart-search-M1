<?php

class Klevu_Search_Adminhtml_Klevu_Search_WizardController extends Mage_Adminhtml_Controller_Action {
    
    public function configure_userplanAction() {
        $this->loadLayout();
        $this->initLayoutMessages('klevu_search/session');
        $this->renderLayout();
    }

    public function configure_userplan_postAction() {
        /* if partner account selected as UserPlan then change plan to trial*/
                $partnerAccount = false;
                $request = $this->getRequest();
                $session = Mage::getSingleton('klevu_search/session');
                $userPlan = $request->getPost("userPlan");
                if($userPlan=="partnerAccount"){
                   $partnerAccount = true;
                }
                 
                if(empty($userPlan)) {
                    $session->addError(Mage::helper("klevu_search")->__("Not sure, which plan to select? Select Premium to try all features free for 14-days."));
                    return $this->_forward("configure_userplan");
                
                }
                
                $api = Mage::helper("klevu_search/api");
                $result = $api->createUser(
                    Mage::getSingleton('core/session')->getKlevuNewEmail(),
                    Mage::getSingleton('core/session')->getKlevuNewPassword(),
                    $userPlan,
                    $partnerAccount,
                    Mage::getSingleton('core/session')->getKlevuNewUrl(),
                    Mage::getSingleton('core/session')->getMerchantEmail(),
                    Mage::getSingleton('core/session')->getContactNo()
                );
                
        if ($result["success"]) {
            $session->setConfiguredCustomerId($result["customer_id"]);
            if (isset($result["message"])) {
                $session->addSuccess(Mage::helper("klevu_search")->__($result["message"]));
            }
            return $this->_forward("configure_store");
        } else {
            $session->addError(Mage::helper("klevu_search")->__($result["message"]));
            return $this->_forward("configure_userplan");
        }
       
       
        return $this->_forward("configure_store");
    }    
    public function configure_userAction() {
        $this->loadLayout();
        $this->initLayoutMessages('klevu_search/session');
        $this->renderLayout();
    }

    public function configure_user_postAction() {

        $request = $this->getRequest();

        if (!$request->isPost() || !$request->isAjax()) {
            return $this->_redirect('adminhtml/dashboard');
        }

        $api = Mage::helper("klevu_search/api");
        $session = Mage::getSingleton('klevu_search/session');
        Mage::getSingleton('core/session')->setHideStep("no"); 
        if ($request->getPost("klevu_existing_email")) {
            $result = $api->getUser(
                $request->getPost("klevu_existing_email"),
                $request->getPost("klevu_existing_password")
            );
            
            if ($result["success"]) {
                Mage::getSingleton('core/session')->setHideStep("yes");
                $session->setConfiguredCustomerId($result["customer_id"]);
                if (isset($result["message"])) {
                    $session->addSuccess(Mage::helper("klevu_search")->__($result["message"]));
                }
                return $this->_forward("configure_store");
            } else {
                $session->addError(Mage::helper("klevu_search")->__($result["message"]));
                return $this->_forward("configure_user");
            }
        } else {
            $termsconditions = $request->getPost("termsconditions");
            $klevu_new_email = $request->getPost("klevu_new_email");
            $klevu_new_password = $request->getPost("klevu_new_password");
            $userPlan = $request->getPost("userPlan");
            $partnerAccount = false;
            $klevu_new_url = $request->getPost("klevu_new_url");
            $merchantEmail = $request->getPost("merchantEmail");
            $contactNo = $request->getPost("countyCode")."-".$request->getPost("contactNo");
            $error = true;
            if(empty($klevu_new_email) || empty($klevu_new_password) || empty($klevu_new_url)
            || empty($merchantEmail) ) {
                $session->addError(Mage::helper("klevu_search")->__("Missing details in the form. Please check."));
                return $this->_forward("configure_user");
            } else if(!preg_match("/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/i",$klevu_new_email)) {
                $session->addError(Mage::helper("klevu_search")->__("Please enter valid Primary Email."));
                return $this->_forward("configure_user");
            } else if(!preg_match("/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/i",$merchantEmail)) {
                $session->addError(Mage::helper("klevu_search")->__("Please enter valid Retailer Email."));
                return $this->_forward("configure_user");
            }else if(empty($termsconditions)){
                $session->addError(Mage::helper("klevu_search")->__("Please accept terms and conditions."));
                return $this->_forward("configure_user");
            }else {
                   
                    $result = $api->checkUserDetail(
                        $request->getPost("klevu_new_email")
                    );
                    if ($result["success"]) {
                        Mage::getSingleton('core/session')->setTermsconditions($request->getPost("termsconditions"));
                        Mage::getSingleton('core/session')->setKlevuNewEmail($request->getPost("klevu_new_email"));
                        Mage::getSingleton('core/session')->setKlevuNewPassword($request->getPost("klevu_new_password"));
                        Mage::getSingleton('core/session')->setKlevuNewUrl($request->getPost("klevu_new_url"));
                        Mage::getSingleton('core/session')->setMerchantEmail($request->getPost("merchantEmail"));
                        $contactNo = $request->getPost("countyCode")."-".$request->getPost("contactNo");
                        Mage::getSingleton('core/session')->setContactNo($contactNo);
                        return $this->_forward("configure_userplan");
                    } else {
                            $session->addError(Mage::helper("klevu_search")->__($result["message"]));
                            return $this->_forward("configure_user");
                    }
            }
        }

    }

    public function configure_storeAction() {
        $request = $this->getRequest();

        if (!$request->isAjax()) {
            return $this->_redirect("adminhtml/dashboard");
        }

        $session = Mage::getSingleton("klevu_search/session");

        if (!$session->getConfiguredCustomerId()) {
            $session->addError(Mage::helper("klevu_search")->__("You must configure a user first."));
            return $this->_redirect("*/*/configure_user");
        }

        $this->loadLayout();
        $this->initLayoutMessages('klevu_search/session');
        $this->renderLayout();
    }

    public function configure_store_postAction() {

        $request = $this->getRequest();
        if (!$request->isPost() || !$request->isAjax()) {
            return $this->_redirect("adminhtml/dashboard");
        }

        $config = Mage::helper("klevu_search/config");
        $api = Mage::helper("klevu_search/api");
        $session = Mage::getSingleton('klevu_search/session');
        $customer_id = $session->getConfiguredCustomerId();

        if (!$customer_id) {
            $session->addError(Mage::helper("klevu_search")->__("You must configure a user first."));
            return $this->_redirect("*/*/configure_user");
        }

        $store_code = $request->getPost("store");
        if (strlen($store_code) == 0) {
            $session->addError(Mage::helper("klevu_search")->__("Must select a store"));
            return $this->_forward("configure_store");
        }

        try {
            $store = Mage::app()->getStore($store_code);
        } catch (Mage_Core_Model_Store_Exception $e) {
            $session->addError(Mage::helper("klevu_search")->__("Selected store does not exist."));
            return $this->_forward("configure_store");
        }

        // Setup the live and test Webstores
        foreach (array(false) as $test_mode) {
            $result = $api->createWebstore($customer_id, $store, $test_mode);
            if ($result["success"]) {
                $config->setJsApiKey($result["webstore"]->getJsApiKey(), $store, $test_mode);
                $config->setRestApiKey($result["webstore"]->getRestApiKey(), $store, $test_mode);
                $config->setHostname($result["webstore"]->getHostedOn(), $store, $test_mode);
                $config->setCloudSearchUrl($result['webstore']->getCloudSearchUrl(), $store, $test_mode);
                $config->setAnalyticsUrl($result['webstore']->getAnalyticsUrl(), $store, $test_mode);
                $config->setJsUrl($result['webstore']->getJsUrl(), $store, $test_mode);
                $config->setRestHostname($result['webstore']->getRestHostname(), $store, $test_mode);
                $config->setTiresUrl($result['webstore']->getTiresUrl(), $store, $test_mode);
                if (isset($result["message"])) {
                    $session->addSuccess(Mage::helper("klevu_search")->__($result["message"]));
                    $session->setFirstSync($store_code);
                }
            } else {
                $session->addError(Mage::helper("klevu_search")->__($result["message"]));
                return $this->_forward("configure_store");
            }
        }
        
        $config->setTaxEnabledFlag($request->getPost("tax_enable"), $store);
        $config->setSecureUrlEnabledFlag($request->getPost("secureurl_setting"), $store);

        // Clear Product Sync and Order Sync data for the newly configured store
        Mage::getModel("klevu_search/product_sync")->clearAllProducts($store);
        Mage::getModel("klevu_search/order_sync")->clearQueue($store);

        $session->setConfiguredStoreCode($store_code);

        $session->addSuccess("Store configured successfully. Saved API credentials.");

        // Schedule a Product Sync
        Mage::getModel("klevu_search/product_sync")->schedule();
       
        $this->loadLayout();
        $this->initLayoutMessages("klevu_search/session");
        $this->renderLayout();
        return;
    }

    public function configure_attributesAction() {
        
        $request = $this->getRequest();

        if (!$request->isAjax()) {
            return $this->_redirect("adminhtml/dashboard");
        }

        $session = Mage::getSingleton("klevu_search/session");

        if (!$session->getConfiguredCustomerId()) {
            $session->addError(Mage::helper("klevu_search")->__("You must configure a user first."));
            return $this->_redirect("*/*/configure_user");
        }

        if (!$session->getConfiguredStoreCode()) {
            $session->addError(Mage::helper("klevu_search")->__("Must select a store"));
            return $this->_redirect("*/*/configure_store");
        }

        $this->loadLayout();
        $this->initLayoutMessages('klevu_search/session');
        $this->renderLayout();
    }

    public function configure_attributes_postAction() {
    
        $request = $this->getRequest();

        if (!$request->isPost() || !$request->isAjax()) {
            return $this->_redirect("adminhtml/dashboard");
        }

        $session = Mage::getSingleton("klevu_search/session");

        if (!$session->getConfiguredCustomerId()) {
            $session->addError(Mage::helper("klevu_search")->__("You must configure a user first."));
            return $this->_redirect("*/*/configure_user");
        }

        if (!$session->getConfiguredStoreCode()) {
            $session->addError(Mage::helper("klevu_search")->__("Must select a store"));
            return $this->_redirect("*/*/configure_store");
        }

        if ($attributes = $request->getPost("attributes")) {
            $store = Mage::app()->getStore($session->getConfiguredStoreCode());

            Mage::helper("klevu_search/config")->setAdditionalAttributesMap($attributes, $store);

            $session->addSuccess(Mage::helper("klevu_search")->__("Attributes configured successfully. Attribute mappings saved to System Configuration."));

            // Schedule a Product Sync
            Mage::getModel("klevu_search/product_sync")->schedule();

            $this->loadLayout();
            $this->initLayoutMessages("klevu_search/session");
            $this->renderLayout();
            return;
        } else {
            $session->addError(Mage::helper("klevu_search")->__("Missing attributes!"));
            //return $this->_forward("configure_attributes");
        }
    }
}
