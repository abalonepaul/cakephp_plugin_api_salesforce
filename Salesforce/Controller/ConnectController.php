<?php
/**
 * @author Paul
 *
 */
class ConnectController extends SalesforceAppController {
    public $uses = array('User','Apis.OauthToken');
    public $components = array(
            'Apis.Oauth' => 'salesforce',
    );

    public function salesforce_connect() {
        $this->Oauth->connect();
    }

    public function salesforce_callback() {

        $callback = Router::url('/salesforce/callback',true);
        //debug($callback);

       $accessToken = json_decode( $this->Oauth->callback(null,$callback) );
       //debug($accessToken);
       $instance_url = substr($accessToken->instance_url, 8);

       //debug($this->Oauth);
       $oauthToken = $this->User->OauthToken->find('first', array('conditions' => array('user_id' => $this->Auth->user('id'),'')));
       if (isset($accessToken->refresh_token)) {
       //Save the accessToken to the user's record.
       $oauthToken['OauthToken'] = array(
               'user_id' => $this->Auth->user('id'),
               //'source_name' => '',
               'type' => 'salesforce',
               'access_token' => $accessToken->access_token,
               'refresh_token' => $accessToken->refresh_token,
               'instance_url' => $instance_url
                );
        }elseif (isset($accessToken->aceess_token)) {
            $oauthToken['OathToken']['access_token'] = $accessToken->aceess_token;
        }
        $this->OauthToken->save($oauthToken);
        $this->Session->write('OAuth.salesforce.access_token',$accessToken->access_token);
        //$this->Session->write('OAuth.salesforce.refresh_token',$accessToken->refresh_token);
        $this->Session->write('OAuth.salesforce.instance_url',$instance_url);
        $this->autoLayout = false;
        echo true;

        //Save the instance url
        //Configure::write('Apis.Salesforce.hosts.rest', $accessToken->instance_url);
        //Configure::read('Apis');
    }

}

