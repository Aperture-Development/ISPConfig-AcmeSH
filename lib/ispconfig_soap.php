<?php
/* *
 * ISPConfig Simple SSL Soap Client
 * 
 * Description: Allows easy changes to SSL Certificates of ISPConfig Websites
 * 
 * @author Aperture Development <developers@aperture-Development.de>
 * @version 0.0.2
 * @license by-sa 4.0
*/

class ISPConfigSoap {
    private $client = null; 
    private $session_id = null;

    /**
     * Class Constructor
     *
     * Description: Connects the soap client to the ISPConfig API
     * 
     * @param string $uri The ISPConfig URI to connect to
     * @param string $username The ISPConfig Soap Username
     * @param string $password The ISPConfig Soap Password
     * 
     * @author     Aperture Development <developers@aperture-Development.de>
     * @copyright  2021 Aperture Development
     */
    function __construct(string $uri, string $username, string $password){
        $context = stream_context_create(array(
            'ssl' => array(
                'verify_peer'      => false,
                'verify_peer_name' => false,
            )
        ));
        
        $this->client = new SoapClient(null, array(
            'location'       => $uri . 'index.php',
            'uri'            => $uri,
            'trace'          => 1,
            'exceptions'     => 1,
            'stream_context' => $context
        ));

        if ($this->session_id = $this->client->login($username, $password)) {
            echo 'Logged into ISPconfig, SessionID: ' . $this->session_id . PHP_EOL;
        } else {
            throw new Exception('Login failed, aborting execution');
        }
    }

    /**
     * loadUpdateableDomains
     *
     * Description: Loads all avaiable domains for SSL update. If the domain is already updated, it will skip. 
     * 
     * @param string $domain The root domain to check for in all webspaces
     * @param string $certificate The new certificate to insert into the domain
     * 
     * @return array Array that holds all domains where a SSL update is applicable
     * 
     * @author     Aperture Development <developers@aperture-Development.de>
     * @copyright  2021 Aperture Development
     */
    function loadUpdateableDomains(string $domain, string $certificate){
        if(!$this->session_id) {throw new Exception('No session ID was Set, aborting execution!' . PHP_EOL . 'Please verify the username and password to log into ISPConfig');}
        $updateableDomains = array();
        $client_ids = array();

        $allDomains = $this->client->sites_web_domain_get($this->session_id, array('domain' => $domain, 'active' => 'y'));

        foreach($allDomains as $val) {
            if($val['ssl'] === 'y'){
                if(md5(trim(str_replace('\r\n', '\n', $certificate))) == md5(trim(str_replace('\r\n', '\n', $val['ssl_cert'])))){
                    echo 'Certificate for \'' . $val['domain'] . '\' did not change, skipping...' . PHP_EOL;
                } else {
                    if(!isset($client_ids[$val['sys_userid']])) {
                        $client_ids[$val['sys_userid']] = $this->client->client_get_id($this->session_id, $val['sys_userid']);
                    }
                    $updateableDomains[$val['domain']] = array(
                        'domain_id' => $val['domain_id'],
                        'client_id' => $client_ids[$val['sys_userid']]
                    );
                    echo 'SSL certificate for \'' . $val['domain'] . '\' does not match, marking for update' . PHP_EOL;
                }
            }
        }

        return $updateableDomains;
    }

    /**
     * updateDomainData
     *
     * Description: Updates given domainid with the new sll informations
     * 
     * @param int $domain_id The ID of the domain to update
     * @param int $client_id The client ID of the domain to update
     * @param array $sslInformations The SSL informations to insert into the webspace
     * 
     * @author     Aperture Development <developers@aperture-Development.de>
     * @copyright  2021 Aperture Development
     */
    function updateDomainData(int $domain_id, int $client_id, array $sslInformations){
        if(!$this->session_id) {throw new Exception('No session ID was Set, aborting execution!' . PHP_EOL . 'Please verify the username and password to log into ISPConfig');}
        try {
            $this->client->sites_web_domain_update($this->session_id, $client_id, $domain_id, array(
                'ssl_cert' => $sslInformations['ssl_cert'],
                'ssl_bundle' => $sslInformations['ssl_bundle'],
                'ssl_key' => $sslInformations['ssl_key'],
                'ssl_action' => 'save'
            ));
        } catch(SoapFault $e) {
            throw $e;
        }
    }

    /**
     * logoutSoapClient
     *
     * Description: Logs out the ISPConfig Soap client
     * 
     * @return I have no idea tbh
     * 
     * @author     Aperture Development <developers@aperture-Development.de>
     * @copyright  2021 Aperture Development
     */
    function logoutSoapClient() {
        if(!$this->session_id) {throw new Exception('No session ID was Set, aborting execution!' . PHP_EOL . 'Please verify the username and password to log into ISPConfig');}
        return $this->client->logout($this->session_id);
    }

    /**
     * Class Deconstructor
     *
     * Description: The class deconstructor logs out the Soap client before deconstructing the class
     * 
     * @return I have no idea tbh
     * 
     * @author     Aperture Development <developers@aperture-Development.de>
     * @copyright  2021 Aperture Development
     */
    function __destruct(){
        if(!$this->session_id) {throw new Exception('No session ID was Set, aborting execution!' . PHP_EOL . 'Please verify the username and password to log into ISPConfig');}
        $this->client->logout($this->session_id);
    }
}