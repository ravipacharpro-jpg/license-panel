<?php
// api/tg_api.php - Telegram API stub (route exists in index.php).
// Full Telegram OTP flow is optional; this keeps the route from fatal-erroring.
header('Content-Type: application/json; charset=UTF-8');
http_response_code(501);
echo json_encode(['status' => 'error', 'message' => 'Telegram API not configured. Set TELEGRAM_BOT_TOKEN in .env / environment.']);
