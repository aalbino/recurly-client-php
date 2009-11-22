<?php

/**
 * @category   Recurly
 * @package    Recurly_Client_PHP
 * @copyright  Copyright (c) 2009 {@link http://recurly.com Recurly, Inc.}
 */
class RecurlySubscription
{
	var $account;		// User account information
	var $plan_code;		// Subscription plan's code
	var $unit_amount;	// Defaults to plan's current price if not set
	var $quantity;		// Defaults to 1
	var $billing_info;	// Account's billing information
	
	public function create()
	{
		$uri = RecurlyClient::PATH_ACCOUNTS . urlencode($this->account->account_code) . RecurlyClient::PATH_ACCOUNT_SUBSCRIPTION;
		$data = $this->getXml();
		$result = RecurlyClient::__sendRequest($uri, 'POST', $data);
		if (preg_match("/^2..$/", $result->code)) {
			return RecurlyClient::__parse_xml($result->response, 'subscription', 'RecurlySubscription');
		} else if (strpos($result->response, '<errors>') > 0 && $result->code == 422) {
			throw new RecurlyValidationException($result->code, $result->response);
		} else {
			throw new RecurlyException("Could not create a subscription for {$this->account->account_code}: {$result->response} -- ({$result->code})");
		}
	}
	
	public static function getSubscription($accountCode)
	{
	    $uri = RecurlyClient::PATH_ACCOUNTS . urlencode($accountCode) . RecurlyClient::PATH_ACCOUNT_SUBSCRIPTION;
		$result = RecurlyClient::__sendRequest($uri, 'GET');
		if (preg_match("/^2..$/", $result->code)) {
			return RecurlyClient::__parse_xml($result->response, 'subscription', 'RecurlySubscription');
		} else if ($result->code == '404') {
			return null;
		} else {
			throw new RecurlyException("Could not get subscription for {$accountCode}: {$result->response} -- ({$result->code})");
		}
	}
	
	public static function cancelSubscription($accountCode)
	{
		$uri = RecurlyClient::PATH_ACCOUNTS . urlencode($accountCode) . RecurlyClient::PATH_ACCOUNT_SUBSCRIPTION;
		$result = RecurlyClient::__sendRequest($uri, 'DELETE');
		if (preg_match("/^2..$/", $result->code)) {
			return true;
		} else if (strpos($result->response, '<errors>') > 0 && $result->code == 422) {
			throw new RecurlyValidationException($result->code, $result->response);
		} else {
			throw new RecurlyException("Could not cancel the subscription for {$accountCode}: {$result->response} ({$result->code})");
		}
	}
	
	public static function refundSubscription($accountCode, $fullRefund = false)
	{
		$uri = RecurlyClient::PATH_ACCOUNTS . urlencode($accountCode) . RecurlyClient::PATH_ACCOUNT_SUBSCRIPTION;
		$uri .= '?refund=' . ($fullRefund ? 'full' : 'partial');
		$result = RecurlyClient::__sendRequest($uri, 'DELETE');
		if (preg_match("/^2..$/", $result->code)) {
			return true;
		} else if (strpos($result->response, '<errors>') > 0 && $result->code == 422) {
			throw new RecurlyValidationException($result->code, $result->response);
		} else {
			throw new RecurlyException("Could not refund the subscription for {$accountCode}: {$result->response} ({$result->code})");
		}
	}

    // Change the subscription 'now' or at 'renewal'.
	// Set a value to change it. Leave it as null otherwise.
	public static function changeSubscription($accountCode, $timeframe = 'now', $newPlanCode = null, $newQuantity = null, $newUnitAmount = null)
	{
	    if (!($timeframe == 'now' || $timeframe == 'renewal'))
	        throw new RecurlyException("The timeframe must be 'now' or 'renewal'.");
	    
        $uri = RecurlyClient::PATH_ACCOUNTS . urlencode($accountCode) . RecurlyClient::PATH_ACCOUNT_SUBSCRIPTION;
		$data = RecurlySubscription::getChangeSubscriptionXml($timeframe, $newPlanCode, $newQuantity, $newUnitAmount);
		$result = RecurlyClient::__sendRequest($uri, 'PUT', $data);
		if (preg_match("/^2..$/", $result->code)) {
			return true;
		} else if (strpos($result->response, '<errors>') > 0 && $result->code == 422) {
			throw new RecurlyValidationException($result->code, $result->response);
		} else {
			throw new RecurlyException("Could not change the subscription for {$accountCode}: {$result->response} ({$result->code})");
		}
	}
	
	public function getXml()
	{
		$doc = new DOMDocument("1.0");
		$this->populateXmlDoc($doc);		
		return $doc->saveXML();
	}
	
	public function populateXmlDoc(&$doc)
	{
		$root = $doc->appendChild($doc->createElement("subscription"));
		$root->appendChild($doc->createElement("plan_code", $this->plan_code));
		
		if (isset($this->quantity))
			$root->appendChild($doc->createElement("quantity", $this->quantity));
		
		if (isset($this->unit_amount))
			$root->appendChild($doc->createElement("unit_amount", $this->unit_amount));
		
		$account_node = $this->account->populateXmlDoc($doc, $root);
		$this->billing_info->populateXmlDoc($doc, $account_node);

		return $root;
	}
	
	public static function getChangeSubscriptionXml($timeframe, $newPlanCode, $newQuantity, $newUnitAmount)
	{
	    print "<!-- Change VARS: $newPlanCode, $newQuantity, $newUnitAmount -->\n";
		
	    $doc = new DOMDocument("1.0");
		$root = $doc->appendChild($doc->createElement("subscription"));
		$root->appendChild($doc->createElement("timeframe", $timeframe));
		
		if ($newPlanCode != null)
		    $root->appendChild($doc->createElement("plan_code", $newPlanCode));

		if ($newQuantity != null)
		    $root->appendChild($doc->createElement("quantity", $newQuantity));

		if ($newUnitAmount != null)
		    $root->appendChild($doc->createElement("unit_amount", $newUnitAmount));
		
		return $doc->saveXML();
	}
}