<?php
/**
 * Communicate with the API model layer
 */
class Rest_Requestor
{
    /**
     * Get data from the API
     * 
     * @param string $method
     * @param string $url
     * @return array
     */
    public static function apiRequest($method = 'GET', $url = '')
    {
        $cr = curl_init();
        curl_setopt($cr, CURLOPT_URL, 'http://localhost' . $url);
        curl_setopt($cr, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($cr, CURLOPT_HTTPHEADER ,array('Accept:application/json'));
        curl_setopt($cr, CURLOPT_TIMEOUT, 2);
        curl_setopt($cr, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cr, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($cr, CURLOPT_USERPWD, 'Alex:admin');
        $json = curl_exec($cr);

        return json_decode($json, true);
    }
}