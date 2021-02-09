<?php

namespace Skytraffic\BitApps;

class BitAppsForwarding
{
    const MAINNET_URL = 'https://api.bitaps.com/btc/v1/';

    const HTTP_METHOD_POST = 'POST';
    const HTTP_METHOD_GET = 'GET';

    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var string
     */
    private $paymentCode;


    private $responseHeaders = [];


    /**
     * @param string $accessToken
     */
    public function setAccessToken($accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @param string $paymentCode
     */
    public function setPaymentCode($paymentCode): void
    {
        $this->paymentCode = $paymentCode;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function getHeaderParam(string $key)
    {
        $key = strtolower($key);

        if (array_key_exists($key, $this->responseHeaders)) {

            $headers = $this->responseHeaders[$key];

            if (count($headers) === 1) {
                return $headers[0];
            }

            return $headers;
        }

        return null;
    }

    /**
     * @return int
     */
    public function getRatelimitLimit(): ?int
    {
        $key = 'ratelimit-limit';
        return $this->getHeaderParam($key);
    }

    /**
     * @return int
     */
    public function getRatelimitPeriod(): ?int
    {
        $key = 'ratelimit-period';
        return $this->getHeaderParam($key);
    }

    /**
     * @return int
     */
    public function getRatelimitRemaining(): ?int
    {
        $key = 'ratelimit-remaining';
        return $this->getHeaderParam($key);
    }

    /**
     * @return int
     */
    public function getRatelimitReset(): ?int
    {
        $key = 'ratelimit-reset';
        return $this->getHeaderParam($key);
    }

    /**
     * @return array
     */
    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }


    /**
     * Creates payment address
     * @param string $forwardingAddress Address for payout
     * @param null|string $callback_link Link for payment notification handler
     * @param int|null $confirmations Number of confirmations required for the payment
     * @return array
     * @see https://developer.bitaps.com/forwarding#Create_forwarding_address
     * @throws \Exception
     */
    public function createPaymentAddress(string $forwardingAddress, string $callback_link = null, int $confirmations = null)
    {
        $params = [
            'forwarding_address' => $forwardingAddress
        ];

        if (!is_null($callback_link)) {
            $params['callback_link'] = $callback_link;
        }

        if (!is_null($confirmations)) {
            $params['confirmations'] = $confirmations;
        }

        return $this->request('/create/payment/address', $params);
    }


    /**
     * Request status and statistics of the payment address.
     * The request without authorization returns only public information,
     * full information is available with request header Payment-Code or Access-Token.
     * @param string $paymentAddress
     * @return array
     * @see https://developer.bitaps.com/forwarding#Payment_address_state
     * @throws \Exception
     */
    public function getPaymentAddressState(string $paymentAddress)
    {
        $params = [];
        return $this->request('/payment/address/state/' . $paymentAddress, $params, self::HTTP_METHOD_GET);
    }

    /**
     * Request list of payment address transactions.
     * The request without authorization returns only public information,
     * full information is available with request header Payment-Code or Access-Token.
     * @param string $paymentAddress Payment address
     * @param int|null $from From timestamp
     * @param int|null $to Until timestamp
     * @param int|null $limit Limit per page
     * @param int|null $page Page number
     * @return array
     * @throws \Exception
     */
    public function getPaymentAddressTransactions(string $paymentAddress, int $from = null, int $to = null, int $limit = null, int $page = null)
    {
        $params = [];
        if (!is_null($from)) {
            $params['from'] = $from;
        }
        if (!is_null($to)) {
            $params['to'] = $to;
        }
        if (!is_null($limit)) {
            $params['limit'] = $limit;
        }
        if (!is_null($page)) {
            $params['page'] = $page;
        }
        return $this->request('/payment/address/transactions/' . $paymentAddress, $params, self::HTTP_METHOD_GET);
    }

    /**
     * Callback log for payment address.
     * @param string $paymentAddress
     * @param int|null $limit limit per page
     * @param int|null $page Page number
     * @return array
     * @throws \Exception
     */
    public function getPaymentAddressCallbackLog(string $paymentAddress, int $limit = null, int $page = null)
    {

        $this->assertAuthorization();

        $params = [];

        if (!is_null($limit)) {
            $params['limit'] = $limit;
        }
        if (!is_null($page)) {
            $params['page'] = $page;
        }

        return $this->request('/payment/address/callback/log/' . $paymentAddress, $params, self::HTTP_METHOD_GET);

    }


    /**
     * Callback logs for payment transaction hash.
     * @param string $txHash Payment transaction hash
     * @param string $txOutput Payment transaction output
     * @param int|null $limit
     * @param int|null $page
     * @return array
     * @throws \Exception
     */
    public function getPaymentTransaction(string $txHash, string $txOutput, int $limit = null, int $page = null)
    {
        $this->assertAuthorization();

        $params = [];

        if (!is_null($limit)) {
            $params['limit'] = $limit;
        }
        if (!is_null($page)) {
            $params['page'] = $page;
        }

        return $this->request('/payment/callback/log/' . $txHash . '/' . $txOutput, $params, self::HTTP_METHOD_GET);
    }


    /**
     *  Domain authorization
     * To gain access to statistics, you must verify domain ownership and get Access-Token
     * Step 1. CreateDomainAuthCode
     * Step 2. Add the output of the authorization code as plain / text when you receive a GET request through callback link.
     * Step 3. CreateDomainAccessToken
     */

    /**
     * @param string $callbackLink
     * @return array
     * @throws \Exception
     */
    public function createDomainAuthCode(string $callbackLink)
    {
        $params['callback_link'] = $callbackLink;
        return $this->request('/create/domain/authorization/code', $params, self::HTTP_METHOD_POST);
    }

    /**
     * @param string $callbackLink
     * @return array
     * @throws \Exception
     */
    public function createDomainAccessToken(string $callbackLink)
    {
        $params['callback_link'] = $callbackLink;
        return $this->request('/create/domain/access/token', $params, self::HTTP_METHOD_POST);
    }

    /**
     * Domain statistics
     * @param string $domainHash
     * @return array
     * @throws \Exception
     */
    public function getDomainStatistics(string $domainHash)
    {
        $this->assertAccessToken();
        return $this->request('/domain/state/' . $domainHash, [], self::HTTP_METHOD_GET);
    }

    /**
     * List of created addresses
     * @param string $domainHash
     * @param string|null $from
     * @param string|null $to
     * @param int|null $limit
     * @param int|null $page
     * @return array
     * @throws \Exception
     */
    public function getDomainAddresses(string $domainHash, string $from = null, string $to = null, int $limit = null, int $page = null)
    {
        $this->assertAccessToken();

        $params = [];
        if (!is_null($from)) {
            $params['from'] = $from;
        }
        if (!is_null($to)) {
            $params['to'] = $to;
        }
        if (!is_null($limit)) {
            $params['limit'] = $limit;
        }
        if (!is_null($page)) {
            $params['page'] = $page;
        }
        return $this->request('/domain/addresses/' . $domainHash, $params, self::HTTP_METHOD_GET);
    }

    /**
     * List of domain transactions
     * @param string $domainHash
     * @param string|null $from
     * @param string|null $to
     * @param int|null $limit
     * @param int|null $page
     * @param string|null $type
     * @return array
     * @throws \Exception
     */
    public function getDomainTransactions(string $domainHash, string $from = null, string $to = null, int $limit = null, int $page = null, string $type = null)
    {
        $this->assertAccessToken();

        $params = [];
        if (!is_null($from)) {
            $params['from'] = $from;
        }
        if (!is_null($to)) {
            $params['to'] = $to;
        }
        if (!is_null($limit)) {
            $params['limit'] = $limit;
        }
        if (!is_null($page)) {
            $params['page'] = $page;
        }
        if (!is_null($type)) {
            $params['type'] = $type;
        }
        return $this->request('/domain/transactions/' . $domainHash, $params, self::HTTP_METHOD_GET);
    }

    /**
     * Daily domain statistics
     * @param string $domainHash
     * @param string|null $from
     * @param string|null $to
     * @param int|null $limit
     * @param int|null $page
     * @return array
     * @throws \Exception
     */
    public function getDomainDailyStatistic(string $domainHash, string $from = null, string $to = null, int $limit = null, int $page = null)
    {
        $this->assertAccessToken();

        $params = [];
        if (!is_null($from)) {
            $params['from'] = $from;
        }
        if (!is_null($to)) {
            $params['to'] = $to;
        }
        if (!is_null($limit)) {
            $params['limit'] = $limit;
        }
        if (!is_null($page)) {
            $params['page'] = $page;
        }

        return $this->request('/domain/daily/statistic/' . $domainHash, $params, self::HTTP_METHOD_GET);
    }

    /**
     * @throws \Exception
     */
    private function assertAuthorization()
    {
        if (!isset($this->accessToken) && !isset($this->paymentCode)) {
            throw new \Exception('Authorization required. Please provide Payment-Code or Access-Token');
        }
    }

    private function assertAccessToken()
    {
        if (!isset($this->accessToken)) {
            throw new \Exception('Authorization required. Please provide Access-Token');
        }
    }

    /**
     * @param string $path
     * @param array $params
     * @param string $method
     * @return array
     * @throws \Exception
     */
    private function request(string $path, array $params, string $method = self::HTTP_METHOD_POST): array
    {

        $this->responseHeaders = [];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, array($this, 'parseHeaders'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);

        if (isset($this->paymentCode)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Payment-Code: ' . $this->paymentCode]);
        }

        if (isset($this->accessToken)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Access-Token: ' . $this->accessToken]);
        }

        $endpoint = self::MAINNET_URL . $path;

        if ($method === self::HTTP_METHOD_GET) {
            $endpoint .= '?' . http_build_query($params);
        }

        curl_setopt($curl, CURLOPT_URL, $endpoint);

        if ($method === self::HTTP_METHOD_POST) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        }

        $response = curl_exec($curl);


        if (curl_errno($curl) > 0) {
            throw new \Exception('Curl error: ' . curl_error($curl));
        }

        curl_close($curl);

        $result = json_decode($response, true);

        return $result;
    }

    /**
     * @param $curl
     * @param string $header
     * @return int header length
     */
    private function parseHeaders($curl, string $header): int
    {
        $len = strlen($header);
        $header = explode(':', $header, 2);

        if (count($header) < 2) {
            // ignore invalid headers
            return $len;
        }


        $name = strtolower(trim($header[0]));

        if (!array_key_exists($name, $this->responseHeaders))
            $this->responseHeaders[$name] = [trim($header[1])];
        else
            $this->responseHeaders[$name][] = trim($header[1]);

        return $len;

    }
}