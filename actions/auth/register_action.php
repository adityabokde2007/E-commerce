<?php
// actions/auth/register_action.php

require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    // 1. Sanitize and retrieve inputs
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // 2. Server-side validation
    $errors = [];
    
    // Name validation
    if (empty($name)) {
        $errors[] = "Full Name is required.";
    }
    
    // Email validation
    if (empty($email)) {
        $errors[] = "Email Address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    
    // Phone validation (Basic check to ensure it contains numbers, allow some common symbols)
    if (empty($phone)) {
        $errors[] = "Phone Number is required.";
    } elseif (!preg_match("/^[0-9\-\+\(\)\s]{7,15}$/", $phone)) {
        $errors[] = "Invalid phone number format.";
    }
    
    // Password validation
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    // If there are validation errors, set flash message and redirect back
    if (!empty($errors)) {
        // We'll just display the first error for simplicity
        if ($isAjax) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => $errors[0]]);
            exit;
        }
        setFlashMessage('error', $errors[0]);
        redirect(SITE_URL . '/register.php');
    }
    
    try {
        // 3. Check for email uniqueness
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            // Email already exists
            if ($isAjax) {
                http_response_code(409);
                echo json_encode(['status' => 'error', 'message' => 'An account with this email address already exists.']);
                exit;
            }
            setFlashMessage('error', "An account with this email address already exists.");
            redirect(SITE_URL . '/register.php');
        }
        
        // 4. Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // 5. Insert the new user into the database
        $insert_stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role, status) VALUES (?, ?, ?, ?, 'customer', 'active')");
        $success = $insert_stmt->execute([$name, $email, $hashed_password, $phone]);
        
        if ($success) {
            // 6. Auto-login the new user and send them to shopping
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'customer';

            if ($isAjax) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Registration successful! Redirecting to shop.',
                    'redirect' => SITE_URL . '/shop.php'
                ]);
                exit;
            }
            setFlashMessage('success', "Registration successful! Redirecting to shop.");
            redirect(SITE_URL . '/shop.php');
        } else {
            // Insertion failed
            if ($isAjax) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Something went wrong. Please try again later.']);
                exit;
            }
            setFlashMessage('error', "Something went wrong. Please try again later.");
            redirect(SITE_URL . '/register.php');
        }
        
    } catch (PDOException $e) {
        // Handle database errors safely
        if ($isAjax) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
            exit;
        }
        setFlashMessage('error', "Database Error: " . $e->getMessage());
        redirect(SITE_URL . '/register.php');
    }
    
} else {
    // If someone tries to access this file directly via GET
    redirect(SITE_URL . '/register.php');
}
