<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$app_id = $_GET['id'] ?? 0;
if (!$app_id) {
    die('Не указан ID заявки');
}

$stmt = $pdo->prepare("SELECT * FROM travel_applications WHERE id = ?");
$stmt->execute([$app_id]);
$app = $stmt->fetch();

if (!$app) {
    die('Заявка не найдена');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $tour = $_POST['tour'];
    $message = trim($_POST['message']);

    if (empty($name)) {
        $error = 'Имя обязательно';
    } elseif (empty($email)) {
        $error = 'Email обязателен';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email';
    } elseif (empty($phone)) {
        $error = 'Телефон обязателен';
    } elseif (empty($tour)) {
        $error = 'Выберите тур';
    }

    if (!$error) {
        try {
            $stmt = $pdo->prepare("
                UPDATE travel_applications 
                SET name = ?, email = ?, phone = ?, tour = ?, message = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $email, $phone, $tour, $message, $app_id]);
            $success = '✅ Заявка успешно обновлена';
            
            $app['name'] = $name;
            $app['email'] = $email;
            $app['phone'] = $phone;
            $app['tour'] = $tour;
            $app['message'] = $message;
        } catch (PDOException $e) {
            $error = 'Ошибка при сохранении';
        }
    }
}

$tour_options = [
    '' => 'Выберите тур',
    'paris' => 'Романтический Париж',
    'japan' => 'Загадочная Япония',
    'iceland' => 'Исландия: земля льдов и огня',
    'thailand' => 'Тропический Таиланд',
    'other' => 'Другой вариант'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование заявки #<?= $app_id ?> - WorldTravel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 700px; margin: 0 auto; background: white; border-radius: 15px;
            padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #667eea; }
        .admin-header h2 { color: #333; margin: 0; }
        .back-link { color: #667eea; text-decoration: none; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50; }
        .required::after { content: " *"; color: #e74c3c; }
        input, select, textarea {
            width: 100%; padding: 12px; border: 2px solid #e1e1e1;
            border-radius: 8px; font-size: 16px; font-family: inherit;
        }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #667eea; }
        textarea { resize: vertical; min-height: 100px; }
        .buttons { display: flex; gap: 15px; margin-top: 25px; }
        button { flex: 1; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; }
        .cancel-btn { background: #6c757d; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; color: white; }
        .success-message { background: #d4edda; color: #155724; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .error-message { background: #f8d7da; color: #721c24; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        .info-block {
            background: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .info-block strong {
            color: #4a6fa5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-header">
            <h2>✏️ Редактирование заявки #<?= $app_id ?></h2>
            <a href="admin.php" class="back-link">← Назад</a>
        </div>
        
        <div class="info-block">
            <strong>🔐 Логин пользователя:</strong> <?= htmlspecialchars($app['login']) ?><br>
            <strong>📅 Дата создания:</strong> <?= date('d.m.Y H:i', strtotime($app['created_at'])) ?>
        </div>
        
        <?php if ($success): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label class="required">Имя:</label>
                <input type="text" name="name" value="<?= htmlspecialchars($app['name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label class="required">Email:</label>
                <input type="email" name="email" value="<?= htmlspecialchars($app['email']) ?>" required>
            </div>
            
            <div class="form-group">
                <label class="required">Телефон:</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($app['phone']) ?>" required>
                <small style="color: #666; font-size: 12px;">Формат: +7XXXXXXXXXX или 8XXXXXXXXXX</small>
            </div>
            
            <div class="form-group">
                <label class="required">Интересующий тур:</label>
                <select name="tour" required>
                    <?php foreach ($tour_options as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $app['tour'] == $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Сообщение:</label>
                <textarea name="message" rows="4"><?= htmlspecialchars($app['message'] ?? '') ?></textarea>
            </div>
            
            <div class="buttons">
                <button type="submit">💾 Сохранить изменения</button>
                <a href="admin.php" class="cancel-btn">❌ Отмена</a>
            </div>
        </form>
    </div>
</body>
</html>