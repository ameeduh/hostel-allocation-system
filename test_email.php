<?php
require_once 'config/mail_config.php';

$result = sendEmail('ibuhidah@gmail.com', 'Test Email', '<h1>Success!</h1><p>Your email system is working.</p>');

if($result) {
    echo "✅ Email sent successfully!";
} else {
    echo "❌ Email failed. Check your App Password.";
}
?>