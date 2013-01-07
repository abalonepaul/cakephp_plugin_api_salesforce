<?php
class Account extends SalesforceAppModel {
    public $useTable = false;
    
    public function listAccounts() {
        $this->setDataSource('salesforce');
        return $this->find('all',array('path' => 'sobjects/Account'));
        
    }
    
}