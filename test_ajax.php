<?php
// test_ajax.php
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'update_info';
$_POST['name'] = 'Test';
$_POST['email'] = 'test@test.com';

// Mock session
session_start();
$_SESSION['user_id'] = 1;

require_once 'actions/profile/update_profile.php';
