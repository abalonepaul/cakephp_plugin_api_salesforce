<?php
class SalesforceContact extends SalesforceAppModel {
    public $useTable = false;
    public $table = 'Contact';
    public $primaryKey = 'Id';

    public function onError($error) {
        return $error;

    }

}