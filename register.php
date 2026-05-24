<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'errors' => ['general' => 'Нет данных']]);
    exit;
}

$errors = [];

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$phone = trim($data['phone'] ?? '');
$tour = trim($data['tour'] ?? '');
$message = trim($data['message'] ?? '');

if (empty($name)) {
    $errors['name'] = 'Имя обязательно для заполнения';
} elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $name)) {
    $errors['name'] = 'Имя может содержать только буквы, пробелы и дефисы';
}

if (empty($email)) {
    $errors['email'] = 'Email обязателен для заполнения';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Введите корректный email (например: name@domain.com)';
}

if (empty($phone)) {
    $errors['phone'] = 'Телефон обязателен для заполнения';
} elseif (!preg_match('/^(\+7|8)[0-9]{10}$/', $phone)) {
    $errors['phone'] = 'Телефон должен быть в формате +7XXXXXXXXXX или 8XXXXXXXXXX (10 цифр после кода)';
}

if (empty($tour)) {
    $errors['tour'] = 'Выберите интересующий тур';
}

// Сохраняем ВСЕ введённые данные в cookies (даже если есть ошибки)
setcookie('saved_name', $name, time() + 365 * 24 * 3600, '/');
setcookie('saved_email', $email, time() + 365 * 24 * 3600, '/');
setcookie('saved_phone', $phone, time() + 365 * 24 * 3600, '/');
setcookie('saved_tour', $tour, time() + 365 * 24 * 3600, '/');
setcookie('saved_message', $message, time() + 365 * 24 * 3600, '/');

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

$result = saveApplication($pdo, ['name' => $name, 'email' => $email, 'phone' => $phone, 'tour' => $tour, 'message' => $message]);

echo json_encode(['success' => true, 'data' => $result]);
?>