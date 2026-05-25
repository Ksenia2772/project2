<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// Обработка AJAX-запроса на удаление
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Не указан ID']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM travel_applications WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Заявка не найдена']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Ошибка базы данных']);
    }
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
        .message {
            background: #d4edda; color: #155724; padding: 15px 20px; border-radius: 10px;
            margin-bottom: 20px; border-left: 4px solid #28a745; display: none;
        }
        .error {
            background: #f8d7da; color: #721c24; padding: 15px 20px; border-radius: 10px;
            margin-bottom: 20px; border-left: 4px solid #dc3545; display: none;
        }
        .table-container { background: white; border-radius: 15px; overflow-x: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e1e1e1; }
        th { background: #f8f9fa; color: #333; font-weight: 600; }
        tr:hover { background: #f5f5f5; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 6px 12px; border-radius: 5px; text-decoration: none; font-size: 13px; transition: all 0.2s; cursor: pointer; border: none; }
        .btn-edit { background: #ffc107; color: #333; }
        .btn-edit:hover { background: #e0a800; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-delete:hover { background: #c82333; }
        .btn-delete:disabled { opacity: 0.5; cursor: not-allowed; }
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
        
        <div id="messageBox" class="message"></div>
        <div id="errorBox" class="error"></div>
        
        <div class="stats-container">
            <h2>📊 Статистика</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number" id="totalCount"><?= $total_count ?></div>
                    <div class="stat-label">Всего заявок</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $pdo->query("SELECT COUNT(DISTINCT tour) FROM travel_applications WHERE tour != ''")->fetchColumn() ?></div>
                    <div class="stat-label">Уникальных туров</div>
                </div>
            </div>
        </div>
        
        <div class="table-container">
            <table id="applicationsTable">
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
                <tbody id="tableBody">
                    <?php if (empty($applications)): ?>
                        <tr class="empty-row" id="emptyRow">
                            <td colspan="9">Нет заявок для отображения</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                            <tr id="row_<?= $app['id'] ?>">
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
                                    <button class="btn btn-delete" data-id="<?= $app['id'] ?>">🗑️ Удалить</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <a href="index.html" class="back-link">← Вернуться на главную</a>
    </div>

    <script>
        function showMessage(text, isError = false) {
            const messageBox = document.getElementById('messageBox');
            const errorBox = document.getElementById('errorBox');
            
            if (isError) {
                errorBox.innerHTML = text;
                errorBox.style.display = 'block';
                setTimeout(() => {
                    errorBox.style.display = 'none';
                }, 3000);
            } else {
                messageBox.innerHTML = text;
                messageBox.style.display = 'block';
                setTimeout(() => {
                    messageBox.style.display = 'none';
                }, 3000);
            }
        }
        
        async function deleteApplication(id) {
            if (!confirm('Удалить заявку #' + id + '?')) {
                return;
            }
            
            const button = document.querySelector(`.btn-delete[data-id="${id}"]`);
            const row = document.getElementById(`row_${id}`);
            
            button.disabled = true;
            button.textContent = '⌛ Удаление...';
            row.style.opacity = '0.5';
            
            try {
                const response = await fetch('/project/admin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: id })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    row.remove();
                    showMessage('✅ Заявка #' + id + ' успешно удалена');
                    
                    const totalCountSpan = document.getElementById('totalCount');
                    const currentCount = parseInt(totalCountSpan.textContent);
                    totalCountSpan.textContent = currentCount - 1;
                    
                    const tbody = document.getElementById('tableBody');
                    if (tbody.children.length === 0) {
                        tbody.innerHTML = '<tr class="empty-row"><td colspan="9">Нет заявок для отображения</td></tr>';
                    }
                } else {
                    showMessage('❌ Ошибка: ' + (result.error || 'Не удалось удалить заявку'), true);
                    row.style.opacity = '1';
                    button.disabled = false;
                    button.textContent = '🗑️ Удалить';
                }
            } catch (error) {
                console.error('Ошибка:', error);
                showMessage('❌ Ошибка соединения с сервером', true);
                row.style.opacity = '1';
                button.disabled = false;
                button.textContent = '🗑️ Удалить';
            }
        }
        
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.getAttribute('data-id');
                deleteApplication(id);
            });
        });
    </script>
</body>
</html>