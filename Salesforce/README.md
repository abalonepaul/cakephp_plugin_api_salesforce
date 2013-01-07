# CakePHP Salesforce Api Plugin

*
## Installation

This plugin depends on the [ApisDatasource](https://github.com/ProLoser/CakePHP-Api-Datasources). Refer to the instructions found there.

You need to add the following datasource config to your database.php or other database config file.

    public $salesforce = array(
            'datasource' => 'Salesforce.Salesforce',
            'login' => 'YOUR-API-KEY',
            'password' => 'YOUR-API-SECRET',
            //With the Salesforce Component, this may not be necessary
            'response_type' => 'code',
    );

## Contributors

* [abalonepaul](http://www.protelligence.com/)

## Bugs

If you need help, try opening a ticket here on the [bug tracker](https://github.com/proloser/cakephp-api-datasources/issues)
 **CHANGE URL**