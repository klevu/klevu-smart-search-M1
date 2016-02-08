<?php

class Klevu_Search_Model_Adminhtml_Comment extends Klevu_Search_Model_Product_Sync {

    public function getCommentText(){ //this method returns the text for the label
        $check_preserve = Mage::getModel("klevu_search/product_sync")->getFeatures();
        if(!empty($check_preserve['disabled'])) {
            if(strpos($check_preserve['disabled'],"preserves_layout") !== false) {
                $klevu_html ="";
                if(!empty($check_preserve['preserve_layout_message']) || !empty($check_preserve['upgrade_label'])) {
                    $klevu_html.=  "<div class='klevu-upgrade-block-simple'>";
                        if(!empty($check_preserve['preserve_layout_message'])){
                            $klevu_html.=$check_preserve['preserve_layout_message'];
                        }
                        
                    $klevu_html.="</div>";
                }  
                return $klevu_html;                
            } else {
               return "Choose your Layout";
            }
        } 
    }
}
