<?php
// actions/contact/send_message.php
require_once '../../config/db.php';
require_once '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // If this is an AJAX call, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
        exit;
    }
    redirect(SITE_URL . '/contact.php');
}

$name = sanitize($_POST['name'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$subject = sanitize($_POST['subject'] ?? '');
$message = sanitize($_POST['message'] ?? '');

// If user is logged in, attach their user_id
$user_id = isLoggedIn() ? $_SESSION['user_id'] : null;

// Validation
if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }
    setFlashMessage('error', 'All fields are required.');
    redirect(SITE_URL . '/contact.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Please provide a valid email address.']);
        exit;
    }
    setFlashMessage('error', 'Please provide a valid email address.');
    redirect(SITE_URL . '/contact.php');
}

try {
    // Insert without `user_id` to match `contact_messages` table schema
    $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $subject, $message]);

    // Send an email notification to the admin with message details
    $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@localhost';
    $emailSubject = "[" . SITE_NAME . "] New contact message: " . $subject;
    $submittedAt = date('Y-m-d H:i:s');
    $emailBody = "You have received a new contact message from the website.<br><br>" .
                 "<strong>Name:</strong> " . htmlspecialchars($name) . "<br>" .
                 "<strong>Email:</strong> " . htmlspecialchars($email) . "<br>" .
                 "<strong>Subject:</strong> " . htmlspecialchars($subject) . "<br>" .
                 "<strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "<br><br>" .
                 "<em>Submitted at: " . $submittedAt . "</em>";

    // If PHPMailer is installed and config requests SMTP, use it. Otherwise fallback to mail().
    $mailSent = false;
    $mailConfigPath = __DIR__ . '/../../config/mail.php';
    if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
        // Load composer autoloader
        require_once __DIR__ . '/../../vendor/autoload.php';

        // Load mail config if present
        $mailConfig = file_exists($mailConfigPath) ? require $mailConfigPath : [];

        // Only attempt SMTP if configured
        if (!empty($mailConfig) && !empty($mailConfig['use_smtp'])) {
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                // SMTP configuration
                $mail->isSMTP();
                $mail->Host = $mailConfig['smtp']['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $mailConfig['smtp']['username'];
                $mail->Password = $mailConfig['smtp']['password'];
                $mail->SMTPSecure = $mailConfig['smtp']['encryption'];
                $mail->Port = $mailConfig['smtp']['port'];

                $mail->setFrom($mailConfig['from_email'] ?? 'no-reply@localhost', $mailConfig['from_name'] ?? SITE_NAME);
                $mail->addAddress($adminEmail);
                $mail->addReplyTo($email, $name);

                $mail->isHTML(true);
                $mail->Subject = $emailSubject;
                $mail->Body    = $emailBody;

                $mail->send();
                $mailSent = true;
            } catch (Exception $e) {
                // PHPMailer failed; fallback to mail()
                $mailSent = false;
            }
        }
    }

    if (!$mailSent) {
        // Fallback to PHP mail() with plain text
        $plainBody = "You have received a new contact message from the website.\n\n" .
                     "Name: " . $name . "\n" .
                     "Email: " . $email . "\n" .
                     "Subject: " . $subject . "\n" .
                     "Message:\n" . $message . "\n\n" .
                     "Submitted at: " . $submittedAt . "\n";

        $headers = "From: " . SITE_NAME . " <no-reply@localhost>\r\n" .
                   "Reply-To: " . $email . "\r\n" .
                   "Content-Type: text/plain; charset=UTF-8\r\n";

        @mail($adminEmail, $emailSubject, $plainBody, $headers);
    }

    // If AJAX request, return JSON for toast
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['status' => 'success', 'message' => 'Your message has been sent successfully. We will get back to you shortly!']);
        exit;
    }

    setFlashMessage('success', 'Your message has been sent successfully. We will get back to you shortly!');
    redirect(SITE_URL . '/contact.php');

} catch (PDOException $e) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'An error occurred while sending your message. Please try again later.']);
        exit;
    }
    setFlashMessage('error', 'An error occurred while sending your message. Please try again later.');
    redirect(SITE_URL . '/contact.php');
}
