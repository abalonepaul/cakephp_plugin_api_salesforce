<?php
/**
 * Salesforce OAuth Component
 *
 * This component extends the OauthComponent to implement some special methods for dealing with the
 * Oauth Dance with Salesforce.
 *
 *
 *
 * @author Paul Marshall <abalonepaul>
 * @link http://www.protelligence.com
 * @copyright (c) 2012 Paul Marshall
 * @license MIT License - http://www.opensource.org/licenses/mit-license.php
 */
App::uses('Component', 'Controller');
cApp::uses('OauthComponent', 'Apis.Controller');
class SalesforceOauthComponent extends OauthComponent {

	/**
	 * Same as above for OAuth v2.0
	 *
	 * @param string $oAuthRequestToken
	 * @return void
	 */
	public function authorizeV2($oAuthConsumerKey, $oAuthCallback) {

		$this->_getMap();
		$redirect = $this->_oAuthRequestDefaults['uri']['scheme'] . '://' . $this->_map['hosts']['oauth'] . '/' . $this->_map['oauth']['authorize'] . '?client_id=' . $oAuthConsumerKey . '&redirect_uri=' . $oAuthCallback;
			if (!empty($this->_config[$this->useDbConfig]['scope'])) {
			$redirect .= '&scope=' . $this->_config[$this->useDbConfig]['scope'];
		}
		$redirect .= '&display=popup';
		$redirect .= '&response_type=code';
		$this->controller->redirect($redirect);
	}


	/**
	 * Same as above for OAuth v2.0
	 *
	 * @param string $oAuthConsumerKey
	 * @param string $oAuthConsumerSecret
	 * @param string $oAuthCode
	 * @return array Array containing keys token and token_secret
	 * @author Dean Sofer
	 */
	public function getOAuthAccessTokenV2($oAuthConsumerKey, $oAuthConsumerSecret, $oAuthCode, $redirectUri) {
		$this->_getMap();
		$request = Set::merge($this->_oAuthRequestDefaults, array(
			'uri' => array(
				'host' => $this->_map['hosts']['oauth'],
				'path' => $this->_map['oauth']['access'],
			),
			'method' => 'POST',
			'body' => array(
			    'grant_type' => 'authorization_code',
				'client_id' => $oAuthConsumerKey,
				'client_secret' => $oAuthConsumerSecret,
			    'redirect_uri' => $redirectUri,
				'code' => $oAuthCode,
			)
		));
		//debug($request);
		App::uses('HttpSocketOauth', 'HttpSocketOauth.Lib');
		$Http = new HttpSocketOauth();
        $response = $Http->request($request);
        //debug($response);
        if ($Http->response['status']['code'] != 200) {
			return false;
		}
        if (is_string($response)) {
		parse_str($response, $accessToken);

		return $accessToken;
        }

		return $response['body'];
	}
	/**
	 * Refresh the OAuth v2.0 Access Token
	 *
	 * @param string $oAuthConsumerKey
	 * @param string $oAuthConsumerSecret
	 * @param string $refreshToken
	 * @return array Array containing keys token and token_secret
	 * @author Dean Sofer
	 */
	public function refreshOAuthAccessToken($refreshToken = null) {

	    if ($refreshToken == null) {
	        $oauthToken = ClassRegistry::init('OauthToken')->find('first', array('conditions' => array('user_id' => AuthComponent::user('id'),'type' => $this->useDbConfig)));
            $refreshToken = $oauthToken['OauthToken']['refresh_token'];
	    }
	    $this->_getMap();
	    $request = Set::merge($this->_oAuthRequestDefaults, array(
	            'uri' => array(
	                    'host' => $this->_map['hosts']['oauth'],
	                    'path' => $this->_map['oauth']['refresh'],
	            ),
	            'method' => 'POST',
	            'body' => array(
	                    'grant_type' => 'refresh_token',
	                    'client_id' => $this->_config[$this->useDbConfig]['login'],
	                    'client_secret' => $this->_config[$this->useDbConfig]['password'],
	                    'refresh_token' => $refreshToken,
	            )
	    ));
	    App::uses('HttpSocketOauth', 'HttpSocketOauth.Lib');
	    $Http = new HttpSocketOauth();
	    $response = $Http->request($request);
	    if ($Http->response['status']['code'] != 200) {
	        return false;
	    }
	    if (is_string($response['body'])) {
	        $accessToken = json_decode($response['body'],true);
	        $oauthToken['OauthToken']['access_token'] = $accessToken['access_token'];
	        ClassRegistry::init('OauthToken')->save($oauthToken);
            $this->Session->write('OAuth.salesforce.access_token',$accessToken['access_token']);
	        //$this->controller->redirect($this->controller->request->here);
	        //return $accessToken;
	    }

	    //return $response['body'];
	}

}
