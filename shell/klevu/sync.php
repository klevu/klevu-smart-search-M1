<?php
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'abstract.php';
class Klevu_Shell_Sync extends Mage_Shell_Abstract
{
    protected $_argname = array();
 
    public function __construct() {
        parent::__construct();
 
        // Time limit to infinity
        set_time_limit(0);     
    }
 
    // Shell script point of entry
    public function run() {

        $lockFilePath =  Mage::getBaseDir()."/shell/klevu/"."klevu_running_index.lock";
		if(file_exists($lockFilePath)){
			echo "Klevu indexing process is in running state";
			return;
		} 
		
		fopen($lockFilePath, "w");
		
        try {
            if ($this->getArg('updatesonly')) {
                Mage::getModel('klevu_search/product_sync')->run();
                $failedMessage = Mage::getSingleton('core/session')->getKlevuFailedFlag();
				if(!empty($failedMessage) && $failedMessage == 1) {
					echo "Product sync failed.Please consult klevu_search.log file for more information.";
				} else {
					echo "Data updates have been sent to Klevu";
				}
            } else if($this->getArg('alldata')) {
                // Modified the updated date klevu_product_sync table
                Mage::getModel('klevu_search/product_sync')->markAllProductsForUpdate();
                // Run the product sync for all store
                Mage::getModel('klevu_search/product_sync')->run();
                Mage::getModel("content/content")->run();
				if(!empty($failedMessage) && $failedMessage == 1) {
					echo "Product sync failed.Please consult klevu_search.log file for more information.";
				} else {
					echo "All Data have been sent to Klevu";
				}
			} else if ($this->getArg('updatesonlywithindexing')) {
				/* @var $indexCollection Mage_Index_Model_Resource_Process_Collection */
				$indexCollection = Mage::getModel('index/process')->getCollection();
				foreach ($indexCollection as $index) {
					/* @var $index Mage_Index_Model_Process */
					$index->reindexAll();
				}
				
				Mage::getModel('klevu_search/product_sync')->run();
                Mage::getModel("content/content")->run();
				$failedMessage = Mage::getSingleton('core/session')->getKlevuFailedFlag();
				if(!empty($failedMessage) && $failedMessage == 1) {
					echo "Product sync failed.Please consult klevu_search.log file for more information.";
				} else {
					echo "Data updates have been sent to Klevu";
				}
			} else if($this->getArg('alldatawithindexing')) {
				
				/* @var $indexCollection Mage_Index_Model_Resource_Process_Collection */
				$indexCollection = Mage::getModel('index/process')->getCollection();
				foreach ($indexCollection as $index) {
					/* @var $index Mage_Index_Model_Process */
					$index->reindexAll();
				}
				
				// Modified the updated date klevu_product_sync table
                Mage::getModel('klevu_search/product_sync')->markAllProductsForUpdate();
                // Run the product sync for all store
                Mage::getModel('klevu_search/product_sync')->run();
                Mage::getModel("content/content")->run();
				if(!empty($failedMessage) && $failedMessage == 1) {
					echo "Product sync failed.Please consult klevu_search.log file for more information.";
				} else {
					echo "All Data have been sent to Klevu";
				}
				
            } else if($this->getArg('refreshklevuimages')) {
				$collections = Mage::getModel('catalog/product')->getCollection(); 
				foreach($collections as $collection){
					$stores = Mage::app()->getStores();
					foreach ($stores as $store) {
						$ProductModel = Mage::getModel('catalog/product')->setStoreId($store->getId())
						->load($collection->getId());
						$image = $ProductModel->getImage();
						$this->createThumb($ProductModel->getImage());
					}
				}	
			} else if($this->getArg('storecodes')) {
                $storeCodesToSync = explode(',',$this->getArg('storecodes'));
                $syncedStores = Mage::getModel('klevu_search/product_sync')->syncStores($storeCodesToSync);
                echo "Synced Stores Codes: ".implode(',',$syncedStores);

            } else {
                echo $this->usageHelp();
            }
        } catch(Exception $e){
            echo $e->getMessage();
        }
		
		if(file_exists($lockFilePath)){
			unlink($lockFilePath);
		}
		
	
    }
	
	
	public function createThumb($image) {
		$imageResized = Mage::getBaseDir('media').DS."klevu_images".$image;
		$baseImageUrl = Mage::getBaseDir('media').DS."catalog".DS."product".$image;
		if(file_exists($baseImageUrl)) {
			list($width, $height, $type, $attr)= getimagesize($baseImageUrl); 
			if($width > 200 && $height > 200) {
				if(file_exists($imageResized)) {
					if (!unlink('media/klevu_images'. $image))
						{
							Mage::helper('klevu_search')->log(Zend_Log::DEBUG, sprintf("Image Deleting Error:\n%s", $image));  
						}
				}
				Mage::getModel("klevu_search/product_sync")->thumbImageObj($baseImageUrl,$imageResized);
			}
		}
	}
 
    // Usage instructions
    public function usageHelp()
    {
        return <<<USAGE
        
Usage:  php -f sync.php -- [options]

  Note:If you choose to run this script at regular intervals to sync data, please make sure to select the value "Never" for the System -> Configuration -> Klevu -> Search Configuration -> Data Sync Settings -> Frequency option.
  
  --updatesonly If you are using this option, only the products updated since the last successful synchronization will be synchronized with the Klevu servers. Klevu uses the updated_at timestamp of the catalog_product_entity table to figure out which products to synchronize.
  
  --alldata     If you are using this option, the entire product catalog is considered for synchronization.
  
  --storecodes  If you want to sync complete data for particular store then pass store codes separated by , e.g (en_ae,en_ar)
  
  
  
USAGE;
    }
}
// Instantiate
$shell = new Klevu_Shell_Sync();
 
// Initiate script
$shell->run();
?>