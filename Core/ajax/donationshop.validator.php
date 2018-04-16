<?php //MODULO DE ISAAC...
session_start();
include_once('../controller.php');

if ( !isset($_SESSION['account_id']) || !isset($_POST['item_id']) )
{
	echo 'denied';
	exit;
}

$item_id = $_POST['item_id'];
$data_cache = $ITEM->get('ItemDB');
if ( !$data_cache )
{
	echo 'Cache Error Detected';
	exit;
}
	
if ($ITEM->__GETDB($item_id,'precio') <= 0)
{
	echo 'Well this is complicated, the items seems to have an error';
	exit;
} else
	$precio = $ITEM->__GETDB($item_id,'precio');

$consult = "SELECT `char_id` FROM `char` WHERE account_id=? AND `online` > 0";
$result = $DB->execute($consult, [$_SESSION['account_id']]);
if ($DB->num_rows())
{
	echo 'You have to log out in order to continue';
	exit;
}

$consult = 
"
	SELECT `value` 
	FROM ".
		($FNC->get_emulator() == EAMOD ?
		 	"`global_reg_value` WHERE `str`='".$CONFIG->getConfig('PayPal-Points')."'" :
		 	"`acc_reg_num` WHERE `key`='".$CONFIG->getConfig('PayPal-Points')."'"
		)."
		AND account_id=?
";

$result = $DB->execute($consult, [$_SESSION['account_id']]);
$row = $result->fetch();
if (!$row || $row['value'] < $precio)
{
	echo 'You don\'t have enough Dona Points';
	exit;
}

$donapoints_actuales = $row['value'];
$donapoints_actualizados = $row['value'] - $precio;
		
/*ALL OK: HORA DE COMPRAR ITEM! */
$consult = 
"
	UPDATE 
		".($FNC->get_emulator() == EAMOD ? "`global_reg_value`" : "`acc_reg_num`")." 
	SET 
		`value`=?
	WHERE ".($FNC->get_emulator() == EAMOD ? "`str`" : "`key`")."='".$CONFIG->getConfig('PayPal-Points')."'
	AND 
		account_id=?
";
$result = $DB->execute($consult, [$donapoints_actualizados, $_SESSION['account_id']]);
if ( !$result )
{
	echo 'Something wrong happened';
	exit;
}

$consult = "INSERT INTO `storage`(`account_id`,`nameid`,`amount`,`identify`) VALUES(?, ?, 1, 1)";
$result = $DB->execute($consult, [$_SESSION['account_id'], $item_id]);
if ( !$result )
	echo 'Something went wrong, Your Donapoints has been taken';
else 
	echo 'ok';
exit;
?>