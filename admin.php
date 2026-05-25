<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$admin_login = $_SESSION['admin_login'];
$message = '';
$error = '';

// Удаление заявки
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM travel_applications WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Заявка #$id успешно удалена";
    } catch (PDOException $e) {
        $error = "Ошибка при удалении";
    }
}

// Выход
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

// Получение всех заявок
$applications = $pdo->query("SELECT * FROM travel_applications ORDER BY id DESC")->fetchAll();
$total_count = count($applications);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора - WorldTravel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        .header {
            background: white; padding: 20px 30px; border-radius: 15px; margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); display: flex; justify-content: space-between;
            align-items: center; flex-wrap: wrap; gap: 15px;
        }
        .header h1 { color: #333; font-size: 1.8em; }
        .admin-info { color: #666; }
        .admin-info strong { color: #667eea; }
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            margin-left: 15px;
        }
        .logout-btn:hover { background: #c82333; }
        .stats-container {
            background: white; padding: 20px 30px; border-radius: 15px; margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .stats-container h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 15px;
            border-radius: 10px; text-align: center;
        }
        .stat-card .stat-number { font-size: 28px; font-weight: bold; }
        .stat-card .stat-label { font-size: 14px; margin-top: 5px; }
        .message { background: #d4edda; color: #155724; padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        .table-container { background: white; border-radius: 15px; overflow-x: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e1e1e1; }
        th { background: #f8f9fa; color: #333; font-weight: 600; }
        tr:hover { background: #f5f5f5; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 6px 12px; border-radius: 5px; text-decoration: none; font-size: 13px; transition: all 0.2s; }
        .btn-edit { background: #ffc107; color: #333; }
        .btn-edit:hover { background: #e0a800; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-delete:hover { background: #c82333; }
        .back-link { display: inline-block; margin-top: 20px; padding: 12px 24px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; }
        .empty-row td { text-align: center; padding: 40px; color: #999; }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            background: #e9ecef;
            border-radius: 12px;
            font-size: 12px;
        }
        .tour-badge {
            background: #4a6fa5;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌍 Панель администратора WorldTravel</h1>
            <div class="admin-info">
                Вы вошли как: <strong><?= htmlspecialchars($admin_login) ?></strong>
                <a href="?logout=1" class="logout-btn" onclick="return confirm('Выйти из админ-панели?')">🚪 Выход</a>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="stats-container">
            <h2>📊 Статистика</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $total_count ?></div>
                    <div class="stat-label">Всего заявок</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $pdo->query("SELECT COUNT(DISTINCT tour) FROM travel_applications WHERE tour != ''")->fetchColumn() ?></div>
                    <div class="stat-label">Уникальных туров</div>
                </div>
            </div>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Логин</th>
                        <th>Имя</th>
                        <th>Email</th>
                        <th>Телефон</th>
                        <th>Тур</th>
                        <th>Сообщение</th>
                        <th>Дата</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($applications)): ?>
                        <tr class="empty-row">
                            <td colspan="9">Нет заявок для отображения</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td><?= $app['id'] ?></td>
                                <td><span class="badge"><?= htmlspecialchars($app['login']) ?></span></td>
                                <td><?= htmlspecialchars($app['name']) ?></td>
                                <td><?= htmlspecialchars($app['email']) ?></td>
                                <td><?= htmlspecialchars($app['phone']) ?></td>
                                <td>
                                    <?php
                                    $tour_name = '';
                                    if ($app['tour'] == 'paris') $tour_name = 'Париж';
                                    elseif ($app['tour'] == 'japan') $tour_name = 'Япония';
                                    elseif ($app['tour'] == 'iceland') $tour_name = 'Исландия';
                                    elseif ($app['tour'] == 'thailand') $tour_name = 'Таиланд';
                                    else $tour_name = $app['tour'];
                                    ?>
                                    <span class="badge tour-badge"><?= htmlspecialchars($tour_name) ?></span>
                                </td>
                                <td><?= htmlspecialchars(substr($app['message'] ?? '', 0, 50)) ?></td>
                                <td><?= date('d.m.Y', strtotime($app['created_at'])) ?></td>
                                <td class="actions">
                                    <a href="admin_edit.php?id=<?= $app['id'] ?>" class="btn btn-edit">✏️ Ред.</a>
                                    <a href="?delete=<?= $app['id'] ?>" class="btn btn-delete" onclick="return confirm('Удалить заявку?')">🗑️ Удалить</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <a href="index.html" class="back-link">← Вернуться на главную</a>
    </div>
</body>
</html>