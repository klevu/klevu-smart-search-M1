<?php

class Klevu_Search_Model_Session extends Mage_Core_Model_Session {

    public function __construct() {
        $this->init('klevu_search');
    }
}
