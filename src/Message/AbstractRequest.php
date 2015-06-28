<?php
/**
 * Pin Abstract REST Request
 */

namespace Omnipay\Pin\Message;

/**
 * Pin Abstract REST Request
 *
 * This is the parent class for all Pin REST requests.
 *
 * Test modes:
 *
 * The API has two endpoint host names:
 *
 * * api.pin.net.au (live)
 * * test-api.pin.net.au (test)
 *
 * The live host is for processing live transactions, whereas the test
 * host can be used for integration testing and development.
 *
 * Each endpoint requires a different set of API keys, which can be
 * found in your account settings.
 *
 * Currently this class makes the assumption that if the testMode
 * flag is set then the Test Endpoint is being used.
 *
 * @see \Omnipay\Pin\Gateway
 * @link https://pin.net.au/docs/api
 */
abstract class AbstractRequest extends \Omnipay\Common\Message\AbstractRequest
{
    const API_VERSION = '1';

    /**
     * Test Endpoint URL
     *
     * @var string URL
     */
    protected $testEndpoint = 'https://test-api.pin.net.au/';

    /**
     * Live Endpoint URL
     *
     * @var string URL
     */
    protected $liveEndpoint = 'https://api.pin.net.au/';

    /**
     * Get secret key
     *
     * Calls to the Pin Payments API must be authenticated using HTTP
     * basic authentication, with your API key as the username, and
     * a blank string as the password.
     *
     * @return string
     */
    public function getSecretKey()
    {
        return $this->getParameter('secretKey');
    }

    /**
     * Set secret key
     *
     * Calls to the Pin Payments API must be authenticated using HTTP
     * basic authentication, with your API key as the username, and
     * a blank string as the password.
     *
     * @param string $value
     * @return AbstractRequest implements a fluent interface
     */
    public function setSecretKey($value)
    {
        return $this->setParameter('secretKey', $value);
    }

    /**
     * Get API endpoint URL
     *
     * @return string
     */
    protected function getEndpoint()
    {
        $base = $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;
        return $base . '/' . self::API_VERSION;
    }

    /**
     * Send a request to the gateway.
     *
     * FIXME: This should be calling createRequest() instead of post()
     * to allow other HTTP methods to be used.  See issue #4.
     *
     * @param string $action
     * @param array $data
     * @return HttpResponse
     */
    public function sendRequest($action, $data = null, $method = "post")
    {
        // don't throw exceptions for 4xx errors
        $this->httpClient->getEventDispatcher()->addListener(
            'request.error',
            function ($event) {
                if ($event['response']->isClientError()) {
                    $event->stopPropagation();
                }
            }
        );

        if($method == "put") {
          $httpResponse = $this->httpClient->put($this->getEndpoint() . $action, null, $data)
              ->setHeader('Authorization', 'Basic ' . base64_encode($this->getSecretKey() . ':'));
        } else {
          $httpResponse = $this->httpClient->post($this->getEndpoint() . $action, null, $data)
              ->setHeader('Authorization', 'Basic ' . base64_encode($this->getSecretKey() . ':'));
        }

        return $httpResponse->send();
    }


    public function getData()
    {
        $this->validate('amount', 'card');
        $data = array();

        // FIXME -- this won't work if there is no card.
        $data['email'] = $this->getCard()->getEmail();

        $data['amount'] = $this->getAmountInteger();
        $data['currency'] = strtolower($this->getCurrency());
        $data['description'] = $this->getDescription();
        $data['ip_address'] = $this->getClientIp();

        if ($token = $this->getToken()) {
            if (strpos($token, 'card_') !== false) {
                $data['card_token'] = $token;
            } else {
                $data['customer_token'] = $token;
            }
        } else {
            $this->getCard()->validate();

            $data['card']['number'] = $this->getCard()->getNumber();
            $data['card']['expiry_month'] = $this->getCard()->getExpiryMonth();
            $data['card']['expiry_year'] = $this->getCard()->getExpiryYear();
            $data['card']['cvc'] = $this->getCard()->getCvv();
            $data['card']['name'] = $this->getCard()->getName();
            $data['card']['address_line1'] = $this->getCard()->getAddress1();
            $data['card']['address_line2'] = $this->getCard()->getAddress2();
            $data['card']['address_city'] = $this->getCard()->getCity();
            $data['card']['address_postcode'] = $this->getCard()->getPostcode();
            $data['card']['address_state'] = $this->getCard()->getState();
            $data['card']['address_country'] = $this->getCard()->getCountry();
        }

        return $data;
    }
}
