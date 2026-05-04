<?php
session_start();
session_unset();
session_destroy();
header('Location: /pastimes-marketplace-v2/index.php');
exit;
