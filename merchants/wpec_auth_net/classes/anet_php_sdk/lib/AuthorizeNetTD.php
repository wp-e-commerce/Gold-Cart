<?php
/**
 * Easily interact with the Authorize.Net Transaction Details XML API.
 *
 * @package    AuthorizeNet
 * @subpackage AuthorizeNetTD
 * @link       http://www.authorize.net/support/ReportingGuide_XML.pdf Transaction Details XML Guide
 */


/**
 * A class to send a request to the Transaction Details XML API.
 *
 * @package    AuthorizeNet
 * @subpackage AuthorizeNetTD
 */ 
class AuthorizeNetTD extends AuthorizeNetRequest
{

    const LIVE_URL = "https://api.authorize.net/xml/v1/request.api";
    const SANDBOX_URL = "https://apitest.authorize.net/xml/v1/request.api";
    
    private $_xml;
    
    public function getSettledBatchList($includeStatistics = false, $firstSettlementDate = false, $lastSettlementDate = false)
    {
        $this->_constructXml("getSettledBatchListRequest");
        ($includeStatistics ?
        $this->_xml->addChild("includeStatistics", $includeStatistics) : null);
        ($firstSettlementDate ?
        $this->_xml->addChild("firstSettlementDate", $firstSettlementDate) : null);
        ($lastSettlementDate ?
        $this->_xml->addChild("lastSettlementDate", $lastSettlementDate) : null);
        return $this->_sendRequest();
    }
    
    public function getTransactionDetails($transId)
    {
        $this->_constructXml("getTransactionDetailsRequest");
        $this->_xml->addChild("transId", $transId);
        return $this->_sendRequest();
    }
    
     /**
     * @return string
     */
    protected function _getPostUrl()
    {
        return ($this->_sandbox ? self::SANDBOX_URL : self::LIVE_URL);
    }
    
    /**
     *
     *
     * @param string $response
     * 
     * @return AuthorizeNetTransactionDetails_Response
     */
    protected function _handleResponse($response)
    {
        return new AuthorizeNetTD_Response($response);
    }
    
    /**
     * Prepare the XML post string.
     */
    protected function _setPostString()
    {
        $this->_post_string = $this->_xml->asXML();
        
    }
    
    /**
     * Start the SimpleXMLElement that will be posted.
     *
     * @param string $request_type The action to be performed.
     */
    private function _constructXml($request_type)
    {
        $string = '<?xml version="1.0" encoding="utf-8"?><'.$request_type.' xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd"></'.$request_type.'>';
        $this->_xml = @new SimpleXMLElement($string);
        $merchant = $this->_xml->addChild('merchantAuthentication');
        $merchant->addChild('name',$this->_api_login);
        $merchant->addChild('transactionKey',$this->_transaction_key);
    }
    
}

/**
 * A class to parse a response from the Transaction Details XML API.
 *
 * @package    AuthorizeNet
 * @subpackage AuthorizeNetTD
 */
class AuthorizeNetTD_Response extends AuthorizeNetXMLResponse
{
    

}
