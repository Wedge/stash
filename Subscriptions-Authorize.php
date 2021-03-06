<?php
/**
 * Pluggable payment gateway for subscriptions paid through Authorize.net.
 *
 * @package Wedge
 * @copyright 2010 René-Gilles Deberdt, wedge.org
 * @license http://wedge.org/license/
 * @author see contributors.txt
 */

// This won't be dedicated without this - this must exist in each gateway!
// Wedge Payment Gateway: authorize

if (!defined('WEDGE'))
	die('Hacking attempt...');

class authorize_display
{
	public $title = 'Authorize.net | Credit Card';

	// Basic settings that we need.
	public function getGatewaySettings()
	{
		global $txt;

		$setting_data = array(
			array('text', 'authorize_id', 'subtext' => $txt['authorize_id_desc']),
			array('text', 'authorize_transid'),
		);

		return $setting_data;
	}

	// Is it enabled?
	public function gatewayEnabled()
	{
		global $settings;

		return !empty($settings['authorize_id']) && !empty($settings['authorize_transid']);
	}

	// Let's set up the fields needed for the transaction.
	public function fetchGatewayFields($unique_id, $sub_data, $value, $period, $return_url)
	{
		global $settings, $txt, $boardurl;

		$return_data = array(
			'form' => 'https://' . (empty($settings['paidsubs_test']) ? 'secure' : 'test') . '.authorize.net/gateway/transact.dll',
			'id' => 'authorize',
			'hidden' => array(),
			'title' => $txt['authorize'],
			'desc' => $txt['paid_confirm_authorize'],
			'submit' => $txt['paid_authorize_order'],
			'javascript' => '',
		);

		$timestamp = time();
		$sequence = substr(time(), -5);
		$hash = $this->_md5_hmac($settings['authorize_transid'], $settings['authorize_id'] . '^' . $sequence . '^' . $timestamp . '^' . $value . '^' . strtoupper($settings['paid_currency_code']));

		$return_data['hidden']['x_login'] = $settings['authorize_id'];
		$return_data['hidden']['x_amount'] = $value;
		$return_data['hidden']['x_currency_code'] = strtoupper($settings['paid_currency_code']);
		$return_data['hidden']['x_show_form'] = 'PAYMENT_FORM';
		$return_data['hidden']['x_test_request'] = empty($settings['paidsubs_test']) ? 'FALSE' : 'TRUE';
		$return_data['hidden']['x_fp_sequence'] = $sequence;
		$return_data['hidden']['x_fp_timestamp'] = $timestamp;
		$return_data['hidden']['x_fp_hash'] = $hash;
		$return_data['hidden']['x_invoice_num'] = $unique_id;
		$return_data['hidden']['x_email'] = we::$user['email'];
		$return_data['hidden']['x_type'] = 'AUTH_CAPTURE';
		$return_data['hidden']['x_cust_id'] = we::$user['name'];
		$return_data['hidden']['x_relay_url'] = $boardurl . '/subscriptions.php';
		$return_data['hidden']['x_receipt_link_url'] = $return_url;

		return $return_data;
	}

	// A private function to generate the hash.
	private function _md5_hmac($key, $data)
	{
		$key = str_pad(strlen($key) <= 64 ? $key : pack('H*', md5($key)), 64, chr(0x00));
		return md5(($key ^ str_repeat(chr(0x5c), 64)) . pack('H*', md5(($key ^ str_repeat(chr(0x36), 64)) . $data)));
	}
}

class authorize_payment
{
	private $return_data;

	public function isValid()
	{
		global $settings;

		// Is it even on?
		if (empty($settings['authorize_id']) || empty($settings['authorize_transid']))
			return false;
		// We got a hash?
		if (empty($_POST['x_MD5_Hash']))
			return false;
		// Do we have an invoice number?
		if (empty($_POST['x_invoice_num']))
			return false;
		if (empty($_POST['x_response_code']))
			return false;

		return true;
	}

	// Validate this is valid for this transaction type.
	public function precheck()
	{
		global $settings;

		// Is this the right hash?
		if ($_POST['x_MD5_Hash'] != strtoupper(md5($settings['authorize_id'] . $_POST['x_trans_id'] . $_POST['x_amount'])))
			exit;

		// Can't exist if it doesn't contain anything.
		if (empty($_POST['x_invoice_num']))
			exit;

		// Verify the currency
		$currency = $_POST['x_currency_code'];

		// Verify the currency!
		if (strtolower($currency) != $settings['currency_code'])
			exit;

		// Return the ID_SUB/ID_MEMBER
		return explode('+', $_POST['x_invoice_num']);
	}

	// Is this a refund?
	public function isRefund()
	{
		return false;
	}

	// Is this a subscription?
	public function isSubscription()
	{
		return false;
	}

	// Is this a normal payment?
	public function isPayment()
	{
		if ($_POST['x_response_code'] == 1)
			return true;
		else
			return false;
	}

	// How much was paid?
	public function getCost()
	{
		return $_POST['x_amount'];
	}

	// Redirect the user away.
	public function close()
	{
		exit;
	}
}
