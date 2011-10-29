<?php

/**
 * @file    Integrate Ipay88 (Malaysia) payment gateway system.
 * @date    2011-08-24
 * @version 1.0
 * @author  Leow Kah Thong <http://kahthong.com>
 */
class IPay88 {

  // Payment methods, please view technical spec for latest update.
  public static $payment_methods = array(
    array(54, 'Alipay', 'USD'),
    array(8, 'Alliance Online Transfer', 'MYR'),
    array(10, 'AmBank', 'MYR'),
    array(21, 'China Union Pay', 'MYR'),
    array(20, 'CIMB Clicks', 'MYR'),
    array(39, 'Credit Card', 'AUD'),
    array(37, 'Credit Card', 'CAD'),
    array(41, 'Credit Card', 'EUR'),
    array(35, 'Credit Card', 'GBP'),
    array(42, 'Credit Card', 'HKD'),
    array(46, 'Credit Card', 'IDR'),
    array(45, 'Credit Card', 'INR'),
    array(2, 'Credit Card', 'MYR'),
    array(40, 'Credit Card', 'MYR'), // For multi-currency only
    array(47, 'Credit Card', 'PHP'),
    array(38, 'Credit Card', 'SGD'),
    array(36, 'Credit Card', 'THB'),
    array(50, 'Credit Card', 'TWD'),
    array(25, 'Credit Card', 'USD'),
    array(16, 'FPX', 'MYR'),
    array(15, 'Hong Leong Bank Transfer', 'MYR'),
    array(6, 'Maybank2U', 'MYR'),
    array(23, 'Meps Cash', 'MYR'),
    array(17, 'Mobile Money', 'MYR'),
    array(32, 'Payeasy', 'PHP'),
    array(65, 'PayPal', 'AUD'),
    array(63, 'PayPal', 'CAD'),
    array(66, 'PayPal', 'EUR'),
    array(61, 'PayPal', 'GBP'),
    array(67, 'PayPal', 'HKD'),
    array(48, 'PayPal', 'MYR'),
    array(56, 'PayPal', 'PHP'),
    array(64, 'PayPal', 'SGD'),
    array(62, 'PayPal', 'THB'),
    array(68, 'PayPal', 'TWD'),
    array(33, 'PayPal', 'USD'),
    array(53, 'Paysbuy (Credit Card only)', 'THB'),
    array(52, 'Paysbuy (E-wallet & Counter Services only)', 'THB'),
    array(14, 'RHB', 'MYR'),
  );

  public static $epayment_url = 'https://www.mobile88.com/epayment/entry.asp';
  public static $requery_url = 'https://www.mobile88.com/epayment/enquiry.asp';
  public static $referer_url = 'www.mobile88.com'; // without scheme (http/https)

  private $merchant_key = '';  // Private key, do not share!

  // Details to be sent to iPay88 for payment request.
  private $payment_request = array(
    'MerchantCode' => '',  // Merchant code assigned by iPay88. (varchar 20)
    'PaymentId'    => '',  // (Optional) (int)
    'RefNo'        => '',  // Unique merchant transaction number / Order ID (Retry for same RefNo only valid for 30 mins). (varchar 20)
    'Amount'       => '',  // Payment amount with two decimals.
    'Currency'     => '',  // (varchar 5)
    'ProdDesc'     => '',  // Product description. (varchar 100)
    'UserName'     => '',  // Customer name. (varchar 100)
    'UserEmail'    => '',  // Customer email.  (varchar 100)
    'UserContact'  => '',  // Customer contact.  (varchar 20)
    'Remark'       => '',  // (Optional) Merchant remarks. (varchar 100)
    'Lang'         => 'UTF-8',  // (Optional) Encoding type:- ISO-8859-1 (English), UTF-8 (Unicode), GB2312 (Chinese Simplified), GD18030 (Chinese Simplified), BIG5 (Chinese Traditional)
    'Signature'    => '',  // SHA1 signature.
    'ResponseURL'  => '',  // (Optional) Payment response page.
  );

  /* Return response from iPay88:
   * - MerchantCode -
   * - PaymentId    - (Optional)
   * - RefNo        -
   * - Amount       -
   * - Currency     -
   * - Remark       - (Optional)
   * - TransId      - (Optional) IPay88 transaction Id. (varchar 30)
   * - AuthCode     - (Optional) Bank's approval code. (varchar 10)
   * - Status       - Payment status:- 1 - Success, 0 - Failed. (varchar 1)
   * - ErrDesc      - (Optional) Payment status description. (varchar 100)
   * - Signature    -
   */

  /**
   * Create a new IPay88 object.
   *
   * @param string $merchant_code (Optional) Merchant code supplied by Ipay88. This must be set before you can make payments.
   */
  public function __construct($merchant_code = '') {
    if ($merchant_code) {
      $this->setField('MerchantCode', $merchant_code);
    }
  }

  /**
   * Validate the data given by user according to the rules specified by iPay88 API.
   *
   * @param string $field The field to check.
   * @param string $data  Data supplied by user.
   *
   * @return boolean TRUE if passed validation and vice-versa.
   */
  public function validateField($field, $data) {
    switch ($field) {
      case 'MerchantCode':
      case 'RefNo':
      case 'UserContact':
        if (strlen($data) <= 20) return TRUE;
        break;
      case 'PaymentId':
        if (is_int($data)) return TRUE;
        break;
      case 'Amount':
        if (preg_match('^[0-9]+\.[0-9]{2}$^', $data)) return TRUE;
        break;
      case 'Currency':
        if (strlen($data) <= 5) return TRUE;
        break;
      case 'ProdDesc':
      case 'UserName':
      case 'UserEmail':
      case 'Remark':
        if (strlen($data) <= 100) return TRUE;
        break;
      case 'Lang':
        if (in_array(strtoupper($data), array('ISO-8859-1', 'UTF-8', 'GB2312', 'GD18030', 'BIG5'))) return TRUE;
        break;
      case 'Signature':
        if (strlen($data) <= 40) return TRUE;
        break;
      case 'MerchantKey':
      case 'ResponseURL':
      case 'TransId':
      case 'AuthCode':
      case 'Status':
      case 'ErrDesc':
        return TRUE;
        break;
    }

    return FALSE;
  }

  /**
   * Return all the fields (normally after setField() method is called).
   * Can be used to populate forms.
   *
   * @return array Payment method fields.
   */
  public function getFields() {
    return $this->payment_request;
  }

  /**
   * Return individual field values.
   *
   * @param string $field Field name.
   * @return string Value of the field. If field name is invalid, returns FALSE.
   */
  public function getField($field) {
    return (isset($this->payment_request[$field]) ? $this->payment_request[$field] : FALSE);
  }

  /**
   * Get info about payment method.
   *
   * @param int $payment_id Payment method ID.
   *
   * @return array Name and currency of payment method.
   */
  public function getPaymentMethod($payment_id) {
    foreach (self::$payment_methods as $val) {
      if ($val[0] === (int) $payment_id) {
        return array(
          'name' => isset($val[1]) ? trim($val[1]) : NULL,
          'currency' => isset($val[2]) ? strtoupper(trim($val[2])) : NULL,
        );
      }
    }
  }

  /**
   * Wrapper method to receive response and return status. If transaction was successful, a requery will be done to double-check.
   *
   * @param boolean $requery     Whether to requery Ipay88 server for transaction confirmation.
   * @param boolean $return_data Whether to return data back.
   *
   * @return array Status of the transaction and processed response.
   */
  public function getResponse($requery = TRUE, $return_data = TRUE) {
    $return = array(
      'status' => '',
      'message' => '',
      'data' => array(),
    );

    $data = $_POST;
    $return['status'] = isset($data['Status']) ? $data['Status'] : FALSE;
    $return['message'] = isset($data['ErrDesc']) ? $data['ErrDesc'] : '';

    if ($requery) {
      if ($return['status']) {
        $data['_RequeryStatus'] = $this->requery($data);
        if ($data['_RequeryStatus'] != '00') {
          // Requery failed, return NULL array.
          $return['status'] = FALSE;
          return $return;
        }
      }
    }

    if ($return_data) {
      $return['data'] = $data;
    }

    return $return;
  }

  /**
   * Set variable to field. Data supplied will be validated before it is set and any error found will be thrown to user.
   *
   * @param string $field The field name to set.
   * @param string $data  Data supplied by user.
   */
  public function setField($field, $data) {
    if ($this->validateField($field, $data)) {
      switch ($field) {
        case 'Currency':
        case 'Lang':
          $data = strtoupper($data);
          break;
      }

      $this->payment_request[$field] = $data;
    }
    else {
      // Return error message.
      $field = "<em>$field</em>";
      $error_msg = "Failed validation for $field. ";
      switch (strip_tags($field))  {
        case 'MerchantCode':
        case 'RefNo':
        case 'UserContact':
          $error_msg .= "$field must not be more than 20 characters in length.";
          break;
        case 'PaymentId':
          $error_msg .= "$field must be a number.";
          break;
        case 'Amount':
          $error_msg .= "$field must be a number with 2 decimal points.";
          break;
        case 'Currency':
          $error_msg .= "$field must not be more than 5 characters in length.";
          break;
        case 'ProdDesc':
        case 'UserName':
        case 'UserEmail':
        case 'Remark':
          $error_msg .= "$field must not be more than 100 characters in length.";
          break;
        case 'Lang':
          $langs = array('ISO-8859-1', 'UTF-8', 'GB2312', 'GD18030', 'BIG5');
          $error_msg .= "$field must be either " . implode(', ', $langs) . '.';
          break;
        case 'Signature':
          $error_msg .= "$field must not be more than 40 characters in length.";
          break;
      }
      trigger_error(trim($error_msg));
    }
  }

  /**
   * Separate method to set merchant key, not sharing with setField() due to privacy concern.
   *
   * @param string $key Private key for merchant.
   */
  public function setMerchantKey($key) {
    $this->merchant_key = $key;
  }

  /**
   * Generate signature to be used for transaction.
   *
   * You may verify your signature with online tool provided by iPay88
   * http://www.mobile88.com/epayment/testing/TestSignature.asp
   *
   * @param array $signature_params (Optional) Fields required to generate signature (MerchantKey is set via setMerchantKey() method). If not passed, will use values that were set earlier.
   * - MerchantCode
   * - RefNo
   * - Amount
   * - Currency
   */
  public function generateSignature($signature_params = array()) {
    $signature = '';

    if ($signature_params) {
      foreach (array('MerchantCode', 'RefNo', 'Amount', 'Currency') as $val) {
        if (!isset($signature_params[$val])) {
          trigger_error('Missing or invalid parameters required for signature.');
          return FALSE;
        }
      }

      foreach ($signature_params as $key => $val) {
        // Validate parameters for signature.
        if (!$this->validateField($key, $val)) {
          trigger_error('Missing or invalid parameters required for signature.');
          return FALSE;
        }

        // Some formatting..
        switch ($key) {
          case 'Amount':
            // Remove ',' and '.' from amount
            $signature_params[$key] = str_replace(',', '', $val);
            $signature_params[$key] = str_replace('.', '', $val);
            break;
          case 'Currency':
          case 'Lang':
            $signature_params[$key] = strtoupper($val);
            break;
        }
      }
    }
    else {
      $signature_params['MerchantCode'] = $this->getField('MerchantCode');
      $signature_params['RefNo'] = $this->getField('RefNo');
      $signature_params['Amount'] = str_replace('.', '', str_replace(',', '', $this->getField('Amount')));
      $signature_params['Currency'] = $this->getField('Currency');
    }

    if (!$this->merchant_key) {
      trigger_error('Merchant key is required.');
      return FALSE;
    }

    // Make sure the order is correct.
    $signature .= $this->merchant_key;
    $signature .= $signature_params['MerchantCode'];
    $signature .= $signature_params['RefNo'];
    $signature .= $signature_params['Amount'];
    $signature .= $signature_params['Currency'];

    // Hash the signature.
    $signature = base64_encode($this->_hex2bin(sha1($signature)));

    $this->setField('Signature', $signature);
  }

  /**
   * Referred from iPay88 technical specification v1.5.2.
   */
  private function _hex2bin($source) {
    $bin = '';
    for ($i = 0; $i < strlen($source); $i += 2) {
      $bin .= chr(hexdec(substr($source, $i, 2)));
    }
    return $bin;
  }

  /**
   * Receives response returned from iPay88 server after payment is processed.
   *
   * @param array $response Response returned from Ipay88 server after transaction is processed.
   *
   * @return boolean Only returns FALSE for failed transaction. You should only check for FALSE status.
   */
  public function validateResponse($response) {
    // Check referer, must be from mobile88.com only
    // Only valid if payment went through IPay88.
    $referer = parse_url($_SERVER['HTTP_REFERER']);
    if ($referer['host'] != self::$referer_url) {
      trigger_error('Referer check failed, mismatch with www.mobile88.com.');
      return FALSE;
    }

    // Check amount is same
    // TODO

    // Re-query to check payment
    if ($this->requery(array('MerchantCode' => $response['MerchantCode'], 'RefNo' => $response['RefNo'], 'Amount' => $response['Amount'])) != '00') {
      trigger_error('Requery with server failed to verify transaction.');
      return FALSE;
    }

    // Compare signature
    if ($this->generateSignature(array(
        'MerchantKey' => $this->merchant_key,
        'MerchantCode' => $response['MerchantCode'],
        'RefNo' => $response['RefNo'],
        'Amount' => $response['Amount'],
        'Currency' => $response['Currency'],
      )) != trim($response['Signature'])) {
      trigger_error('Failed to verify signature.');
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Check payment status (re-query).
   *
   * @param array $payment_details The following variables are required:
   * - MerchantCode
   * - RefNo
   * - Amount
   *
   * @return string Possible payment status from iPay88 server:
   * - 00                 - Successful payment
   * - Invalid parameters - Parameters passed is incorrect
   * - Record not found   - Could not find the record.
   * - Incorrect amount   - Amount differs.
   * - Payment fail       - Payment failed.
   * - M88Admin           - Payment status updated by Mobile88 Admin (Fail)
   */
  public function requery($payment_details) {
    if (!function_exists('curl_init')) {
      trigger_error('PHP cURL extension is required.');
      return FALSE;
    }

    $curl = curl_init(self::$requery_url . '?' . http_build_query($payment_details));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    $result = trim(curl_exec($curl));
    //$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return $result;
  }

}