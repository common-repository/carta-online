<?php

/**
 * co_api short summary.
 *      Class voor het aanroepen en cachen van de Carta Online API
 *
 * co_shortcode_teacher description.
 *      Gebruikt de settings API Key, Portal Address en cache timeout om de API aan te roepen.
 *
 * @author Hans
 */
class CartaOnlineApi
{
    var $_apiKey;
    var $_portalAddress;
    var $_cacheTimeout;
    var $_apiCallTimeout; 
    var $_graceperiodTimeout;
    var $lastError;

    function __construct()
    {
        global $CO_COOKIE;

        // Get settings from Carta Online Plugin Configuration page.
        // see $(plugins)/carta-online/admin/cataonline-admin.php
        $options = get_option('co_settings');
        if (!isset($options)) {
            $options = array();
        }

        if (co_isset(co_safe_array_get($options,'co_api_key')) && !(co_safe_array_get($_SESSION, "co_test") == true)) {
            $this->_apiKey = $options['co_api_key'];
        } else {
            if (co_isset(co_safe_array_get($CO_COOKIE,'CO_API_KEY'))) {
                $this->_apiKey = $CO_COOKIE['CO_API_KEY'];
            }
        }
        if (co_isset(co_safe_array_get($options,'co_portal_address')) && !(co_safe_array_get($_SESSION, "co_test") == true)) {
            $this->_portalAddress = $options['co_portal_address'];
        } else {
            if (co_isset(co_safe_array_get($CO_COOKIE,'CO_API_URL'))) {
                $this->_portalAddress = $CO_COOKIE['CO_API_URL'];
            }
        }
        $this->_cacheTimeout = co_safe_array_get($options, 'co_cache_timeout');
        if (!isset($this->_cacheTimeout)) {
            $this->_cacheTimeout = 10; // Minutes
        }
        $this->_apiCallTimeout = co_safe_array_get($options, 'co_api_timeout');
        if (!isset($this->_apiCallTimeout)) {
            $this->_apiCallTimeout = 30; // Seconds
        }        
        $this->_graceperiodTimeout = co_safe_array_get($options, 'co_grace_period_timeout');
        if (!isset($this->_graceperiodTimeout)) {
            $this->_graceperiodTimeout = 30; // Minutes
        }
        if ($this ->_graceperiodTimeout < $this->_cacheTimeout) {
            $this->_graceperiodTimeout = $this->_cacheTimeout + $this -> _graceperiodTimeout;
        }   
    }

    public function getPortalVersion()
    {
        $function = 'settings/AssemblyVersion';
        $returnValue = $this->callApiDirect($function, 'GET', null, null);
        if ($returnValue == null) {
            return "24.3.0.0";
        }
        return $returnValue -> result -> value;        
    }

    public function getCredits($offerIdentifier)
    {
        $function = 'offer/' . $offerIdentifier . '/accreditations';
        $returnValue = $this->callApiDirect($function, 'GET', null, null);
        return $returnValue;
    }

    public function getOfferings($numberOfItems, $filter, $filterCallback = null)
    {
        $function = 'offer';
        $returnValue = $this->callApiPaged($function, 'POST', $numberOfItems, $filter, $filterCallback);
        return $returnValue;
    }

    public function getOfferingsV2($numberOfItems, $filter, $filterCallback = null)
    {
        $function = 'offer';
        $returnValue = $this->callApiPaged($function, 'POST', $numberOfItems, $filter, $filterCallback, 'v2');
        return $returnValue;
    }

    public function getForms($numberOfItems)
    {
        $function = 'form';
        $returnValue = $this->callApiPaged($function, 'GET', $numberOfItems, null);
        return $returnValue;
    }

    public function getDocuments($classIdentifier)
    {
        $function = "offer/" . $classIdentifier . "/publicdocuments";
        $returnValue = $this->callApiDirect($function, 'GET', null, null);
        return $returnValue;
    }

    /// Returns a list of all Teachers subscribed in this $identifier (Class or Course)
    ///
    public function getTeacherlist($numberOfItems, $offerIdentifier)
    {
        return $this->getParticipantsList($numberOfItems, $offerIdentifier, '{ "role" : "Teacher" }');
    }

    /// Returns a list of all Employees subscribed in this $identifier (Class or Course)
    ///
    public function getContactlist($numberOfItems, $offerIdentifier)
    {
        return $this->getParticipantsList($numberOfItems, $offerIdentifier, '{ "role" : "StaffMember" }');
    }

    /// Returns a list of all participants.
    ///  $identifier = Class or Course (e.g. cl34, co23)
    ///  $filter = null or $filter = { "role" : "<role>" }
    ///                             <role> = [All, Trainee, Teacher, StaffMember, CompanyContact, Performer]
    public function getParticipantsList($numberOfItems, $offerIdentifier, $filter)
    {
        $function = 'offer/' . $offerIdentifier . '/participants';
        if (!isset($filter)) {
            $filter = '{ "role" : "All" }';
        }
        $returnValue = $this->callApiDirect($function, 'POST', $numberOfItems, $filter);
        return $returnValue;
    }

    /// Returns a person object
    public function getPerson($Identifier)
    {
        $function = 'person/' . $Identifier;
        $returnValue = $this->callApiDirect($function, 'GET', 1, null);
        return $returnValue;
    }

    public function getOfferDetail($classID)
    {
        $function = 'offer/' . $classID;
        $returnValue = $this->callApiDirect($function, 'GET', 1, null);

        return $returnValue;
    }

    public function getCompanyList()
    {
        $function = 'company/qualification';
        $returnValue = $this->callApiDirect($function, 'GET', 1, null);

        return $returnValue;
    }

    public function getexpertiseList()
    {
        $function = 'stock/fields';

        $returnValue = $this->callApiDirect($function, 'GET', 1, null);

        if (isset($returnValue) && ($returnValue->status == 200))
            return $returnValue->result;
        else
            return null;
    }

    //�RegistrationNumber(string)�, �ProfessionalOrganization(int)� �Scheme(int)� zijn
    function createRegisterFilter($regNumber, $organization, $scheme)
    {
        $filter = array(
            'RegistrationNumber' => $regNumber,
            'ProfessionalOrganization' => $organization,
            'Schema' => $scheme
        );
        return json_encode($filter);
    }

    public function getIsRegistered($numberOfItems, $RegistrationNumber, $organization, $scheme)
    {
        $function = 'person/qualification';
        if (!isset($RegistrationNumber)) {
            $filter = "";
        } else {
            $filter = $this->createRegisterFilter($RegistrationNumber, $organization, $scheme);
        }
        $returnValue = $this->callApiDirect($function, 'POST', $numberOfItems, $filter);
        return $returnValue;
    }

    protected function callApiDirect($functionName, $functionType, $numberOfItems, $filter, $version = 'v1')
    {
        if (!isset($filter)) {
            $Url = $this->_portalAddress . '/api/' . $version . '/' . $functionName;
            $filter = "{}";
        } else {
            $Url = $this->_portalAddress . '/api/' . $version . '/' . $functionName . '/filter';
        }

        $data = $this->callApi($Url, $functionType, $filter);
        return $data;
    }

    protected function callApiDirect2($functionName, $functionType, $content, $version = 'v1')
    {
        $Url = $this->_portalAddress . '/api/' . $version . '/' . $functionName;

        $data = $this->callApi($Url, $functionType, $content);
        return $data;
    }

    protected function callApiPaged($functionName, $functionType, $numberOfItems, $filter, $filterCallback = null, $version = 'v1')
    {
        if (!isset($numberOfItems) || $numberOfItems === 'ALL' || $numberOfItems < 1) {
            if ($numberOfItems === 'ALL') {
                $numberOfItems = PHP_INT_MAX;
            } else {
                $numberOfItems = '5';
            }
        }
        $page = '0';
        $pageSize = $numberOfItems;
        if (intval($pageSize) > 1000) {
            $pageSize = '1000';
        }

        $collection = [];
        if (!isset($filter)) {
            $nextUrl = $this->_portalAddress . '/api/' . $version . '/' . $functionName . '?page=' . $page . '&pageSize=' . $pageSize . '';
            $filter = "{}";
        } else {
            $nextUrl = $this->_portalAddress . '/api/' . $version . '/' . $functionName . '/filter?page=' . $page . '&pageSize=' . $pageSize . '';
        }

        while (isset($nextUrl) && (count($collection) < intval($numberOfItems))) {
            $data = $this->callApi($nextUrl, $functionType, $filter);
            if ($data == null) {
                // Error occured during API call
                return null;
            }

            unset($nextUrl);
            if ($version == 'v2') {
                if (isset($data)) {
                    $collection = array_merge($collection, $data->collection);
                    if (count($collection) >= $numberOfItems) {
                        while (count($collection) > $numberOfItems) {
                            array_pop($collection);
                        }
                        unset($nextUrl);
                    } else {
                        $nextUrl = $data->nextPageLink;
                    }
                }

            } else if (isset($data) && isset($data->result)) {
                $filteredResult = $this->filterCollection($data->result->collection, $filterCallback);
                $collection = array_merge($collection, $filteredResult);
                if (count($collection) >= $numberOfItems) {
                    while (count($collection) > $numberOfItems) {
                        array_pop($collection);
                    }
                    unset($nextUrl);
                } else {
                    $nextUrl = $data->result->nextPageLink;
                }
            } else {
                unset($nextUrl);
            }
        }
        if ($version == 'v2') {
            if (isset($data)) {
                $data->collection = $collection;
                return $data;
            }
            return null;
        }
        return $collection;
    }

    protected function filterCollection($collection, $filterCallback)
    {
        $result = [];
        if (isset($filterCallback)) {
            foreach ($collection as $item) {
                // Callback function must be specified as array($object, 'function')
                $isFiltered = call_user_func($filterCallback, $item);
                if ($isFiltered === true) {
                    array_push($result, $item);
                }
            }
            return $result;
        } else {
            return $collection;
        }
    }

    private function callApi($url, $method, $body)
    {
        $cacheIsValid  = false;
        $cacheKey = md5($url . $method . $body);
        
        //Check if a usable cachefile is available
        $cache_result = $this->get_cache($cacheKey);

        //Check if cachefile is not outdated
        if (isset($cache_result)) 
        {    
            // If an api call is already initiated, return the old cache while we can...
            // Because $cache_result is present we know we are within the grace period.
            if ($this->CheckSemaphore($cacheKey))                
            {
                $cacheIsValid = true;
            }
            else
            {
                // Check if cache should be refreshed
                $cacheIsValid = $this->is_cache_valid($cache_result, $this->_cacheTimeout);
            }
        }
        else
        {
            $cacheIsValid = false;
        }

        if ($cacheIsValid  === false) 
        {
            // Signal: We are executing an API call to refresh the cache
            $this->AcquireSemaphore($cacheKey);
            try
            {
                $curl = $this->intit_apicall($url, $method, $body);
            
                $result = curl_exec($curl);

                // Check the return value of curl_exec(), too
                if ($result === false)
                {
                    // Error in API call
                    if (isset($cache_result)) 
                    {
                        // Return cache if still in grace period
                        $cacheIsValid = $this->is_cache_valid($cache_result, $this->_graceperiodTimeout);

                        if ($cacheIsValid)
                        {
                            return $cache_result;
                        }
                    
                    }
                    // return error
                    $errorDescription = curl_error($curl);
                    $errorCode        = curl_errno($curl);
                    
                    throw new Exception($errorDescription, $errorCode);
                }

                // process API response
                $json_result = json_decode($result);

                $httpcode = intval(curl_getinfo($curl, CURLINFO_HTTP_CODE));

                if (($httpcode >= 200 && $httpcode < 300))
                {
                    if (!isset($json_result->timeStamp))
                    {
                        $json_result->timeStamp = (new DateTime())->format("c");
                    }
                    // Update the cache
                    $this -> lastError = null;
                    $this->save_cache($json_result, $cacheKey);
                } 
                else
                {
                    if (isset($json_result))
                    {
                        if ($httpcode !== 404)
                        {
                            co_report_error($httpcode . $json_result->message);
                            $this->lastError = $json_result->message;
                        }
                        else
                        {
                            // Save 404 to chache, but only for a short time
                            $this->save_cache($json_result, $cacheKey);
                        }
                    }
                    $json_result = null;
                }

                curl_close($curl);

                return $json_result;
            }
            finally
            {
                $this->ReleaseSemaphore($cacheKey);
            }
        } 
        else
        {
            return $cache_result;
        }
    }

    private function is_cache_valid($cache_result, $timeout)
    {
        // Check if cache is currupted 
        if (!isset($cache_result)) return false;
        if (!property_exists($cache_result, 'timeStamp')) return false;
        if (!property_exists($cache_result, 'status')) return false;

        $currentDatetime = new DateTime(); // Create a DateTime object for the current time
        $cacheDatetime   = new DateTime($cache_result -> timeStamp); 
        
        $interval = $currentDatetime->diff($cacheDatetime);

        $minutes  = $interval->days * 24 * 60;
        $minutes += $interval->h * 60;
        $minutes += $interval->i;

        if ($minutes > 0) {
            // Only 200 states are preserved according to settings. Otherwise we keep the cache onlye for a short time.
            if ($cache_result -> status != 200) {
                if ($cache_result -> status != 404) {
                    $this -> lastError = $cache_result -> message;
                }
                return false;
            } else {
                $this ->lastError = null;
            }
            if (property_exists($cache_result, 'result')) {
                // Also empty results might be caused by a temporary failure. So we keep the cache for a short time.
                if ($cache_result->result == null) return false;

                // Check if $cache_result->result is an object or array
                if (is_object($cache_result->result)) {
                    if (property_exists($cache_result->result, 'collection')) {
                        if (is_array($cache_result->result->collection) && count($cache_result->result->collection) == 0) {
                            return false;
                        }
                    }
                } elseif (is_array($cache_result->result)) {
                    if (array_key_exists('collection', $cache_result->result)) {
                        if (is_array($cache_result->result['collection']) && count($cache_result->result['collection']) == 0) {
                            return false;
                        }
                    }
                }
            }
        }

        if ($minutes > $timeout) return false;
        
        return true;
    }

    private function ReleaseSemaphore($semaphorename)
    {
        // File semaphore to prevent multiple API calls
        $filename = $this -> cache_path() . $semaphorename . '.api';
        if (file_exists($filename))
            unlink($filename);
    }

    private function CheckSemaphore($semaphorename)
    {
        // File semaphore to prevent multiple API calls
        $filename = $this -> cache_path() . $semaphorename . '.api';
        return file_exists($filename);
    }

    private function AcquireSemaphore($semaphorename)
    {
        // File semaphore to prevent multiple API calls
        $filename = $this -> cache_path() . $semaphorename . '.api';
        $fp = @fopen($filename, 'w');
        if ($fp) {
            fwrite($fp, "-");
            fclose($fp);
        }
        else {
            throw new Exception(__("carta-online/cache folder is not writable", "carta-online"));
        }
    }
    
    private function intit_apicall($url, $method, $body)
    {
        $headr = array('Authorization: API-Key ' . $this->_apiKey);

        $curl = curl_init();
        
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->_apiCallTimeout);

        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POST, "POST");
            if (isset($body)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                array_push($headr, 'Content-Type: application/json', 'Content-Length: ' . strlen($body));
            }
        }
        
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headr);

        return $curl;
    }

    // Return path to the cache directory
    private function cache_path()
    {
        // plugin_dir_path. Defined in carta_online.php
        $path = CO_PLUGIN_PATH . 'cache/';
        return $path;   
    }

    // Clear the carta_online API cache 
    public function clear_cache() 
    {
        // Construct the full path to the cache files
        $filename = $this -> cache_path() . '*.cache';
        
        try {
            // Use glob to get all .cache files in the specified directory
            $files = glob($filename);
            
            // Loop through each file and delete it
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        } catch (Exception $exception) {
            // If there's any error, display a user-friendly message with details
            echo __("Carta Online Plugin configuration error:") . " " . $exception->getMessage();
        }
    }

    private function save_cache($result, $cacheKey)
    {
        $filename =  $this -> cache_path() . $cacheKey . '.cache';
        try {
            $fp = @fopen($filename, 'w');
            if ($fp) {
                fwrite($fp, json_encode($result));
            } else {
                throw new Exception(__("carta-online/cache folder is not writable", "carta-online"));
            }
        } catch (Exception $exception) {
            echo __("Carta Online Plugin configuration error:") . " " . $exception->getMessage();
        }
    }

    // Get cache. Returns null if cache is invalid (older than grace-period)
    private function get_cache($cacheKey)
    {
        $filename =$this -> cache_path() . $cacheKey . '.cache';
        try {
            if (file_exists($filename)) {
                $json = json_decode(file_get_contents($filename));

                // if the cache file is older then grace periode timeout then it can be removed.
                $cacheIsValid = $this->is_cache_valid($json, $this->_graceperiodTimeout);
                if (isset($json) && $cacheIsValid) 
                {               
                    // cache is still valid. Return contents.
                    return $json;
                }
                // Cache is not valid anymore. Remove file.
                unlink($filename);
            }
        } catch (Exception $exception) {
            echo __("Carta Online Plugin configuration error:") . " " . $exception->getMessage();
        }
        return null;
    }
}

function register_co_session()
{
    global $CO_GET;
    global $CO_POST;
    global $CO_COOKIE;

    if (session_status() !== PHP_SESSION_ACTIVE) {
        $sessionCacheLimiter = co_get_option('co_session_cache_limiter');

        if (isset($sessionCacheLimiter)) {
            header('Cache-Control: no cache');
            session_cache_limiter('private_no_expire');
        }
        session_start();
    }

    $CO_GET = array_map('stripslashes_deep', $_GET);
    $CO_POST = array_map('stripslashes_deep', $_POST);
    $CO_COOKIE = array_map('stripslashes_deep', $_COOKIE);
}

add_action('init', 'register_co_session');