<?php

namespace Naivic;

/**
 * Naivic cURL envelope
 *
 * This helper class make easier most of cURL activities:
 * make remote requests, get content from internet,
 * use external API, make external tests and API monitorings
 *
 * @version 1.0.0
 * @copyright 2024 Naivic
 */
class CURL {
    /**
     * @var array - cURL constant mnemonics
     */
    private array $mnemonics = [];
    /**
     * @var resource - cURL handler
     */
    private $ch;
    /**
     * @var array - cURL options
     */
    private array $opts = [];
    /**
     * @var array - default cURL options
     */
    private array $opts_def = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_BINARYTRANSFER => true,
        CURLOPT_ENCODING       => "",
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
    ];
    /**
     * @var string - query method
     */
    private string $method = "";
    /**
     * @var string - query URL
     */
    private string $url = "";
    /**
     * @var string|array - query parameters
     */
    private $par = null;
    /**
     * @var array - query headers
     */
    private array $hdr = [];
    /**
     * @var string - host response body
     */
    private string $body = "";
    /**
     * @var ?array - host response data (decoded from body)
     */
    private ?array $data = null;
    /**
     * @var array - host response headers
     */
    private array $headers = [];
    /**
     * @var int - host response headers total length
     */
    private int $headers_len = 0;
    /**
     * @var string - cURL error
     */
    private $err  = "";
    /**
     * @var array - debug data
     */
    private array $debug = [];
    /**
     * @var resource - cURL log handler
     */
    private $hlog = null;
    /**
     * @var bool - true for TLS connection
     */
    public bool $ssl = false;
    /**
     * @var string - path to CA certificate
     *               needed to host cert check
     *               (for $this->ssl == true)
     */
    public string $ssl_ca = "";
    /**
     * @var string - path to client private key
     */
    public string $ssl_key = "";
    /**
     * @var string - path to client public cert
     */
    public string $ssl_crt = "";
    /**
     * @var string - client cert password
     */
    public string $ssl_pass = "";

    /**
     * Prepare parameters for request
     */
    private function addParameters() {

        $pars = is_string( $this->par ) ? $this->par : "";
        $pars = ( is_array( $this->par ) && count( $this->par ) > 0 ) ? http_build_query($this->par) : $pars;

        if( $this->method == "GET" ) {

            // Prepare parameters for "GET" request
            $this->opts += [
                CURLOPT_URL   => $this->url.( empty($pars) ? "" : "?".$pars ),
                CURLOPT_POST  => false,
            ];

        } else {

            // Prepare parameters for any but "GET" request
            if( count( preg_grep( "#(Content-Type|accept)\s*:\s*application/.*json#", $this->hdr ) ) ) {
               $pars = is_string( $this->par ) ? $pars : json_encode( $this->par, JSON_THROW_ON_ERROR );
            }
            $this->opts += [
                CURLOPT_POSTFIELDS => $pars,
                CURLOPT_POST       => true,
                CURLOPT_URL        => $this->url,
            ];
          
        }

    }

    /**
     * Prepare SSL parameters
     *
     * @return void
     */
    private function addSSL() {

        $this->opts += [
            CURLOPT_SSL_VERIFYPEER => $this->ssl,
            CURLOPT_SSL_VERIFYHOST => $this->ssl ? 2 : 0,
        ];

        if( !empty( $this->ssl_ca  ) ) $this->opts += [ CURLOPT_CAINFO => $this->ssl_ca ];

        if( !empty( $this->ssl_key ) ) {
            $this->opts += [
                CURLOPT_SSLKEY        => $this->ssl_key,
                CURLOPT_SSLCERT       => $this->ssl_crt,
                CURLOPT_SSLCERTPASSWD => $this->ssl_pass,
            ];
        }

    }

    /**
     * Decode received data if Content-Type contains "application/json"
     *
     * @return void
     */
    private function decode() {

        $content_type = curl_getinfo( $this->ch, CURLINFO_CONTENT_TYPE );

        if( preg_match( "#application/json#", $content_type ) ) {
            $this->data = json_decode( $this->body, true );
            if( json_last_error() !== JSON_ERROR_NONE ) {
                $this->err .= "\nJSON decode error: ".json_last_error();
            }
        }

    }

    /**
     * Add mnemonics to array of cURL options
     *
     * @param array $opt - cURL numeric options array ( number => value ) 
     * @return array     - cURL mnemonic options array ( mnemonic[number] => value )
     */
    private function addMnemonics( $opt ) {
        $res = [];
        foreach( $opt as $k => $v ) {
            if( $k != CURLOPT_HEADERFUNCTION ) {
                $m = array_key_exists( $k, $this->mnemonics ) ? $this->mnemonics[$k] : $k;
                $res[ $m ] = $v;
            }
        }
        return $res;
    }

    /**
     * Perform query with arbitrary method
     *
     * @param string $method       - HTTP method
     * @param string $url          - URL
     * @param array|string $par    - either associative array with query parameters
     *                                   "parameter name" => parameter value
     *                                       for $method != GET && Content-Type: application/json -
     *                                       $par will be converted to json string and send as body of HTTP request
     *                               or string
     *                                   for $method == GET : $par will be added to URL: $URL."?".$par
     *                                   for $method != GET : $par will become the postfields (body) of HTTP request
     * @param array  $hdr          - query headers
     * @return array               - associative array
     *                                   "body"    => response body, as a string
     *                                   "data"    => response data, as assoc. array, converted from body
     *                                                if response has application/json content type
     *                                                (null for invalid json and for mismatch content type)
     *                                   "headers" => array of response headers
     *                                   "debug"   => assoc. array with verbose debug information
     *                                       "opts"   => assoc.array with cURL actual query options
     *                                       "info"   => assoc.array with curl_getinfo()
     *                                       "log"    => string with cURL query log
     *                                   "err"   => cURL errors
     */
    public function query( string $method, string $url, $par = null, array $hdr = [] ) : CURL\Response {

        // Reset common data
        $this->opts = $this->opts_def;
        $this->body = "";
        $this->data = null;
        $this->headers = [];
        $this->headers_len = 0;

        // Prepare query parameters
        $this->method = mb_strtoupper( $method );
        $this->opts += [ CURLOPT_CUSTOMREQUEST  => $this->method ];
        $this->url = $url;
        $this->par = $par;
        $this->hdr = $hdr;
        if( !empty( $this->hdr ) ) $this->opts += [ CURLOPT_HTTPHEADER => $this->hdr ];
        $this->addParameters();

        // Add SSL support if needed
        $this->addSSL();

        // Prepare debug environment
        if( FALSE === ($this->hlog = fopen('php://temp', 'rw+')) )
            throw new \Exception( "Cannot initialize - temp stream was not opened" );
        $this->opts += [
            CURLOPT_VERBOSE        => true,
            CURLOPT_STDERR         => $this->hlog,
        ];
        $this->debug = [ "opts" => $this->addMnemonics( $this->opts ) ];

        // Prepare connection
        $this->ch = curl_init();
        curl_setopt_array( $this->ch, $this->opts );

        // Make request and decode response
        $body = curl_exec( $this->ch );
        $this->body = substr($body, $this->headers_len);
        $this->err = curl_error( $this->ch );
        $this->decode();

        // Store debug info
        rewind( $this->hlog );
        $this->debug["log"] = stream_get_contents( $this->hlog );
        fclose( $this->hlog );
        $this->debug["info"] = curl_getinfo( $this->ch );

        // Cleanup
        curl_close( $this->ch );
        $this->ch = null;

        // Return result
        return new CURL\Response(
            body:    $this->body,
            data:    $this->data,
            headers: $this->headers,
            err:     $this->err,
            opts:    $this->debug["opts"],
            log:     $this->debug["log"],
            info:    $this->debug["info"],
        );

    }

    /**
     * Constructor
     *
     * Apply defaults to cURL options
     */
    public function __construct() {

        $this->mnemonics = array();
        $constants = get_defined_constants( true );
        foreach( $constants["curl"] as $name => $value ) {
            if( !strncmp($name, "CURLOPT_", 8) ) {
                $this->mnemonics[$value] = $name;
            }
        }

        $this->opts_def += [
            CURLOPT_HEADERFUNCTION =>
                function( $curl, $header ) {
                    $this->headers[] = trim($header);
                    $len = strlen( $header );
                    $this->headers_len += $len;
                    return $len;
                }
        ];

    }

}
                                                 