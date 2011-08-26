<?php

require_once 'IPay88.class.php';

$ipay88 = new IPay88(/*YOUR_MERCHANT_CODE*/);

$ipay88->setMerchantKey(/*YOUR_MERCHANT_KEY*/);

$ipay88->setField('PaymentId', 2);
$ipay88->setField('RefNo', 'IPAY0000000001');
$ipay88->setField('Amount', '1.00');
$ipay88->setField('Currency', 'myr');
$ipay88->setField('ProdDesc', 'Testing');
$ipay88->setField('UserName', 'Your name');
$ipay88->setField('UserEmail', 'email@example.com');
$ipay88->setField('UserContact', '0123456789');
$ipay88->setField('Remark', 'Some remarks here..');
$ipay88->setField('Lang', 'utf-8');
$ipay88->setField('ResponseURL', 'http://yourwebsite.com/ipay88/response');

$ipay88->generateSignature();

$ipay88_fields = $ipay88->getFields();

//echo $ipay88->requery(array('MerchantCode' => /*YOUR_MERCHANT_CODE*/, 'RefNo' => 'IPAY0000000001', 'Amount' => '1.00'));

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>iPay88 - Test - Request</title>
</head>

<body>
  <h1>iPay88 payment gateway</h1>

  <?php if (!empty($ipay88_fields)): ?>
    <form action="<?php echo Ipay88::$epayment_url; ?>" method="post">
      <table>
        <?php foreach ($ipay88_fields as $key => $val): ?>
          <tr>
            <td><label><?php echo $key; ?></label></td>
            <td><input type="text" name="<?php echo $key; ?>" value="<?php echo $val; ?>" /></td>
          </tr>
        <?php endforeach; ?>
        <tr>
          <td colspan="2"><input type="submit" value="Submit" name="Submit" /></td>
        </tr>
      </table>
    </form>
  <?php endif; ?>
</body>

</html>