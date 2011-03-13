<?php
/// <summary>
/// RebillPayment Object
/// </summary>

class RebillPayment
{

    #region Private Variables

    private $m_ewayCustomerID;

    private $m_CustomerRef;

    private $m_CustomerTitle;

    private $m_CustomerFirstName;

    private $m_CustomerLastName;

    private $m_CustomerCompany;

    private $m_CustomerJobDesc;

    private $m_CustomerEmail;

    private $m_CustomerAddress;

    private $m_CustomerSuburb;

    private $m_CustomerState;

    private $m_CustomerPostCode;

    private $m_CustomerCountry;

    private $m_CustomerPhone1;

    private $m_CustomerPhone2;

    private $m_CustomerFax;

    private $m_CustomerURL;

    private $m_CustomerComments;

    private $m_RebillInvRef;

    private $m_RebillInvDesc;

    private $m_RebillCCName;

    private $m_RebillCCNumber;

    private $m_RebillCCExpMonth;

    private $m_RebillCCExpYear;

    private $m_RebillInitAmt;

    private $m_RebillInitDate;

    private $m_RebillRecurAmt;

    private $m_RebillStartDate;

    private $m_RebillInterval;

    private $m_RebillIntervalType;

    private $m_RebillEndDate;

    private $m_ewayURL;

    #endregion



    	#region RebillPayment

	function RebillPayment()
    	{
    	}

    	#endregion



    	#region Public Properties

    	public function eWAYCustomerID($value) 
	{ 
		$this->m_ewayCustomerID = $value;	
	}

    	public function CustomerRef($value)
	{  	
		$this->m_CustomerRef = $value; 
	} 

    	public function CustomerTitle($value)
	{ 
	  	$this->m_CustomerTitle = $value;  
	}

    	public function CustomerFirstName($value)
	{ 
		$this->m_CustomerFirstName = $value; 
	} 

    	public function CustomerLastName($value)
	{ 
		$this->m_CustomerLastName = $value; 
	}

    	public function CustomerCompany($value)
	{ 
		$this->m_CustomerCompany = $value; 
	} 

    	public function CustomerJobDesc($value)
	{ 
		$this->m_CustomerJobDesc = $value; 
	}

    	public function CustomerEmail($value)
	{ 
		$this->m_CustomerEmail = $value; 
	}

    	public function CustomerAddress($value)
	{ 
		$this->m_CustomerAddress = $value; 
	}

    	public function CustomerSuburb($value)
	{ 
		$this->m_CustomerSuburb = $value; 
	}

    	public function CustomerState($value)
	{ 
		$this->m_CustomerState = $value; 
	}

    	public function CustomerPostCode($value)
	{ 
		$this->m_CustomerPostCode = $value; 
	}

    	public function CustomerCountry($value)
	{
		$this->m_CustomerCountry = $value; 
	}

    	public function CustomerPhone1($value)
	{ 
		$this->m_CustomerPhone1 = $value; 
	}

    	public function CustomerPhone2($value)
	{ 
		$this->m_CustomerPhone2 = $value; 
	}

    	public function CustomerFax($value)
	{ 
		$this->m_CustomerFax = $value; 
	}

    	public function CustomerURL($value)
	{ 
		$this->m_CustomerURL = $value; 
	}

    	public function CustomerComments($value)
	{ 
		$this->m_CustomerComments = $value; 
	}

    	public function RebillInvRef($value)
	{ 
		$this->m_RebillInvRef = $value; 
	}

    	public function RebillInvDesc($value)
	{ 
		$this->m_RebillInvDesc = $value; 
	}

    	public function RebillCCname($value)
	{
		$this->m_RebillCCName = $value;
	}

    	public function RebillCCNumber($value)
	{ 
		$this->m_RebillCCNumber = $value; 
	}

    	public function RebillCCExpMonth($value)
	{ 
		$this->m_RebillCCExpMonth = $value; 
	}

    	public function RebillCCExpYear($value)
	{ 
		$this->m_RebillCCExpYear = $value; 
	}

    	public function RebillInitAmt($value)
	{ 
		$this->m_RebillInitAmt = $value; 
	}

    	public function RebillInitDate($value)
	{ 
		$this->m_RebillInitDate = $value; 
	}

    	public function RebillRecurAmt($value)
	{ 
		$this->m_RebillRecurAmt = $value; 
	}

    	public function RebillStartDate($value)
	{ 
		$this->m_RebillStartDate = $value; 
	}

    	public function RebillInterval($value)
	{ 
		$this->m_RebillInterval = $value; 
	}

    	public function RebillIntervalType($value)
	{ 
		$this->m_RebillIntervalType = $value; 
	}

    	public function RebillEndDate($value)
	{ 
		$this->m_RebillEndDate = $value; 
	}

    	public function ewayURL($value)
	{ 
		$this->m_ewayURL = $value; 
	}

    	#endregion



    	#region Get In XML Format

    	public function ToXML()

    	{
		$xmlRebill = new DomDocument('1.0');
	
		$nodeRoot = $xmlRebill->CreateElement('RebillUpload');
		$nodeRoot = $xmlRebill->appendChild($nodeRoot);

		$nodeNewRebill = $xmlRebill->createElement('NewRebill');
		$nodeNewRebill = $nodeRoot->appendChild($nodeNewRebill);

		$nodeCustomer = $xmlRebill->createElement('eWayCustomerID');
		$nodeCustomer = $nodeNewRebill->appendChild($nodeCustomer);

		$value = $xmlRebill->createTextNode($this->m_ewayCustomerID);
		$value = $nodeCustomer->appendChild($value);


        //Customer 
		$nodeCustomer = $xmlRebill->createElement('Customer');
		$nodeCustomer = $nodeNewRebill->appendChild($nodeCustomer);

		$nodeCustomerDetails = $xmlRebill->createElement('CustomerRef');
	 	$nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

                $value = $xmlRebill->createTextNode($this->m_CustomerRef);
                $value = $nodeCustomerDetails->appendChild($value);

	 	$nodeCustomerDetails = $xmlRebill->createElement('CustomerTitle');
                $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

                $value = $xmlRebill->createTextNode($this->m_CustomerTitle);
                $value = $nodeCustomerDetails->appendChild($value);

		$nodeCustomerDetails = $xmlRebill->createElement('CustomerFirstName');
                $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

                $value = $xmlRebill->createTextNode($this->m_CustomerFirstName);
                $value = $nodeCustomerDetails->appendChild($value);

		$nodeCustomerDetails = $xmlRebill->createElement('CustomerLastName');
                $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

                $value = $xmlRebill->createTextNode($this->m_CustomerLastName);
                $value = $nodeCustomerDetails->appendChild($value);

		$nodeCustomerDetails = $xmlRebill->createElement('CustomerCompany');
                $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

                $value = $xmlRebill->createTextNode($this->m_CustomerCompany);
                $value = $nodeCustomerDetails->appendChild($value);


		$nodeCustomerDetails = $xmlRebill->createElement('CustomerJobDesc');
                $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

                $value = $xmlRebill->createTextNode($this->m_CustomerJobDesc);
                $value = $nodeCustomerDetails->appendChild($value);
		
		$nodeCustomerDetails = $xmlRebill->createElement('CustomerEmail');
                $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

                $value = $xmlRebill->createTextNode($this->m_CustomerEmail);
                $value = $nodeCustomerDetails->appendChild($value);

		$nodeCustomerDetails = $xmlRebill->createElement('CustomerAddress');
                $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

                $value = $xmlRebill->createTextNode($this->m_CustomerAddress);
                $value = $nodeCustomerDetails->appendChild($value);

		$nodeCustomerDetails = $xmlRebill->createElement('CustomerSuburb');
                $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

                $value = $xmlRebill->createTextNode($this->m_CustomerSuburb);
                $value = $nodeCustomerDetails->appendChild($value);

		$nodeCustomerDetails = $xmlRebill->createElement('CustomerState');
                $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

                $value = $xmlRebill->createTextNode($this->m_CustomerState);
                $value = $nodeCustomerDetails->appendChild($value);

		$nodeCustomerDetails = $xmlRebill->createElement('CustomerPostCode');
                $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

                $value = $xmlRebill->createTextNode($this->m_CustomerPostCode);
                $value = $nodeCustomerDetails->appendChild($value);

		$nodeCustomerDetails = $xmlRebill->createElement('CustomerCountry');
                $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

                $value = $xmlRebill->createTextNode($this->m_CustomerCountry);
                $value = $nodeCustomerDetails->appendChild($value);

		$nodeCustomerDetails = $xmlRebill->createElement('CustomerPhone1');
                $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

                $value = $xmlRebill->createTextNode($this->m_CustomerPhone1);
                $value = $nodeCustomerDetails->appendChild($value);

		$nodeCustomerDetails = $xmlRebill->createElement('CustomerPhone2');
                $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

                $value = $xmlRebill->createTextNode($this->m_CustomerPhone2);
                $value = $nodeCustomerDetails->appendChild($value);

		$nodeCustomerDetails = $xmlRebill->createElement('CustomerFax');
                $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

                $value = $xmlRebill->createTextNode($this->m_CustomerFax);
                $value = $nodeCustomerDetails->appendChild($value);

		$nodeCustomerDetails = $xmlRebill->createElement('CustomerURL');
                $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

                $value = $xmlRebill->createTextNode($this->m_CustomerURL);
                $value = $nodeCustomerDetails->appendChild($value);

		$nodeCustomerDetails = $xmlRebill->createElement('CustomerComments');
                $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

                $value = $xmlRebill->createTextNode($this->m_CustomerComments);
                $value = $nodeCustomerDetails->appendChild($value);


        //Rebill Events
		
		$nodeRebillEvent = $xmlRebill->createElement('RebillEvent');
		$nodeRebillEvent = $nodeNewRebill->appendChild($nodeRebillEvent);

		$nodeRebillDetails = $xmlRebill->createElement('RebillInvRef');
		$nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);

		$value = $xmlRebill->createTextNode($this->m_RebillInvRef);
		$value = $nodeRebillDetails->AppendChild($value);


		$nodeRebillDetails = $xmlRebill->createElement('RebillInvDesc');
                $nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);

                $value = $xmlRebill->createTextNode($this->m_RebillInvDesc);
                $value = $nodeRebillDetails->AppendChild($value);

		$nodeRebillDetails = $xmlRebill->createElement('RebillCCName');
                $nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);

                $value = $xmlRebill->createTextNode($this->m_RebillCCName);
                $value = $nodeRebillDetails->AppendChild($value);

		$nodeRebillDetails = $xmlRebill->createElement('RebillCCNumber');
                $nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);

                $value = $xmlRebill->createTextNode($this->m_RebillCCNumber);
                $value = $nodeRebillDetails->AppendChild($value);

		$nodeRebillDetails = $xmlRebill->createElement('RebillCCExpMonth');
                $nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);

                $value = $xmlRebill->createTextNode($this->m_RebillCCExpMonth);
                $value = $nodeRebillDetails->AppendChild($value);

		$nodeRebillDetails = $xmlRebill->createElement('RebillCCExpYear');
                $nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);

                $value = $xmlRebill->createTextNode($this->m_RebillCCExpYear);
                $value = $nodeRebillDetails->AppendChild($value);

		$nodeRebillDetails = $xmlRebill->createElement('RebillInitAmt');
                $nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);

		$value = $xmlRebill->createTextNode($this->m_RebillInitAmt);
		$value = $nodeRebillDetails->AppendChild($value);

		$nodeRebillDetails = $xmlRebill->createElement('RebillInitDate');
		$nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);

		$value = $xmlRebill->createTextNode($this->m_RebillInitDate);
		$value = $nodeRebillDetails->AppendChild($value);
	
		$nodeRebillDetails = $xmlRebill->createElement('RebillRecurAmt');
		$nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);
	
		$value = $xmlRebill->createTextNode($this->m_RebillRecurAmt);
		$value = $nodeRebillDetails->AppendChild($value);
	
		$nodeRebillDetails = $xmlRebill->createElement('RebillStartDate');
		$nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);
	
		$value = $xmlRebill->createTextNode($this->m_RebillStartDate);
		$value = $nodeRebillDetails->AppendChild($value);
	
		$nodeRebillDetails = $xmlRebill->createElement('RebillInterval');
		$nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);
	
		$value = $xmlRebill->createTextNode($this->m_RebillInterval);
		$value = $nodeRebillDetails->AppendChild($value);
	
		$nodeRebillDetails = $xmlRebill->createElement('RebillIntervalType');
		$nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);
	
		$value = $xmlRebill->createTextNode($this->m_RebillIntervalType);
		$value = $nodeRebillDetails->AppendChild($value);
	
		$nodeRebillDetails = $xmlRebill->createElement('RebillEndDate');
		$nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);
	
		$value = $xmlRebill->createTextNode($this->m_RebillEndDate);
		$value = $nodeRebillDetails->AppendChild($value);

		$InnerXml = $xmlRebill->saveXML();
		return $InnerXml;

	}
}

?>
