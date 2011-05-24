<?php
/**
 * Base class for the AuthorizeNet ARB & CIM Responses.
 *
 * @package    AuthorizeNet
 * @subpackage AuthorizeNetXML
 */

/**
 * Base class for the AuthorizeNet ARB & CIM Responses.
 *
 * @package    AuthorizeNet
 * @subpackage AuthorizeNetXML
 */
class AuthorizeNetXMLResponse
{

    public $xml; // Holds a SimpleXML Element with response.

    /**
     * Constructor. Parses the AuthorizeNet response string.
     *
     * @param string $response The response from the AuthNet server.
     */
    public function __construct($response)
    {
        $this->response = $response;
        if ($response) {
            $this->xml = @simplexml_load_string($response);
        }
    }
    
    /**
     * Was the transaction successfull.
     *
     * @return bool
     */
    public function isOk()
    {
        return ($this->getResultCode() == "Ok");
    }
    
    /**
     * Was there an error.
     *
     * @return bool
     */
    public function isError()
    {
        return ($this->getResultCode() == "Error");
    }
    
    public function getErrorMessage()
    {
        return "Error: {$this->getResultCode()} 
        Message: {$this->getMessageText()}
        {$this->getMessageCode()}";    
    }
    
    public function getRefID()
    {
        return $this->_getElementContents("refId");
    }
    
    public function getResultCode()
    {
        return $this->_getElementContents("resultCode");
    }
    
    public function getMessageCode()
    {
        return $this->_getElementContents("code");
    }
    
    public function getMessageText()
    {
        return $this->_getElementContents("text");
    }
    
    public function getCustomerAddressId()
    {
        return $this->_getElementContents("customerAddressId");
    }
    
    public function getCustomerProfileId()
    {
        return $this->_getElementContents("customerProfileId");
    }
    
    public function getPaymentProfileId()
    {
        return $this->_getElementContents("customerPaymentProfileId");
    }
    
    protected function _getElementContents($elementName) 
    {
        $start = "<$elementName>";
        $end = "</$elementName>";
        if (strpos($this->response,$start) === false || strpos($this->response,$end) === false) {
            return false;
        } else {
            $start_position = strpos($this->response, $start)+strlen($start);
            $end_position = strpos($this->response, $end);
            return substr($this->response, $start_position, $end_position-$start_position);
        }
    }

}