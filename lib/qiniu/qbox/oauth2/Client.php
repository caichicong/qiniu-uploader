<?php
/**
 * Note : Code is released under the GNU LGPL
 *
 * Please do not change the header of this file
 *
 * This library is free software; you can redistribute it and/or modify it under the terms of the GNU
 * Lesser General Public License as published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * See the GNU Lesser General Public License for more details.
 */

/**
 * Light PHP wrapper for the OAuth 2.0 protocol.
 *
 * This client is based on the QBox_OAuth specification draft v2.15
 * http://tools.ietf.org/html/draft-ietf-oauth-v2-15
 *
 * @author      Pierrick Charron <pierrick@webstart.fr>
 * @author      Anis Berejeb <anis.berejeb@gmail.com>
 * @version     1.2-dev
 */

require_once('Exception.php');
require_once('GrantType/IGrantType.php');
require_once('GrantType/AuthorizationCode.php');
require_once('GrantType/ClientCredentials.php');
require_once('GrantType/Password.php');
require_once('GrantType/RefreshToken.php');

class QBox_OAuth_Client
{
    /**
     * Different AUTH method
     */
    const AUTH_TYPE_URI                 = 0;
    const AUTH_TYPE_AUTHORIZATION_BASIC = 1;
    const AUTH_TYPE_FORM                = 2;

    /**
     * Different Access token type
     */
    const ACCESS_TOKEN_URI      = 0;
    const ACCESS_TOKEN_BEARER   = 1;
    const ACCESS_TOKEN_OAUTH    = 2;
    const ACCESS_TOKEN_MAC      = 3;
    const ACCESS_TOKEN_QBOX	= 4;
    /**
    * Different Grant types
    */
    const GRANT_TYPE_AUTH_CODE          = 'authorization_code';
    const GRANT_TYPE_PASSWORD           = 'password';
    const GRANT_TYPE_CLIENT_CREDENTIALS = 'client_credentials';
    const GRANT_TYPE_REFRESH_TOKEN      = 'refresh_token';

    /**
     * HTTP Methods
     */
    const HTTP_METHOD_GET    = 'GET';
    const HTTP_METHOD_POST   = 'POST';
    const HTTP_METHOD_PUT    = 'PUT';
    const HTTP_METHOD_DELETE = 'DELETE';
    const HTTP_METHOD_HEAD   = 'HEAD';

    /**
     * HTTP Form content types
     */
    const HTTP_FORM_CONTENT_TYPE_APPLICATION = 0;
    const HTTP_FORM_CONTENT_TYPE_MULTIPART = 1;

    public $access_token_bearer = self::ACCESS_TOKEN_BEARER;
	public $access_token_qbox = self::ACCESS_TOKEN_QBOX;
    public $http_method_post = self::HTTP_METHOD_POST;
    public $http_form_content_type_application = self::HTTP_FORM_CONTENT_TYPE_APPLICATION;

    /**
     * Client ID
     *
     * @var string
     */
    protected $client_id = null;

    /**
     * Client Secret
     *
     * @var string
     */
    protected $client_secret = null;

    /**
     * Client Authentication method
     *
     * @var int
     */
    protected $client_auth = self::AUTH_TYPE_URI;

    /**
     * Access Token
     *
     * @var string
     */
    protected $access_token = null;

    /**
     * Access Token Type
     *
     * @var int
     */
    protected $access_token_type = self::ACCESS_TOKEN_URI;

    /**
     * Access Token Secret
     *
     * @var string
     */
    protected $access_token_secret = null;

    /**
     * Access Token crypt algorithm
     *
     * @var string
     */
    protected $access_token_algorithm = null;

    /**
     * Access Token Parameter name
     *
     * @var string
     */
    protected $access_token_param_name = 'access_token';

    /**
     * The path to the certificate file to use for https connections
     *
     * @var string  Defaults to .
     */
    protected $certificate_file = null;

    /**
     * Construct
     *
     * @param string $client_id Client ID
     * @param string $client_secret Client Secret
     * @param int    $client_auth (AUTH_TYPE_URI, AUTH_TYPE_AUTHORIZATION_BASIC, AUTH_TYPE_FORM)
     * @param string $certificate_file Indicates if we want to use a certificate file to trust the server. Optional, defaults to null.
     * @return void
     */
    public function __construct($client_id, $client_secret, $client_auth = self::AUTH_TYPE_URI, $certificate_file = null)
    {
        if (!extension_loaded('curl')) {
            throw new QBox_OAuth_Exception('The PHP exention curl must be installed to use this library.', QBox_OAuth_Exception::CURL_NOT_FOUND);
        }

        $this->client_id     = $client_id;
        $this->client_secret = $client_secret;
        $this->client_auth   = $client_auth;
        $this->certificate_file = $certificate_file;
        if (!empty($this->certificate_file)  && !is_file($this->certificate_file)) {
            throw new QBox_OAuth_InvalidArgumentException('The certificate file was not found', QBox_OAuth_InvalidArgumentException::CERTIFICATE_NOT_FOUND);
        }
    }

    /**
     * Get the client Id
     *
     * @return string Client ID
     */
    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * Get the client Secret
     *
     * @return string Client Secret
     */
    public function getClientSecret()
    {
        return $this->client_secret;
    }

    /**
     * getAuthenticationUrl
     *
     * @param string $auth_endpoint Url of the authentication endpoint
     * @param string $redirect_uri  Redirection URI
     * @param array  $extra_parameters  Array of extra parameters like scope or state (Ex: array('scope' => null, 'state' => ''))
     * @return string URL used for authentication
     */
    public function getAuthenticationUrl($auth_endpoint, $redirect_uri, array $extra_parameters = array())
    {
        $parameters = array_merge(array(
            'response_type' => 'code',
            'client_id'     => $this->client_id,
            'redirect_uri'  => $redirect_uri
        ), $extra_parameters);
        return $auth_endpoint . '?' . http_build_query($parameters, null, '&');
    }

    /**
     * getAccessToken
     *
     * @param string $token_endpoint    Url of the token endpoint
     * @param int    $grant_type        Grand Type ('authorization_code', 'password', 'client_credentials', 'refresh_token', or a custom code (@see GrantType Classes)
     * @param array  $parameters        Array sent to the server (depend on which grant type you're using)
     * @return array Array of parameters required by the grant_type (CF SPEC)
     */
    public function getAccessToken($token_endpoint, $grant_type, array $parameters)
    {
        if (!$grant_type) {
            throw new QBox_OAuth_InvalidArgumentException('The grant_type is mandatory.', QBox_OAuth_InvalidArgumentException::INVALID_GRANT_TYPE);
        }
        $grantTypeClassName = $this->convertToCamelCase($grant_type);
        $grantTypeClass = 'QBox_OAuth_GrantType_' . $grantTypeClassName;
        if (!class_exists($grantTypeClass)) {
            throw new QBox_OAuth_InvalidArgumentException('Unknown grant type \'' . $grant_type . '\'', QBox_OAuth_InvalidArgumentException::INVALID_GRANT_TYPE);
        }
        $grantTypeObject = new $grantTypeClass();
        $grantTypeObject->validateParameters($parameters);
        if (!defined($grantTypeClass . '::GRANT_TYPE')) {
            throw new QBox_OAuth_Exception('Unknown constant GRANT_TYPE for class ' . $grantTypeClassName, QBox_OAuth_Exception::GRANT_TYPE_ERROR);
        }
        #$parameters['grant_type'] = $grantTypeClass::GRANT_TYPE;
        $grantTypeObj = new $grantTypeClass();
        $parameters['grant_type'] = $grantTypeObj->grant_type;
        $http_headers = array();
        switch ($this->client_auth) {
            case self::AUTH_TYPE_URI:
            case self::AUTH_TYPE_FORM:
                $parameters['client_id'] = $this->client_id;
                $parameters['client_secret'] = $this->client_secret;
                break;
            case self::AUTH_TYPE_AUTHORIZATION_BASIC:
                $parameters['client_id'] = $this->client_id;
                $http_headers['Authorization'] = 'Basic ' . base64_encode($this->client_id .  ':' . $this->client_secret);
                break;
            default:
                throw new QBox_OAuth_Exception('Unknown client auth type.', QBox_OAuth_Exception::INVALID_CLIENT_AUTHENTICATION_TYPE);
                break;
        }

        #return $this->executeRequest($token_endpoint, $parameters, self::HTTP_METHOD_POST, $http_headers, self::HTTP_FORM_CONTENT_TYPE_APPLICATION);
        return $this->executeRequestSafely($token_endpoint, $parameters, self::HTTP_METHOD_POST, $http_headers, self::HTTP_FORM_CONTENT_TYPE_APPLICATION);
    }

    /**
     * setToken
     *
     * @param string $token Set the access token
     * @return void
     */
    public function setAccessToken($token)
    {
        $this->access_token = $token;
    }

    /**
     * Set the client authentication type
     *
     * @param string $client_auth (AUTH_TYPE_URI, AUTH_TYPE_AUTHORIZATION_BASIC, AUTH_TYPE_FORM)
     * @return void
     */
    public function setClientAuthType($client_auth)
    {
        $this->client_auth = $client_auth;
    }

    /**
     * Set the access token type
     *
     * @param int $type Access token type (ACCESS_TOKEN_BEARER, ACCESS_TOKEN_MAC, ACCESS_TOKEN_URI)
     * @param string $secret The secret key used to encrypt the MAC header
     * @param string $algorithm Algorithm used to encrypt the signature
     * @return void
     */
    public function setAccessTokenType($type, $secret = null, $algorithm = null)
    {
        $this->access_token_type = $type;
        $this->access_token_secret = $secret;
        $this->access_token_algorithm = $algorithm;
    }

    /**
     * Fetch a protected ressource
     *
     * @param string $protected_ressource_url Protected resource URL
     * @param array  $parameters Array of parameters
     * @param string $http_method HTTP Method to use (POST, PUT, GET, HEAD, DELETE)
     * @param array  $http_headers HTTP headers
     * @param int    $form_content_type HTTP form content type to use
     * @return array
     */
    public function fetch($protected_resource_url, $parameters = array(), $http_method = self::HTTP_METHOD_GET, array $http_headers = array(), $form_content_type = self::HTTP_FORM_CONTENT_TYPE_MULTIPART)
    {
        if ($this->access_token) {
            switch ($this->access_token_type) {
                case self::ACCESS_TOKEN_URI:
                    if (is_array($parameters)) {
                        $parameters[$this->access_token_param_name] = $this->access_token;
                    } else {
                        throw new QBox_OAuth_InvalidArgumentException(
                            'You need to give parameters as array if you want to give the token within the URI.',
                            QBox_OAuth_InvalidArgumentException::REQUIRE_PARAMS_AS_ARRAY
                        );
                    }
		    break;
		case self::ACCESS_TOKEN_QBOX:
		    $http_headers['Authorization'] = 'QBox ' . $this->generateQBOXSignature($protected_resource_url,$parameters);
		    break;
                case self::ACCESS_TOKEN_BEARER:
                    $http_headers['Authorization'] = 'Bearer ' . $this->access_token;
                    break;
                case self::ACCESS_TOKEN_OAUTH:
                    $http_headers['Authorization'] = 'OAuth ' . $this->access_token;
                    break;
                case self::ACCESS_TOKEN_MAC:
                    $http_headers['Authorization'] = 'MAC ' . $this->generateMACSignature($protected_resource_url, $parameters, $http_method);
                    break;
                default:
                    throw new QBox_OAuth_Exception('Unknown access token type.', QBox_OAuth_Exception::INVALID_ACCESS_TOKEN_TYPE);
                    break;
            }
        }
        #return $this->executeRequest($protected_resource_url, $parameters, $http_method, $http_headers, $form_content_type);
        return $this->executeRequestSafely($protected_resource_url, $parameters, $http_method, $http_headers, $form_content_type);
    }



          /**
	   * Generate the QBOX signature
	   *
	   * @param string $url Called URL
	   * @param array  $parameters Parameters
	   */
    private function generateQBOXSignature($url,$parameters){
	$parsed_url = parse_url($url);
	$path = $parsed_url['path'];
	$data = $path;
	if (isset($parsed_url['query'])) {
		$data .= "?" . $parsed_url['query'];
	}
        $data .= "\n";

	if($parameters){
		if (is_array($parameters)){
			$parameters = http_build_query($parameters);									                       }
		$data .= $parameters;
	}
	$digest = QBox_Encode(hash_hmac('sha1', $data, $this->access_token_secret, true));
	$digest = $this->access_token . ":" .$digest;
	return $digest;
 }

    /**
     * Generate the MAC signature
     *
     * @param string $url Called URL
     * @param array  $parameters Parameters
     * @param string $http_method Http Method
     * @return string
     */
    private function generateMACSignature($url, $parameters, $http_method)
    {
        $timestamp = time();
        $nonce = uniqid();
        $query_parameters = array();
        $body_hash = '';
        $parsed_url = parse_url($url);
        if (!isset($parsed_url['port'])) {
            $parsed_url['port'] = ($parsed_url['scheme'] == 'https') ? 443 : 80;
        }

        if (self::HTTP_METHOD_POST === $http_method || self::HTTP_METHOD_PUT === $http_method) {
            if (is_array($parameters) && !empty($parameters)) {
                $body_hash = base64_encode(hash($this->access_token_algorithm, http_build_query($parameters)));
            } elseif ($parameters) {
                $body_hash = base64_encode(hash($this->access_token_algorithm, $parameters));
            }
        } else {
            if (!is_array($parameters)) {
                parse_str($parameters, $parameters);
            }
            foreach ($parameters as $key => $parsed_urlvalue) {
                $query_parameters[] = rawurlencode($key) . '=' . rawurlencode($parsed_urlvalue);
            }
            sort($query_parameters);
        }

        $signature = base64_encode(hash_hmac($this->access_token_algorithm,
            $this->access_token . "\n"
            . $timestamp . "\n"
            . $nonce . "\n"
            . $body_hash . "\n"
            . $http_method . "\n"
            . $parsed_url['host'] . "\n"
            . $parsed_url['port'] . "\n"
            . $parsed_url['path'] . "\n"
            . implode($query_parameters, "\n")
            , $this->access_token_secret));

        return 'token="' . $this->access_token . '", timestamp="' . $timestamp . '", nonce="' . $nonce . '", signature="' . $signature . '"';
    }

    /**
     * Build a url from some url parsed parts
     */
    private function buildURL($parsed) {
        $url = '';
        if (isset($parsed["scheme"]) && isset($parsed["host"])) {
            $url = $parsed["scheme"] . "://" . $parsed["host"];
            if (isset($parsed["port"])) {
                $url .= ":" . $parsed["port"];
            }
            if (isset($parsed["path"])) {
                $url .= $parsed["path"];
            }
            if (isset($parsed["query"])) {
                $url .= '?' . $parsed["query"];
            }
            if (isset($parsed["fragment"])) {
                $url .= '#' . $parsed["fragment"];
            }
        }
        return $url;
    }

    /**
     * Execute a request safely (with curl)
     *
     * @param string $url URL
     * @param mixed  $parameters Array of parameters
     * @param string $http_method HTTP Method
     * @param array  $http_headers HTTP Headers
     * @param int    $form_content_type HTTP form content type to use
     * @return array
     */
    private function executeRequestSafely($url, $parameters = '' /* array() */, $http_method = self::HTTP_METHOD_GET, $http_headers = null, $form_content_type = self::HTTP_FORM_CONTENT_TYPE_MULTIPART, $curl_extra_options = null, $i = 0)
    {
        $curl_options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CUSTOMREQUEST  => $http_method
        );
        if (!empty($curl_extra_options)) {
            foreach ($curl_extra_options as $k => $v)
                $curl_options[$k] = $v;
        }

        switch($http_method)
        {
            case self::HTTP_METHOD_POST:
                $curl_options[CURLOPT_POST] = true;
                /* No break */
            case self::HTTP_METHOD_PUT:
                /**
                 * Passing an array to CURLOPT_POSTFIELDS will encode the data as multipart/form-data,
                 * while passing a URL-encoded string will encode the data as application/x-www-form-urlencoded.
                 * http://php.net/manual/en/function.curl-setopt.php
                 */
                if (!isset($curl_options[CURLOPT_UPLOAD])) {
                    if (self::HTTP_FORM_CONTENT_TYPE_APPLICATION === $form_content_type) {
                        if (is_array($parameters))
                            $parameters = http_build_query($parameters);
                    }
                    $curl_options[CURLOPT_POSTFIELDS] = $parameters;
                }
                break;
            case self::HTTP_METHOD_HEAD:
                $curl_options[CURLOPT_NOBODY] = true;
                /* No break */
            case self::HTTP_METHOD_DELETE:
            case self::HTTP_METHOD_GET:
                $url .= '?' . http_build_query($parameters, null, '&');
                break;
            default:
                break;
        }

        if (is_array($http_headers))
        {
            $header = array();
            foreach($http_headers as $key => $parsed_urlvalue) {
                $header[] = "$key: $parsed_urlvalue";
            }
            $curl_options[CURLOPT_HTTPHEADER] = $header;
        }

        $curl_options[CURLOPT_URL] = $url;

        $parsed = parse_url($url);
        $domains = explode('.', $parsed['host']);
        $n = substr($domains[0], -1);
        if (is_numeric($n)) {
            $n = (int)$n;
            $prefix = substr($domains[0], 0, -1);
        } else {
            $n = 1;
            $prefix = $domains[0];
        }
        $tmphosts = sys_get_temp_dir() . DIRECTORY_SEPARATOR . '.qbox_' . $prefix;
        if (file_exists($tmphosts)) {
            if ($hoststr = file_get_contents($tmphosts)){
                $parsed["host"] = $hoststr;
                $curl_options[CURLOPT_URL] = $this->buildURL($parsed);
            }
        }

        $ch = curl_init();
        curl_setopt_array($ch, $curl_options);
        $result = curl_exec($ch);
        $errno = curl_errno($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        # retry, max request 3 times
        # acc, acc2, acc3
        if ($errno > 0) {
            $i += 1;
            $n = $n > 2 ? 1 : $n + 1;
            if ($i < 3) {
                $domains[0] = ($n == 1) ? $prefix : ($prefix . $n);
                $newhost = implode('.', $domains);
                $parsed["host"] = $newhost;
                $newurl = $this->buildURL($parsed);
                return $this->executeRequestSafely($newurl, $parameters, $http_method, $http_headers, $form_content_type, $curl_extra_options, $i);
            }
        }

        if ($i > 0 && $i < 3) {
            file_put_contents($tmphosts, $parsed['host'], LOCK_EX);
        }

        if ($content_type === "application/json") {
            $json_decode = json_decode($result, true);
        } else {
            $json_decode = null;
        }
        return array(
            'result' => (null === $json_decode) ? $result : $json_decode,
            'code' => $http_code,
            'content_type' => $content_type
        );
    }

    /**
     * Execute a request (with curl)
     *
     * @param string $url URL
     * @param mixed  $parameters Array of parameters
     * @param string $http_method HTTP Method
     * @param array  $http_headers HTTP Headers
     * @param int    $form_content_type HTTP form content type to use
     * @return array
     */
    private function executeRequest($url, $parameters = array(), $http_method = self::HTTP_METHOD_GET, array $http_headers = null, $form_content_type = self::HTTP_FORM_CONTENT_TYPE_MULTIPART)
    {
        $curl_options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CUSTOMREQUEST  => $http_method
        );

        switch($http_method) {
            case self::HTTP_METHOD_POST:
                $curl_options[CURLOPT_POST] = true;
                /* No break */
            case self::HTTP_METHOD_PUT:

                /**
                 * Passing an array to CURLOPT_POSTFIELDS will encode the data as multipart/form-data,
                 * while passing a URL-encoded string will encode the data as application/x-www-form-urlencoded.
                 * http://php.net/manual/en/function.curl-setopt.php
                 */
                if(is_array($parameters) && self::HTTP_FORM_CONTENT_TYPE_APPLICATION === $form_content_type) {
                    $parameters = http_build_query($parameters);
                }
                $curl_options[CURLOPT_POSTFIELDS] = $parameters;
                break;
            case self::HTTP_METHOD_HEAD:
                $curl_options[CURLOPT_NOBODY] = true;
                /* No break */
            case self::HTTP_METHOD_DELETE:
            case self::HTTP_METHOD_GET:
                if (is_array($parameters)) {
                    $url .= '?' . http_build_query($parameters, null, '&');
                } elseif ($parameters) {
                    $url .= '?' . $parameters;
                }
                break;
            default:
                break;
        }

        $curl_options[CURLOPT_URL] = $url;

        if (is_array($http_headers)) {
            $header = array();
            foreach($http_headers as $key => $parsed_urlvalue) {
                $header[] = "$key: $parsed_urlvalue";
            }
            $curl_options[CURLOPT_HTTPHEADER] = $header;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $curl_options);
        // https handling
        if (!empty($this->certificate_file)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_CAINFO, $this->certificate_file);
        } else {
            // bypass ssl verification
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
        }
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        if ($curl_error = curl_error($ch)) {
            throw new QBox_OAuth_Exception($curl_error, QBox_OAuth_Exception::CURL_ERROR);
        } else {
            $json_decode = json_decode($result, true);
        }
        curl_close($ch);

        return array(
            'result' => (null === $json_decode) ? $result : $json_decode,
            'code' => $http_code,
            'content_type' => $content_type
        );
    }

    /**
     * Set the name of the parameter that carry the access token
     *
     * @param string $name Token parameter name
     * @return void
     */
    public function setAccessTokenParamName($name)
    {
        $this->access_token_param_name = $name;
    }

    /**
     * Converts the class name to camel case
     *
     * @param  mixed  $grant_type  the grant type
     * @return string
     */
    private function convertToCamelCase($grant_type)
    {
        $parts = explode('_', $grant_type);
        $new_parts = array();
        foreach ($parts as $item) {
            $new_parts[] = ucfirst($item);
        }
        return implode('', $new_parts);
    }

}
