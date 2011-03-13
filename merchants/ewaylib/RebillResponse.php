<?php
/// <summary>
/// After rebill has been uploaded, response details are returned as RebillResponse object.
/// </summary>
class RebillResponse
{

	private $m_Result;

    	private $m_ErrorSeverity;

    	private $m_ErrorDetails;


	function RebillResponse($Xml)
	{
		$xtr = simplexml_load_string($Xml) or die ("Unable to load XML string!");
                $this->m_Result = $xtr->Result;
                $this->m_ErrorDetails = $xtr->ErrorDetails;
                $this->m_ErrorSeverity = $xtr->ErrorSeverity;
      	}


      	function Result()
      	{
         	return $this->m_Result; 
      	}

      	function ErrorSeverity() 
      	{
         	return $this->m_ErrorSeverity; 
      	}

      	function ErrorDetails()
      	{
         	return $this->m_ErrorDetails; 
      	}   
   
}

?>
