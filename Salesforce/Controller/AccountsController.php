<?php
/** 
 * @author Paul
 * 
 */
class AccountsController extends SalesforceAppController {
    public $uses = array('Salesforce.Account','User');
    public $components = array(
            'Apis.Oauth' => 'salesforce',
    );
    
    public function index() {
        
    //$accounts = $this->Account->find('all',array('path' => 'sobjects/Account', 'fields' => 'Name'));
    //$query = "SELECT Name, Id from Account LIMIT 100";
    $query = "SELECT Id, Name, Email,MailingStreet, MailingCity, MailingState, MailingPostalCode from Contact LIMIT 100";
    $url = "https://na9.salesforce.com/services/data/v20.0/query?q=" . urlencode($query);
    $access_token = $this->Auth->user('salesforce_access_token');
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER,
            array("Authorization: OAuth $access_token"));
    //debug( $access_token);
    $json_response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($json_response, true);
        //$accounts = $this->Account->listAccounts();
        //debug($response);
    }
    
    public function salesforce_connect() {
        $this->Oauth->connect();
    }
    
    public function salesforce_callback() {
       $accessToken = json_decode($this->Oauth->callback(null,'https://www.simplysent.loc/salesforce/callback'));
        //Save the accessToken to the user's record.
        $this->User->save(array(
                'id' => $this->Auth->user('id'), 
                'salesforce_access_token' => $accessToken->access_token,
                'salesforce_instance_url' => $accessToken->instance_url
                ),
                false
                );
        $this->Session->write('Auth.User.salesforce_access_token',$accessToken->access_token);
        $this->Session->write('Auth.User.salesforce_instance_url',$accessToken->instance_url);
        //Save the instance url
        //Configure::write('Apis.Salesforce.hosts.rest', $accessToken->instance_url);
        //Configure::read('Apis');
    }
    

}

?>