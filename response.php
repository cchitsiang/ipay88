<?php

require_once 'IPay88.class.php';

$ipay88 = new IPay88(/*YOUR_MERCHANT_CODE*/);

$response = $ipay88->getResponse();

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>iPay88 - Test - Response</title>
</head>

<body>
  <h1>iPay88 payment gateway</h1>

  <?php if ($response['status']): ?>
    <p>Your transaction was successful.</p>
  <?php else: ?>
    <p>Your transaction failed.</p>
  <?php endif; ?>

  <table>
    <?php if ($response): ?>
      <?php foreach ($response['data'] as $key => $val): ?>
        <tr>
          <td><?php echo $key; ?></td>
          <td><?php echo $val; ?></td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr>
        <td colspan="2">No response or transaction failed.</td>
      </tr>
    <?php endif; ?>
  </table>
</body>

</html>