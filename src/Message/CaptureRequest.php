<?php
/**
 * Pin Purchase Request
 */

namespace Omnipay\Pin\Message;

/**
 * Pin Capture Request
 *
 * The charges API allows you to create new credit card charges, and to retrieve
 * details of previous charges.
 *
 * This message creates a new charge and returns its details. This may be a
 * long-running request.
 *
 * Gateway Parameters
 *
 * * token The token that was returned when it was authorised
 *
 * @see \Omnipay\Pin\Gateway
 * @link https://pin.net.au/docs/api/charges 
 */
class CaptureRequest extends AbstractRequest
{

    function getData() {
      $this->validate('token');
      return [
        "token" => $this->getToken(),
        "amount" => $this->getAmountInteger()
      ];
    }

    public function sendData($data)
    {
        $token = $data["token"];
        unset($data["token"]);
        $httpResponse = $this->sendRequest("/charges/$token/capture", $data, "put");

        return $this->response = new Response($this, $httpResponse->json());
    }
}
