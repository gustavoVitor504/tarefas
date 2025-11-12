<?php
// ====================================
// CRUD COMPLETO COM DATAS - PHP + JavaScript
// ====================================

// Configuração do banco de dados
$host = 'localhost';
$dbname = 'todo_app';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Criar tabela com campo de data limite
    $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task TEXT NOT NULL,
        due_date DATETIME NULL,
        completed BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // CREATE - Adicionar tarefa com data
    if (isset($_POST['add_task'])) {
        $task = $_POST['task'];
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $stmt = $pdo->prepare("INSERT INTO tasks (task, due_date) VALUES (?, ?)");
        $stmt->execute([$task, $due_date]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // UPDATE - Atualizar texto e data da tarefa
    if (isset($_POST['update_task'])) {
        $id = $_POST['task_id'];
        $task = $_POST['task'];
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $stmt = $pdo->prepare("UPDATE tasks SET task = ?, due_date = ? WHERE id = ?");
        $stmt->execute([$task, $due_date, $id]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // UPDATE - Marcar como concluída/pendente
    if (isset($_GET['complete'])) {
        $id = $_GET['complete'];
        $stmt = $pdo->prepare("UPDATE tasks SET completed = NOT completed WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // DELETE - Deletar tarefa
    if (isset($_GET['delete'])) {
        $id = $_GET['delete'];
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // READ - Buscar todas as tarefas
    $stmt = $pdo->query("SELECT * FROM tasks ORDER BY completed ASC, created_at DESC");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar tarefa específica para edição
    $editTask = null;
    if (isset($_GET['edit'])) {
        $id = $_GET['edit'];
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$id]);
        $editTask = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Estatísticas
    $total = count($tasks);
    $completed = count(array_filter($tasks, fn($t) => $t['completed']));
    $pending = $total - $completed;
    
    // Contar tarefas atrasadas
    $now = date('Y-m-d H:i:s');
    $overdue = count(array_filter($tasks, function($t) use ($now) {
        return !$t['completed'] && $t['due_date'] && $t['due_date'] < $now;
    }));

} catch(PDOException $e) {
    $error_message = $e->getMessage();
    $tasks = [];
    $total = $completed = $pending = $overdue = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Tarefas com Datas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .form-group input[type="text"] {
            flex: 2;
            min-width: 200px;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-group input[type="datetime-local"] {
            flex: 1;
            min-width: 180px;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .edit-mode {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .edit-mode h3 {
            color: #856404;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-warning {
            background: #ffc107;
            color: #000;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
        }

        .filter-btn.active {
            background: #667eea;
            color: white;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
        }

        .sort-controls {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .sort-controls label {
            font-weight: bold;
            color: #667eea;
        }

        .sort-controls select {
            padding: 8px 15px;
            border: 2px solid #667eea;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
        }

        .task-list {
            list-style: none;
        }

        .task-item {
            background: #f8f9fa;
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s;
            border-left: 4px solid #667eea;
            flex-wrap: wrap;
            gap: 10px;
        }

        .task-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .task-item.completed {
            opacity: 0.6;
            border-left-color: #28a745;
        }

        .task-item.overdue {
            border-left-color: #dc3545;
            background: #fff5f5;
        }

        .task-item.due-soon {
            border-left-color: #ffc107;
            background: #fffef5;
        }

        .task-item.completed .task-text {
            text-decoration: line-through;
            color: #6c757d;
        }

        .task-item.editing {
            border-left-color: #ffc107;
            background: #fff9e6;
        }

        .task-content {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
            min-width: 200px;
        }

        .task-text {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }

        .task-date-info {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .date-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }

        .date-badge.normal {
            background: #e3f2fd;
            color: #1976d2;
        }

        .date-badge.warning {
            background: #fff3cd;
            color: #856404;
        }

        .date-badge.danger {
            background: #f8d7da;
            color: #721c24;
        }

        .task-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 8px 15px;
            font-size: 14px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: white;
            display: inline-block;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-info {
            background: #17a2b8;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-small:hover {
            opacity: 0.8;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .stats {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            flex-wrap: wrap;
            gap: 10px;
        }

        .stat-item {
            text-align: center;
            min-width: 80px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #6c757d;
            font-size: 14px;
        }

        .error-box {
            color: red;
            padding: 15px;
            background: #ffebee;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }

        @media (max-width: 768px) {
            .task-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .task-actions {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 Minhas Tarefas</h1>
            <p>Sistema completo com datas e filtros inteligentes</p>
        </div>

        <div class="card">
            <?php if (isset($error_message)): ?>
                <div class="error-box">
                    <strong>Erro de conexão:</strong> <?php echo htmlspecialchars($error_message); ?>
                    <br><br><strong>Instruções:</strong>
                    <br>1. Crie um banco de dados chamado 'todo_app' no MySQL
                    <br>2. Verifique se o usuário e senha estão corretos no código
                </div>
            <?php endif; ?>

            <!-- Modo de Edição -->
            <?php if ($editTask): ?>
                <div class="edit-mode">
                    <h3>✏️ Modo de Edição</h3>
                    <form method="POST" action="">
                        <input type="hidden" name="task_id" value="<?php echo $editTask['id']; ?>">
                        <div class="form-group">
                            <input type="text" name="task" value="<?php echo htmlspecialchars($editTask['task']); ?>" required autofocus>
                            <input type="datetime-local" name="due_date" value="<?php echo $editTask['due_date'] ? date('Y-m-d\TH:i', strtotime($editTask['due_date'])) : ''; ?>">
                            <button type="submit" name="update_task" class="btn btn-warning">💾 Salvar</button>
                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">✖️ Cancelar</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Formulário de Adicionar -->
                <form method="POST" action="">
                    <div class="form-group">
                        <input type="text" name="task" placeholder="Digite uma nova tarefa..." required>
                        <input type="datetime-local" name="due_date" title="Data limite (opcional)">
                        <button type="submit" name="add_task" class="btn btn-primary">➕ Adicionar</button>
                    </div>
                </form>
            <?php endif; ?>

            <!-- Filtros -->
            <div class="filter-buttons">
                <button class="filter-btn active" onclick="filtrarTarefas('todas', this)">📋 Todas</button>
                <button class="filter-btn" onclick="filtrarTarefas('pendentes', this)">⏳ Pendentes</button>
                <button class="filter-btn" onclick="filtrarTarefas('concluidas', this)">✅ Concluídas</button>
                <button class="filter-btn" onclick="filtrarTarefas('atrasadas', this)">🔴 Atrasadas</button>
                <button class="filter-btn" onclick="filtrarTarefas('proximas', this)">⚠️ Próximas do Prazo</button>
            </div>

            <!-- Ordenação -->
            <div class="sort-controls">
                <label>🔀 Ordenar por:</label>
                <select id="sortSelect" onchange="ordenarTarefas()">
                    <option value="padrao">Padrão</option>
                    <option value="data-limite">Data Limite</option>
                    <option value="alfabetica">Ordem Alfabética</option>
                    <option value="mais-recente">Mais Recentes</option>
                </select>
            </div>

            <!-- Lista de Tarefas -->
            <ul class="task-list" id="taskList">
                <?php if (empty($tasks)): ?>
                    <div class="empty-state">
                        <h3>Nenhuma tarefa ainda</h3>
                        <p>Adicione sua primeira tarefa acima!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($tasks as $task): 
                        $isOverdue = !$task['completed'] && $task['due_date'] && strtotime($task['due_date']) < time();
                        $isDueSoon = false;
                        if (!$task['completed'] && $task['due_date']) {
                            $hours = (strtotime($task['due_date']) - time()) / 3600;
                            $isDueSoon = $hours > 0 && $hours <= 24;
                        }
                        
                        $itemClass = 'task-item';
                        if ($task['completed']) $itemClass .= ' completed';
                        elseif ($isOverdue) $itemClass .= ' overdue';
                        elseif ($isDueSoon) $itemClass .= ' due-soon';
                        if ($editTask && $editTask['id'] == $task['id']) $itemClass .= ' editing';
                    ?>
                        <li class="<?php echo $itemClass; ?>" 
                            data-status="<?php echo $task['completed'] ? 'concluida' : 'pendente'; ?>"
                            data-overdue="<?php echo $isOverdue ? 'sim' : 'nao'; ?>"
                            data-due-soon="<?php echo $isDueSoon ? 'sim' : 'nao'; ?>"
                            data-created="<?php echo strtotime($task['created_at']); ?>"
                            data-due="<?php echo $task['due_date'] ? strtotime($task['due_date']) : '9999999999'; ?>"
                            data-text="<?php echo htmlspecialchars($task['task']); ?>">
                            <div class="task-content">
                                <span class="task-text"><?php echo htmlspecialchars($task['task']); ?></span>
                                <div class="task-date-info">
                                    <?php if ($task['completed']): ?>
                                        <span class="date-badge normal">✅ Concluída</span>
                                    <?php elseif ($isOverdue): ?>
                                        <?php 
                                            $days = floor((time() - strtotime($task['due_date'])) / 86400);
                                        ?>
                                        <span class="date-badge danger">🔴 Atrasada há <?php echo $days; ?> dia<?php echo $days != 1 ? 's' : ''; ?></span>
                                    <?php elseif ($isDueSoon): ?>
                                        <?php 
                                            $hours = floor((strtotime($task['due_date']) - time()) / 3600);
                                        ?>
                                        <span class="date-badge warning">⚠️ Vence em <?php echo $hours; ?>h</span>
                                    <?php elseif ($task['due_date']): ?>
                                        <?php 
                                            $days = ceil((strtotime($task['due_date']) - time()) / 86400);
                                        ?>
                                        <span class="date-badge normal">📅 <?php echo $days; ?> dia<?php echo $days != 1 ? 's' : ''; ?> restante<?php echo $days != 1 ? 's' : ''; ?></span>
                                    <?php else: ?>
                                        <span class="date-badge normal">📅 Sem prazo definido</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($task['due_date']): ?>
                                        <span style="font-size: 12px; color: #6c757d;">
                                            📅 <?php echo date('d/m/Y H:i', strtotime($task['due_date'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="task-actions">
                                <a href="?complete=<?php echo $task['id']; ?>" class="btn-small btn-success">
                                    <?php echo $task['completed'] ? '↩️ Desfazer' : '✓ Concluir'; ?>
                                </a>
                                <a href="?edit=<?php echo $task['id']; ?>" class="btn-small btn-info">
                                    ✏️ Editar
                                </a>
                                <a href="?delete=<?php echo $task['id']; ?>" 
                                   onclick="return confirm('Tem certeza que deseja excluir esta tarefa?')" 
                                   class="btn-small btn-danger">
                                    🗑️ Excluir
                                </a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>

            <!-- Estatísticas -->
            <?php if ($total > 0): ?>
                <div class="stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $total; ?></div>
                        <div class="stat-label">Total</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $pending; ?></div>
                        <div class="stat-label">Pendentes</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $completed; ?></div>
                        <div class="stat-label">Concluídas</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $overdue; ?></div>
                        <div class="stat-label">Atrasadas</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // ==========================================
        // FUNÇÕES JAVASCRIPT PARA FILTROS E ORDENAÇÃO
        // ==========================================

        let filtroAtual = 'todas';

        // Filtrar tarefas
        function filtrarTarefas(tipo, botao) {
            filtroAtual = tipo;
            
            // Atualizar botões ativos
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            botao.classList.add('active');
            
            // Aplicar filtro
            const tarefas = document.querySelectorAll('.task-item');
            let visibleCount = 0;
            
            tarefas.forEach(tarefa => {
                let mostrar = false;
                
                switch(tipo) {
                    case 'todas':
                        mostrar = true;
                        break;
                    case 'pendentes':
                        mostrar = tarefa.dataset.status === 'pendente';
                        break;
                    case 'concluidas':
                        mostrar = tarefa.dataset.status === 'concluida';
                        break;
                    case 'atrasadas':
                        mostrar = tarefa.dataset.overdue === 'sim';
                        break;
                    case 'proximas':
                        mostrar = tarefa.dataset.dueSoon === 'sim';
                        break;
                }
                
                if (mostrar) {
                    tarefa.style.display = '';
                    visibleCount++;
                } else {
                    tarefa.style.display = 'none';
                }
            });
            
            // Mostrar mensagem se não houver tarefas
            const taskList = document.getElementById('taskList');
            const emptyState = taskList.querySelector('.empty-state');
            
            if (visibleCount === 0 && !emptyState) {
                const div = document.createElement('div');
                div.className = 'empty-state';
                div.innerHTML = '<h3>Nenhuma tarefa encontrada</h3><p>Tente outro filtro!</p>';
                taskList.appendChild(div);
            } else if (visibleCount > 0 && emptyState) {
                emptyState.remove();
            }
        }

        // Ordenar tarefas
        function ordenarTarefas() {
            const tipo = document.getElementById('sortSelect').value;
            const taskList = document.getElementById('taskList');
            const tarefas = Array.from(taskList.querySelectorAll('.task-item'));
            
            tarefas.sort((a, b) => {
                switch(tipo) {
                    case 'data-limite':
                        return parseInt(a.dataset.due) - parseInt(b.dataset.due);
                    case 'alfabetica':
                        return a.dataset.text.localeCompare(b.dataset.text);
                    case 'mais-recente':
                        return parseInt(b.dataset.created) - parseInt(a.dataset.created);
                    default:
                        // Padrão: pendentes primeiro, depois por data de criação
                        if (a.dataset.status !== b.dataset.status) {
                            return a.dataset.status === 'pendente' ? -1 : 1;
                        }
                        return parseInt(b.dataset.created) - parseInt(a.dataset.created);
                }
            });
            
            // Reordenar elementos
            tarefas.forEach(tarefa => taskList.appendChild(tarefa));
        }

        // Função auxiliar para formatar data (caso precise usar no JS)
        function formatarData(dataString) {
            if (!dataString) return '';
            const data = new Date(dataString);
            return data.toLocaleString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Função para calcular tempo restante
        function calcularTempoRestante(dataLimite) {
            const agora = new Date();
            const limite = new Date(dataLimite);
            const diff = limite - agora;
            
            if (diff < 0) {
                const dias = Math.floor(Math.abs(diff) / (1000 * 60 * 60 * 24));
                return `Atrasada há ${dias} dia${dias !== 1 ? 's' : ''}`;
            }
            
            const horas = Math.floor(diff / (1000 * 60 * 60));
            if (horas < 24) {
                return `Vence em ${horas}h`;
            }
            
            const dias = Math.ceil(diff / (1000 * 60 * 60 * 24));
            return `${dias} dia${dias !== 1 ? 's' : ''} restante${dias !== 1 ? 's' : ''}`;
        }
    </script>
</body>
</html>