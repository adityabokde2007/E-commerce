<?php
// actions/auth/login_action.php

require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    // 1. Sanitize and retrieve inputs
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // 2. Server-side validation
    if (empty($email) || empty($password)) {
        if ($isAjax) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Both email and password are required.']);
            exit;
        }
        setFlashMessage('error', "Both email and password are required.");
        redirect(SITE_URL . '/login.php');
    }
    
    try {
        // 3. Fetch user by email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            // 4. Verify password
            if (password_verify($password, $user['password'])) {
                
                // 5. Check if user is blocked
                if ($user['status'] === 'blocked') {
                    if ($isAjax) {
                        http_response_code(403);
                        echo json_encode(['status' => 'error', 'message' => 'Your account has been blocked. Please contact support.']);
                        exit;
                    }
                    setFlashMessage('error', "Your account has been blocked. Please contact support.");
                    redirect(SITE_URL . '/login.php');
                }
                
                // 6. Regenerate session ID to prevent session fixation attacks
                session_regenerate_id(true);
                
                // 7. Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Set success message
                setFlashMessage('success', "Welcome back, " . $user['name'] . "!");

                if ($isAjax) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Welcome back, ' . $user['name'] . '!',
                        'redirect' => ($user['role'] === 'admin') ? SITE_URL . '/admin/index.php' : SITE_URL . '/index.php'
                    ]);
                    exit;
                }
                
                // 8. Redirect based on role
                if ($user['role'] === 'admin') {
                    redirect(SITE_URL . '/admin/index.php');
                } else {
                    redirect(SITE_URL . '/index.php');
                }
                
            } else {
                // Invalid password
                if ($isAjax) {
                    http_response_code(401);
                    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
                    exit;
                }
                setFlashMessage('error', "Invalid email or password.");
                redirect(SITE_URL . '/login.php');
            }
            
        } else {
            // Email not found (we use the same error message to avoid email enumeration)
            if ($isAjax) {
                http_response_code(401);
                echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
                exit;
            }
            setFlashMessage('error', "Invalid email or password.");
            redirect(SITE_URL . '/login.php');
        }
        
    } catch (PDOException $e) {
        // Handle database errors safely
        if ($isAjax) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
            exit;
        }
        setFlashMessage('error', "Database Error: " . $e->getMessage());
        redirect(SITE_URL . '/login.php');
    }
    
} else {
    // If someone tries to access this file directly via GET
    redirect(SITE_URL . '/login.php');
}
