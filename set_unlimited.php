<?php
include('conn.php');
kq("UPDATE `users` SET `saldo` = 999999999 WHERE `level` = 1");
echo "Owner saldo set to unlimited (999999999)";
unlink(__FILE__);
?>
