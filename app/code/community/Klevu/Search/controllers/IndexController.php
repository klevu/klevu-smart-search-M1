<?php
/**
 * Klevu FrontEnd Controller
 */
class Klevu_Search_IndexController extends Mage_Core_Controller_Front_Action {
    
    public function IndexAction() {
      
	    $this->loadLayout();  
        $query = $this->getRequest()->getParam('q');
        if(!empty($query)) {   
            $head = $this->getLayout()->getBlock('head');        
            $head->setTitle($this->__("Search results for: '%s'",$query));
        } else {
            $this->getLayout()->getBlock("head")->setTitle($this->__("Search"));
        }
	    if($breadcrumbs = $this->getLayout()->getBlock("breadcrumbs")) {
            $breadcrumbs->addCrumb("home", array(
                "label" => $this->__("Home"),
                "title" => $this->__("Home"),
                "link"  => Mage::getBaseUrl()
		   ));

			if(!empty($query)) {   
				$breadcrumbs->addCrumb("Search Result", array(
                "label" => $this->__($this->__("Search results for: '%s'",$query)),
                "title" => $this->__($this->__("Search results for: '%s'",$query))
				));
				
			} else {
				$breadcrumbs->addCrumb("Search Result", array(
                "label" => $this->__("Search results"),
                "title" => $this->__("Search results")
				));
			}
        }
        $this->renderLayout(); 
    }

    public function runexternalylogAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }    
     
    /**
     * Send different logs to klevu server to debug the data
     */
    public function runexternalyAction(){
        try {
                $config = Mage::helper('klevu_search/config');
                if($config->isExternalCallEnabled()){
                    if($this->getRequest()->getParam('data') == "updatesonly") {
                        Mage::getModel('klevu_search/product_sync')->run();
                        Mage::getModel("content/content")->run();
                        Mage::getSingleton('core/session')->addSuccess("Updated Data has been sent to klevu.");
                        
                    } else if($this->getRequest()->getParam('data') == "alldata") {
                        // Modified the updated date klevu_product_sync table
                        Mage::getModel('klevu_search/product_sync')->markAllProductsForUpdate();
                        // Run the product sync for all store
                        Mage::getModel('klevu_search/product_sync')->run();
                        Mage::getModel("content/content")->run();
                        Mage::getSingleton('core/session')->addSuccess("All products Data sent to klevu.");

                    }

                }
                $debugapi = Mage::getModel('klevu_search/product_sync')->getApiDebug();
                $content="";
                if($this->getRequest()->getParam('debug') == "klevu") {
                    // get last 500 lines from klevu log 
                    $path = Mage::getBaseDir("log")."/Klevu_Search.log";
                    if($this->getRequest()->getParam('lines')) {
                        $line = $this->getRequest()->getParam('lines'); 
                    }else {
                        $line = 100;
                    }
                    $content.= $this->getLastlines($path,$line,true);
                   
                    //send php and magento version
                    $content.= "</br>".'****Current Magento version on store:'.Mage::getVersion()."</br>";
                    $content.= "</br>".'****Current PHP version on store:'. phpversion()."</br>";
                    
                    //send cron and  logfile data
                    $cron = Mage::getBaseDir()."/cron.php";
                    $cronfile = file_get_contents($cron);
                    $content.= nl2br(htmlspecialchars($content)).nl2br(htmlspecialchars($cronfile));
                    $response = Mage::getModel("klevu_search/api_action_debuginfo")->debugKlevu(array('apiKey'=>$debugapi,'klevuLog'=>$content,'type'=>'log_file'));
                    if($response->getMessage()=="success") {
                        Mage::getSingleton('core/session')->addSuccess("Klevu search log sent.");
                    }
                    
                    $content =  serialize(Mage::getModel('klevu_search/product_sync')->debugsIds());
                    $response = Mage::getModel("klevu_search/api_action_debuginfo")->debugKlevu(array('apiKey'=>$debugapi,'klevuLog'=>$content,'type'=>'product_table'));
                    
                    if($response->getMessage()=="success") {
                        Mage::getSingleton('core/session')->addSuccess("Status of indexing queue sent.");
                    }else {
                        Mage::getSingleton('core/session')->addSuccess($response->getMessage());
                    }
                    
                    $response = Mage::getModel("klevu_search/api_action_debuginfo")->debugKlevu(array('apiKey'=>$debugapi,'klevuLog'=>$content,'type'=>'index'));
                    if($response->getMessage()=="success") {
                        Mage::getSingleton('core/session')->addSuccess("Status of magento indices sent.");
                    }else {
                        Mage::getSingleton('core/session')->addSuccess($response->getMessage());
                    }
                    Mage::helper('klevu_search')->log(Zend_Log::DEBUG, sprintf("klevu debug data was sent to klevu server successfully."));
                }
                $rest_api = $this->getRequest()->getParam('api');
                if(!empty($rest_api)) {
                    Mage::getModel('klevu_search/product_sync')->sheduleCronExteranally($rest_api);
                    Mage::getSingleton('core/session')->addSuccess("Cron scheduled externally."); 
                }
                $this->_redirect('search/index/runexternalylog');
                
        }
        catch(Exception $e) {
              Mage::helper('klevu_search')->log(Zend_Log::DEBUG, sprintf("Product Synchronization was Run externally:\n%s", $e->getMessage()));
        }
    }
    
    function getLastlines($filepath, $lines, $adaptive = true) {
        // Open file
        $f = @fopen($filepath, "rb");
        if ($f === false) return false;
        // Sets buffer size
        if (!$adaptive) $buffer = 4096;
        else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
        // Jump to last character
        fseek($f, -1, SEEK_END);
        // Read it and adjust line number if necessary
        // (Otherwise the result would be wrong if file doesn't end with a blank line)
        if (fread($f, 1) != "\n") $lines -= 1;
        // Start reading
        $output = '';
        $chunk = '';
        // While we would like more
        while (ftell($f) > 0 && $lines >= 0) {
        // Figure out how far back we should jump
        $seek = min(ftell($f), $buffer);
        // Do the jump (backwards, relative to where we are)
        fseek($f, -$seek, SEEK_CUR);
        // Read a chunk and prepend it to our output
        $output = ($chunk = fread($f, $seek)) . $output;
        // Jump back to where we started reading
        fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
        // Decrease our line counter
        $lines -= substr_count($chunk, "\n");
        }
        // While we have too many lines
        // (Because of buffer size we might have read too many)
        while ($lines++ < 0) {
        // Find first newline and remove all text before that
        $output = substr($output, strpos($output, "\n") + 1);
        }
        // Close file and return
        fclose($f);
        return trim($output);
    }
    
    
    
}
