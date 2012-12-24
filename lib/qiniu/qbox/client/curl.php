<?php

/**
 * HTTP Methods
 */
define('QBOX_HTTP_METHOD_GET',    'GET');
define('QBOX_HTTP_METHOD_POST',   'POST');
define('QBOX_HTTP_METHOD_PUT',    'PUT');
define('QBOX_HTTP_METHOD_DELETE', 'DELETE');
define('QBOX_HTTP_METHOD_HEAD',   'HEAD');

/**
 * HTTP Form content types
 */
define('QBOX_HTTP_FORM_CONTENT_TYPE_APPLICATION', 0);
define('QBOX_HTTP_FORM_CONTENT_TYPE_MULTIPART', 1);

/**
 * Build a url from some url parsed parts
 */
function QBox_BuildURL($parsed) {
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

function QBox_ExecuteRequest(
    $url,
    array $parameters = array(),
    $http_method = QBOX_HTTP_METHOD_GET,
    $http_headers = null,
    $form_content_type = QBOX_HTTP_FORM_CONTENT_TYPE_MULTIPART,
    $curl_extra_options = null,
    $i = 0
    )
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
        case QBOX_HTTP_METHOD_POST:
            $curl_options[CURLOPT_POST] = true;
            /* No break */
        case QBOX_HTTP_METHOD_PUT:
            /**
             * Passing an array to CURLOPT_POSTFIELDS will encode the data as multipart/form-data,
             * while passing a URL-encoded string will encode the data as application/x-www-form-urlencoded.
             * http://php.net/manual/en/function.curl-setopt.php
             */
            if (!isset($curl_options[CURLOPT_UPLOAD])) {
                if (QBOX_HTTP_FORM_CONTENT_TYPE_APPLICATION === $form_content_type) {
                    $parameters = http_build_query($parameters);
                }
                $curl_options[CURLOPT_POSTFIELDS] = $parameters;
            }
            break;
        case QBOX_HTTP_METHOD_HEAD:
            $curl_options[CURLOPT_NOBODY] = true;
            /* No break */
        case QBOX_HTTP_METHOD_DELETE:
        case QBOX_HTTP_METHOD_GET:
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
            $curl_options[CURLOPT_URL] = QBox_BuildURL($parsed);
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
    if ($errno > 0) {
        $i += 1;
        $n = $n > 2 ? 1 : $n + 1;
        if ($i < 3) {
            $domains[0] = ($n == 1) ? $prefix : ($prefix . $n);
            $newhost = implode('.', $domains);
            $parsed["host"] = $newhost;
            $newurl = QBox_BuildURL($parsed);
            return QBox_ExecuteRequest($newurl, $parameters, $http_method, $http_headers, $form_content_type, $curl_extra_options, $i);
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
