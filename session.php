<?php
// Set session cookie to last for 30 days
$cookie_lifetime = 60 * 60 * 24 * 30; // 30 days in seconds
session_set_cookie_params($cookie_lifetime);
session_start();


?>
