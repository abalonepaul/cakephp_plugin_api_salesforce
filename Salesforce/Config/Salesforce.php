<?php
/**
 * A Salesforce API Method Map
 *
 * Refer to the apis plugin for how to build a method map
 * @link https://github.com/ProLoser/CakePHP-Api-Datasources
 */
$instance_url = SessionComponent::read('OAuth.salesforce.instance_url');
$config['Apis']['Salesforce']['hosts'] = array(
	'oauth' => 'login.salesforce.com/services/oauth2', // Main domain+path for OAuth requests
	'rest' => $instance_url . '/services/data/v26.0', // Main domain+path for REST requests
);

$config['Apis']['Salesforce']['oauth'] = array(
	'version' => '2.0', // 1.0, 2.0 or null
	// These paths are appended to the end of the Host-OAuth value
	'authorize' => 'authorize', // Example URI: https://github.com/login/oauth/authorize
	'request' => 'requestToken', //client_id={$this->config['login']}&redirect_uri
	'access' => 'token',
    'scheme' => 'https',
	'login' => 'authenticate', // Like authorize, just auto-redirects
	'logout' => 'invalidateToken',
    'refresh' => 'token'
);

$config['Apis']['Salesforce']['read'] = array(
    'Account' => array(
            'sobjects/Account'
        ),
    'Contact' => array(
            'query' => array(
                'q'
            )
        ),
);
// Refer to READ block
$config['Apis']['Salesforce']['create'] = array();
// Refer to READ block
$config['Apis']['Salesforce']['update'] = array();
// Refer to READ block
$config['Apis']['Salesforce']['delete'] = array();// Refer to READ block
$config['Apis']['Salesforce']['describe'] = array(
    'Account' => array(
            'sobjects/Account/describe'
        ),
    'Contact' => array(
            'sobjects/Contact/describe'
        ),
);