<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    
    if ($id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM travel_applications WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Ошибка БД']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Не указан ID']);
    }
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

$admin_login = $_SESSION['admin_login'];
$applications = $pdo->query("SELECT * FROM travel_applications ORDER BY id DESC")->fetchAll();
$total_count = count($applications);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель - WorldTravel</title>
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
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;
        }
        .logout-btn {
            background: #dc3545; color: white; padding: 8px 16px;
            border-radius: 8px; text-decoration: none;
        }
        .table-container { background: white; border-radius: 15px; overflow-x: auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .btn-delete { background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; }
        .btn-edit { background: #ffc107; color: #333; padding: 5px 10px; border-radius: 5px; text-decoration: none; }
        .message, .error { display: none; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .message { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .back-link { display: inline-block; margin-top: 20px; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌍 Админ-панель WorldTravel</h1>
            <div>
                Вы: <strong><?= htmlspecialchars($admin_login) ?></strong>
                <a href="?logout=1" class="logout-btn" onclick="return confirm('Выйти?')">🚪 Выход</a>
            </div>
        </div>
        
        <div id="messageBox" class="message"></div>
        <div id="errorBox" class="error"></div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr><th>ID</th><th>Логин</th><th>Имя</th><th>Email</th><th>Телефон</th><th>Тур</th><th>Сообщение</th><th>Дата</th><th>Действия</th></tr>
                </thead>
                <tbody id="tableBody">
                    <?php foreach ($applications as $app): ?>
                        <tr id="row_<?= $app['id'] ?>">
                            <td><?= $app['id'] ?></td>
                            <td><?= htmlspecialchars($app['login']) ?></td>
                            <td><?= htmlspecialchars($app['name']) ?></td>
                            <td><?= htmlspecialchars($app['email']) ?></td>
                            <td><?= htmlspecialchars($app['phone']) ?></td>
                            <td><?= htmlspecialchars($app['tour']) ?></td>
                            <td><?= htmlspecialchars(substr($app['message'] ?? '', 0, 50)) ?></td>
                            <td><?= date('d.m.Y', strtotime($app['created_at'])) ?></td>
                            <td>
                                <a href="admin_edit.php?id=<?= $app['id'] ?>" class="btn-edit">✏️ Ред.</a>
                                <button class="btn-delete" data-id="<?= $app['id'] ?>">🗑️ Удалить</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <a href="index.html" class="back-link">← На главную</a>
    </div>
    
    <script>
        function showMessage(text, isError) {
            const msgBox = document.getElementById('messageBox');
            const errBox = document.getElementById('errorBox');
            if (isError) {
                errBox.innerHTML = text;
                errBox.style.display = 'block';
                setTimeout(() => errBox.style.display = 'none', 3000);
            } else {
                msgBox.innerHTML = text;
                msgBox.style.display = 'block';
                setTimeout(() => msgBox.style.display = 'none', 3000);
            }
        }
        
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', async function() {
                const id = this.dataset.id;
                if (!confirm('Удалить заявку #' + id + '?')) return;
                
                this.disabled = true;
                this.textContent = '⌛...';
                document.getElementById('row_' + id).style.opacity = '0.5';
                
                const response = await fetch(window.location.pathname, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('row_' + id).remove();
                    showMessage('✅ Заявка удалена', false);
                } else {
                    showMessage('❌ Ошибка', true);
                    this.disabled = false;
                    this.textContent = '🗑️ Удалить';
                    document.getElementById('row_' + id).style.opacity = '1';
                }
            });
        });
    </script>
</body>
</html>