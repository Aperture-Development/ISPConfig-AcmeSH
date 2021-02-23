#!/usr/bin/env php
<?php 
/* *
 * ISPConfig ACME.SH Integration Script
 * 
 * Description: Allows easy integration of ACME.SH into ISPConfig and to deploy Wildcard Certificates to it.
 * 
 * @author Aperture Development <developers@aperture-Development.de>
 * @version 0.0.1
 * @license by-sa 4.0
*/

/**
 * Prepare Variables
 */
# ISPConfig URI
$ispconfigUri   = 'https://localhost:8080/remote/';

# ISPConfig API username
$username       = 'apiuser';

# ISPConfig API password
$password       = 'apipassword';

# The command on your server to reload a service (%service% gets replaced with the service to be restarted)
$reloadCmd      = 'systemctl reload %service%';

# Location of the acme.sh certificates (the path defined with --cert-home at the acme.sh installation, by default its the same path as acme.sh)
$acmeshLocation = '/etc/acmesh/live';

/**
 * #######################################################################
 * DO NOT EDIT STUFF BELOW THIS LINE UNLESS YOU KNOW WHAT YOU ARE DOING!!!
 * #######################################################################
 */
require_once('./lib/ispconfig_soap.php');
$arguments = getopt('d:m::f::s::u::p::l::r::h', array('domain:', 'service::', 'username::', 'password::', 'uri::', 'reloadcmd::', 'help'));
/**
 * Check if the help paramater has been used
 */
if(isset($arguments['h']) || isset($arguments['help'])) {
    echo file_get_contents('./help.txt');
    exit(0);
}

/**
 * Check if paramaters have been supplied and overwrite default ones
 */
if(isset($arguments['u']) || isset($arguments['username'])) {
    $username = isset($arguments['u']) ? $arguments['u'] : $arguments['username'];
}

if(isset($arguments['p']) || isset($arguments['password'])) {
    $password = isset($arguments['p']) ? $arguments['p'] : $arguments['password'];
}

if(isset($arguments['l']) || isset($arguments['uri'])) {
    $ispconfigUri = isset($arguments['l']) ? $arguments['l'] : $arguments['uri'];
}

if(isset($arguments['r']) || isset($arguments['reloadcmd'])) {
    $reloadCmd = isset($arguments['r']) ? $arguments['r'] : $arguments['reloadcmd'];
}

/*
    Finally start the script execution
*/
try {
    /**
     * Check if domain paramater has been provided and abord execution if not
     */
    if(!isset($arguments['d']) && !isset($arguments['domain'])) {
        throw new Exception('Missing required argument: -d / --domain');
    } else {
        $tempDomainsShort = isset($arguments['d']) ? $arguments['d'] : array();
        $tempDomainsLong = isset($arguments['domain']) ? $arguments['domain'] : array();

        if(gettype($tempDomainsShort) === 'array' && gettype($tempDomainsLong) === 'array') {
            $domains = array_merge($tempDomainsShort, $tempDomainsLong);
        } else {
            $domains[] = $tempDomainsShort;
        }
    }

    // check if all these parameters have been provided once and not multible times!
    if(gettype($username) === 'array' || gettype($password) === 'array' || gettype($ispconfigUri) === 'array'){
        throw new Exception('Parameters -u, --username, -p, --password, -l and --uri can only be provided ONCE! Aborting');
    }


    /**
     * Initialize the connection to ISPConfig Once
     */
    $ispconfigSoap = new ISPConfigSoap($ispconfigUri, $username, $password);

    foreach($domains as $domain) { 
        /**
         * Load SSL Informations into variables and check if an update is required
         */
        $cert     = file_get_contents($acmeshLocation . '/' . $domain . '/' . $domain . '.cer');
        $privkey  = file_get_contents($acmeshLocation . '/' . $domain . '/' . $domain . '.key');
        $bundle   = file_get_contents($acmeshLocation . '/' . $domain . '/fullchain.cer');
        echo 'Loading certificate';

        // Check if certificate has been loaded successfully
        if(!isset($cert) || !isset($privkey) || !isset($bundle)){
            throw new Exception('No SSL Certificate could be found for \'' . $domain . '\' please make sure the certificates are inside the /etc/acmesh/%domain% folder.');
        }

        // Read certificate and load all applicable domains
        $sslCert = openssl_x509_parse($cert);
        preg_match_all('/DNS:([*a-zA-Z0-9\.-]+)/', $sslCert['extensions']['subjectAltName'], $certDomains);
        echo 'Found certificate valid for ' . $sslCert['extensions']['subjectAltName'];

        /**
         * Load all domains this change applies to ()
        */

        // Lookup all domains inside ispconfig this domain change would apply to
        $changedDomains = array();
        foreach($certDomains[1] as $certDomain) {
            $changedDomains = array_merge($changedDomains, $ispconfigSoap->loadUpdateableDomains(str_replace('*', '%', $certDomain), $cert));
        }

        /**
         * Update all domains this change applies to with new SSL Data
         */
        foreach($changedDomains as $key => $value) {
            $ispconfigSoap->updateDomainData($value['domain_id'], $value['client_id'], array(
                'ssl_cert' => $cert,
                'ssl_bundle' => $bundle,
                'ssl_key' => $privkey
            ));
            echo 'Updated SSL Certificate for domain \'' . $key . '\'' . PHP_EOL;
        }
    }

    // If enabled, also update SSL certificate in Database
    if(isset($arguments['s']) || isset($arguments['service'])){
        $database = isset($arguments['s']) ? $arguments['s'] : $arguments['service'];

        if(gettype($database) === 'array') {
            foreach($database as $service) {
                shell_exec(str_replace('%service%', $service, $reloadCmd));
            }
        } else {
            shell_exec(str_replace('%service%', $database, $reloadCmd));
        }
    }
    exit(0);
} catch(Exception $e) {
    mail('root@localhost', 'Acme.SH-ISPConfig integration error', 'An error occured while attempting to update SSL certificates: \n' . $e->getMessage());
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    exit(1);
} finally {
    $ispconfigSoap->logoutSoapClient();
}
?>