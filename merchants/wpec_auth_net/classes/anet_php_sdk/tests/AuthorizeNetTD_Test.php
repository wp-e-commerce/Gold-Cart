<?php

require_once 'AuthorizeNet_Test_Config.php';


class AuthorizeNetTD_Test extends PHPUnit_Framework_TestCase
{


    public function testGetSettledBatchList()
    {
        $request = new AuthorizeNetTD;
        $response = $request->getSettledBatchList();
        $this->assertTrue($response->isOk());
    }
    
    public function testGetTransactionDetails()
    {
        $sale = new AuthorizeNetAIM;
        $amount = rand(1, 100);
        $response = $sale->authorizeAndCapture($amount, '4012888818888', '04/17');
        $this->assertTrue($response->approved);
        
        $transId = $response->transaction_id;
        
        $request = new AuthorizeNetTD;
        $response = $request->getTransactionDetails($transId);
        $this->assertTrue($response->isOk());
        
        $this->assertEquals($transId, (string)$response->xml->transaction->transId);
        $this->assertEquals($amount, (string)$response->xml->transaction->authAmount);
        $this->assertEquals("Visa", (string)$response->xml->transaction->payment->creditCard->cardType);
        
    }
  

}