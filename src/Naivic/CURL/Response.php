<?php

namespace Naivic\CURL;

readonly class Response {

    /**
     * Constructor
     *
     * @param string $body    - host response body
     * @param ?array $data    - host response data (decoded from body)
     * @param array  $headers - host response headers
     * @param string $err     - cURL error
     * @param array  $opts    - cURL actual options data
     * @param array  $info    - cURL info
     * @param string $log     - cURL log
     */
    public function __construct(
        public string $body,
        public ?array $data,
        public array $headers,
        public string $err,
        public array $opts,
        public array $info,
        public string $log,
    ) {
    }

}
