<?php
// config/mail.php

// Mail configuration used by PHPMailer
// NOTE: Composer (PHPMailer) must be installed for SMTP sending to work.
// Set 'use_smtp' => true and fill in your SMTP username/password (App Password for Gmail).
return [
    // Enable SMTP once PHPMailer is installed
    'use_smtp' => true,

    // SMTP settings (Gmail SMTP recommended)
    // To use Gmail SMTP, enable 2-Step Verification and create an App Password.
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'bokdeaditya77@gmail.com',
        'password' => 'ttdu kivl uxoz edmw',
        'encryption' => 'tls' // 'ssl' or 'tls'
    ],

    // From address used in outgoing emails
    'from_email' => 'bokdeaditya77@gmail.com',
    'from_name' => SITE_NAME,
];
