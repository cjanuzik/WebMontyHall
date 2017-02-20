<?php

/**
 * getDB
 *
 * The getDB function is a helper function that returns a connection to your
 * database. By storing a reference to the database, you should only need 
 * to create your new PDO object once. After that, you can simply test to 
 * see if your $db object contains a value and return it.
 */
function getDB() {
  global $db;
  
  //  Connect to your database
  if(empty($db)){
      $dsn = 'mysql:dbname=hw7;host=localhost';
      $db = new PDO($dsn);
  }
  
  return $db;
}


// Database Functions
//
// You are encouraged to write database functions here, rather than in your
// main logic flow. There are a lot of steps involved in setting up and 
// making database calls, so its usually nice to separate all that out to
// a separate file. You can more easily see and reference other database
// calls this way, as well as keep your main code much simpler, by just 
// making calls to getSomeData() etc.


/**
 * dbLastInsertID
 *
 * The dbLastInsertID helper function calls MySQL's LAST_INSERT_ID() function
 * to obtain the Primary Key of the last inserted row. This is how you can
 * discover the ID of the row you just inserted elsewhere.
 */
function dbLastInsertID() {
  $db = getDB();
  $stmt = $db->query("SELECT LAST_INSERT_ID()");
  $lastId = $stmt->fetch();
  $lastId = $lastId[0];
  return $lastId;
}

/**
 * http_response_code
 *
 * Older versions of PHP do not have this function built in, so it is replicated
 * here as a fallback.
 */
if (!function_exists('http_response_code')) {
    function http_response_code($code = NULL) {

        if ($code !== NULL) {

            switch ($code) {
                case 100: $text = 'Continue'; break;
                case 101: $text = 'Switching Protocols'; break;
                case 200: $text = 'OK'; break;
                case 201: $text = 'Created'; break;
                case 202: $text = 'Accepted'; break;
                case 203: $text = 'Non-Authoritative Information'; break;
                case 204: $text = 'No Content'; break;
                case 205: $text = 'Reset Content'; break;
                case 206: $text = 'Partial Content'; break;
                case 300: $text = 'Multiple Choices'; break;
                case 301: $text = 'Moved Permanently'; break;
                case 302: $text = 'Moved Temporarily'; break;
                case 303: $text = 'See Other'; break;
                case 304: $text = 'Not Modified'; break;
                case 305: $text = 'Use Proxy'; break;
                case 400: $text = 'Bad Request'; break;
                case 401: $text = 'Unauthorized'; break;
                case 402: $text = 'Payment Required'; break;
                case 403: $text = 'Forbidden'; break;
                case 404: $text = 'Not Found'; break;
                case 405: $text = 'Method Not Allowed'; break;
                case 406: $text = 'Not Acceptable'; break;
                case 407: $text = 'Proxy Authentication Required'; break;
                case 408: $text = 'Request Time-out'; break;
                case 409: $text = 'Conflict'; break;
                case 410: $text = 'Gone'; break;
                case 411: $text = 'Length Required'; break;
                case 412: $text = 'Precondition Failed'; break;
                case 413: $text = 'Request Entity Too Large'; break;
                case 414: $text = 'Request-URI Too Large'; break;
                case 415: $text = 'Unsupported Media Type'; break;
                case 500: $text = 'Internal Server Error'; break;
                case 501: $text = 'Not Implemented'; break;
                case 502: $text = 'Bad Gateway'; break;
                case 503: $text = 'Service Unavailable'; break;
                case 504: $text = 'Gateway Time-out'; break;
                case 505: $text = 'HTTP Version not supported'; break;
                default:
                    exit('Unknown http status code "' . htmlentities($code) . '"');
                break;
            }

            $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

            header($protocol . ' ' . $code . ' ' . $text);

            $GLOBALS['http_response_code'] = $code;

        } else {

            $code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);

        }

        return $code;

    }
}