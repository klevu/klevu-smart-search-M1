<?php
class Klevu_Boosting_Adminhtml_BoostController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu("boosting/boost")->_addBreadcrumb(Mage::helper("adminhtml")->__("Product Boosting Manager"), Mage::helper("adminhtml")->__("Boost Manager"));
        return $this;
    }
    public function indexAction()
    {
        $this->_title($this->__("Product Boosting Manager"));
        $this->_initAction();
        $this->renderLayout();
    }
    public function editAction()
    {
        $this->_title($this->__("Edit Rule"));
        $id = $this->getRequest()->getParam("id");
        $model = Mage::getModel("boosting/boost")->load($id);
        $model->getConditions()->setJsFormObject('rule_conditions_fieldset');
        if ($model->getId()) {
            Mage::register("boost_data", $model);
            $this->loadLayout();
            $this->_setActiveMenu("boosting/boost");
            $this->_addBreadcrumb(Mage::helper("adminhtml")->__("Product Boosting Manager"), Mage::helper("adminhtml")->__("Product Boosting Manager"));
            $this->_addBreadcrumb(Mage::helper("adminhtml")->__("Product Boosting Description"), Mage::helper("adminhtml")->__("Product Boosting Description"));
            $this->getLayout()->getBlock("head")->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()->createBlock("boosting/adminhtml_boost_edit"))->_addLeft($this->getLayout()->createBlock("boosting/adminhtml_boost_edit_tabs"));
            $this->renderLayout();
        }
        else {
            Mage::getSingleton("adminhtml/session")->addError(Mage::helper("boosting")->__("Rule does not exist."));
            $this->_redirect("*/*/");
        }
    }
    public function newAction()
    {
        $this->_title($this->__("Boosting"));
        $this->_title($this->__("Boost"));
        $this->_title($this->__("New Rule"));
        $id = $this->getRequest()->getParam("id");
        $model = Mage::getModel("boosting/boost")->load($id);
        $data = Mage::getSingleton("adminhtml/session")->getFormData(true);
        if (!empty($data)) {
            $model->loadPost($data);
        }

        Mage::register("boost_data", $model);
        $this->loadLayout();
        $this->_setActiveMenu("boosting/boost");
        $this->getLayout()->getBlock("head")->setCanLoadExtJs(true);
        $this->_addBreadcrumb(Mage::helper("adminhtml")->__("Product Boosting Manager"), Mage::helper("adminhtml")->__("Product Boosting Manager"));
        $this->_addBreadcrumb(Mage::helper("adminhtml")->__("Product Boosting Description"), Mage::helper("adminhtml")->__("Product Boosting Description"));
        $this->_addContent($this->getLayout()->createBlock("boosting/adminhtml_boost_edit"))->_addLeft($this->getLayout()->createBlock("boosting/adminhtml_boost_edit_tabs"));
        $this->renderLayout();
    }
    public function saveAction()
    {
        if (!$this->getRequest()->getPost()) {
            $this->_redirect('*/*/');
        }

        $boostModel = $this->_initBoost();
        $data = $this->getRequest()->getPost();
        try {
            $validateResult = $boostModel->validateData(new Varien_Object($data));
            if ($validateResult !== true) {
                foreach($validateResult as $errorMessage) {
                    $this->_getSession()->addError($errorMessage);
                }

                $this->_redirect(
                    '*/*/edit', array(
                    'id' => $boostModel->getId()
                    )
                );
                return;
            }

            $data['conditions'] = $data['rule']['conditions'];
            unset($data['rule']);
            $boostModel->loadPost($data);
            $boostModel->save();
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The Product rule has been saved.'));
            if ($this->getRequest()->getParam('back')) {
                return $this->_redirect(
                    '*/*/edit', array(
                    'id' => $boostModel->getId() ,
                    )
                );
            }
        }
        catch(Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch(Exception $e) {
            $this->_getSession()->addError($this->__('An error occurred while saving the rule data. Please review the log and try again.'));
            Mage::logException($e);
            $this->_redirect(
                '*/*/edit', array(
                'id' => $this->getRequest()->getParam('id')
                )
            );
            return;
        }

        $this->_redirect('*/*/');
    }
    protected function _initBoost()
    {
        $boostModel = Mage::getModel('boosting/boost');
        $id = (int)$this->getRequest()->getParam('id', null);
        if ($id) {
            try {
                $boostModel->load($id);
                if (null === $boostModel->getId()) {
                    throw new Exception($this->__('This rule no longer exists'));
                }
            }
            catch(Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                return null;
            }
        }

        Mage::register('boost_data', $boostModel);
        return $boostModel;
    }
    public function deleteAction()
    {
        if ($this->getRequest()->getParam("id") > 0) {
            try {
                $model = Mage::getModel("boosting/boost");
                $model->setId($this->getRequest()->getParam("id"))->delete();
                Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Rule was successfully deleted"));
                $this->_redirect("*/*/");
            }
            catch(Exception $e) {
                Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
                $this->_redirect(
                    "*/*/edit", array(
                    "id" => $this->getRequest()->getParam("id")
                    )
                );
            }
        }

        $this->_redirect("*/*/");
    }
    public function massRemoveAction()
    {
        try {
            $ids = $this->getRequest()->getPost('ids', array());
            foreach($ids as $id) {
                $model = Mage::getModel("boosting/boost");
                $model->setId($id)->delete();
            }

            Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Rule(s) were successfully removed."));
        }
        catch(Exception $e) {
            Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
        }

        $this->_redirect('*/*/');
    }

    public function newConditionHtmlAction()
    {
        $id = $this->getRequest()->getParam('id');
        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type = $typeArr[0];
        $model = Mage::getModel($type)->setId($id)->setType($type)->setRule(Mage::getModel('boosting/boost'))->setPrefix('conditions');
        if (!empty($typeArr[1])) {
            $model->setAttribute($typeArr[1]);
        }

        $html = '';
        if ($model instanceof Mage_Rule_Model_Condition_Abstract) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $html = $model->asHtmlRecursive();
        }

        $this->getResponse()->setBody($html);
    }
    
    protected function _isAllowed()
    {
        return true;
    }
}
