<?php
/**
 * @author Paul
 *
 */
class SalesforceContactsController extends SalesforceAppController {
    public $uses = array('Salesforce.SalesforceContact','User');
    public $components = array(
            'Apis.Oauth' => 'salesforce',
    );

    public function index() {
        $params = array(
                'path' => 'query',
                'section' => 'Contact',
                'fields' => array('Id,Name,Email,MailingStreet,MailingCity,MailingState,MailingPostalCode'),
                'conditions' => array(
                        'Name != ' => null,
                        'Email != ' => null
                        //'MailingStreet' => 'NY'
                ));

        $result = $this->SalesforceContact->find('all',$params);
        //debug($this->SalesforceContact->response);
        if ($result == false) {
            if ($this->SalesforceContact->response[0]['errorCode'] == 'INVALID_SESSION_ID') {
                $this->Oauth->refreshOAuthAccessToken();

            }
        }
        $contacts = $result['records'];
        $this->set(compact('contacts'));
    }

    public function salesforce_connect() {
        $this->Oauth->connect(Router::url(array('salesforce/connect/salesforce_connect')));
    }

    public function salesforce_callback() {
       $callback = Router::url('/salesforce/callback');
       //debug($callback);
       $accessToken = json_decode($this->Oauth->callback(null,'https://www.simplysent.loc/salesforce/callback'));
        $instance_url = substr($accessToken->instance_url, 8);
       //Save the accessToken, refreshToken and Instance Url.
        $oauthToken = $this->User->OauthToken->find('first', array('conditions' => array('user_id' => $this->Auth->user('id'),'')));
        $oauthToken['OauthToken']['user_id'] = $this->Auth->user('id');
        $oauthToken['OauthToken']['type'] = 'salesforce';
        $oauthToken['OauthToken']['access_token'] = $accessToken->access_token;
        $oauthToken['OauthToken']['refresh_token'] = $accessToken->refresh_token;
        $oauthToken['OauthToken']['instance_url'] = $instance_url;
        
        $this->User->OauthToken->save($oauthToken, false);
        $this->Session->write('OAuth.salesforce.access_token',$accessToken->access_token);
        $this->Session->write('OAuth.salesforce.instance_url',$instance_url);
        //Save the instance url
        //Configure::write('Apis.Salesforce.hosts.rest', $accessToken->instance_url);
        //debug(Configure::read('Apis'));
    }
    
    public function contact_options() {

            $result = $this->SalesforceContact->find('all',array(
                'path' => 'query',
                'section' => 'Contact',
                'fields' => array('Id,Name,Email'),
                'conditions' => array(
                        'Name != ' => null,
                        'Email != ' => null
                )
                ));
            $contacts = $result['records'];
            foreach( $contacts as $contact) {
                    $options[$contact['Id']] = $contact['Name'];
            }
        $listName = 'Salesforce';
        $this->set(compact('options', 'listName'));
        $this->autoRender = false;
        $this->render('/Common/ajax_div');
    }

    public function contact_divs() {

        $token = $this->Session->read('OAuth.salesforce.access_token');
        //check if salesforce is OAuth authenticated
        if (!$token ){
            
            echo 'false';
            exit();
        }
        $result = $this->SalesforceContact->find('all',array(
                'path' => 'query',
                'section' => 'Contact',
                'fields' => array('Id,FirstName,LastName,Name,Email,MailingStreet,MailingCity,MailingState,MailingPostalCode'),
                'conditions' => array(
                        'Name != ' => null,
                        'Email != ' => null
                )
        ));
        //debug($result);
        //debug($this->SalesforceContact->response);exit;
        if ($result == false) {
            if ($this->SalesforceContact->response[0]['errorCode'] == 'INVALID_SESSION_ID') {
                $this->Oauth->refreshOAuthAccessToken();
                $result = $this->SalesforceContact->find('all',array(
                        'path' => 'query',
                        'section' => 'Contact',
                        'fields' => array('Id,FirstName,LastName,Name,Email,MailingStreet,MailingCity,MailingState,MailingPostalCode'),
                        'conditions' => array(
                                'Name != ' => null,
                                'Email != ' => null
                        )
                ));

            }
        }
        $contacts = $result['records'];
        $i = 0;
        foreach( $contacts as $contact) {
            $recipientList['Recipient'][$i]['id'] = $contact['Id'];
            $recipientList['Recipient'][$i]['first_name'] = $contact['FirstName'];
            $recipientList['Recipient'][$i]['last_name'] = $contact['LastName'];
            if (empty($contact['MailingStreet']) || empty($contact['MailingCity']) || empty($contact['MailingState']) || empty($contact['MailingZip'])) {
               $recipientList['Recipient'][$i]['address_1'] = 'SimplySent will request Address from Recipient once order completed.';
            } else {
            $recipientList['Recipient'][$i]['address_1'] = $contact['MailingStreet'];
            $recipientList['Recipient'][$i]['city'] = $contact['MailingCity'];
            $recipientList['Recipient'][$i]['state'] = $contact['MailingState'];
            $recipientList['Recipient'][$i]['zip'] = $contact['MailingPostalCode'];
            $recipientList['Recipient'][$i]['source_id'] = $contact['Id'];
            }
            $i++;
        }
        $listName = 'Salesforce';
        $this->set(compact('recipientList', 'listName'));
        $this->autoRender = false;
        $this->render('/Common/ajax_div');
    }

}
