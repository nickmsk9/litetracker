<?php
require 'system/init.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

header('Location: details.php?id='.$id, true, 301);
die();
