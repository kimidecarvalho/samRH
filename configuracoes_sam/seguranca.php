<?php
session_start();
include('../conexao.php');

// Verifica se o usuário está logado
if (!isset($_SESSION['id_adm'])) {
    header('Location: ../login.php');
    exit();
}

// Atualiza o last_activity da sessão atual
$session_id = session_id();
$sql_update = "UPDATE adm_sessions SET last_activity = NOW() WHERE session_id = ? AND adm_id = ?";
$stmt_update = $mysqli->prepare($sql_update);
$stmt_update->bind_param("si", $session_id, $_SESSION['id_adm']);
$stmt_update->execute();

// Limpa sessões antigas (mais de 24 horas)
$sql_cleanup = "DELETE FROM adm_sessions WHERE adm_id = ? AND last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$stmt_cleanup = $mysqli->prepare($sql_cleanup);
$stmt_cleanup->bind_param("i", $_SESSION['id_adm']);
$stmt_cleanup->execute();

// Processa a alteração da configuração de dois fatores
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dois_fatores'])) {
    $dois_fatores = $_POST['dois_fatores'] === 'true' ? 1 : 0;
    $adm_id = $_SESSION['id_adm'];
    
    // Verifica se já existe uma configuração para este usuário
    $check_sql = "SELECT id FROM configuracoes_seguranca WHERE adm_id = ?";
    $check_stmt = $mysqli->prepare($check_sql);
    $check_stmt->bind_param("i", $adm_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Atualiza a configuração existente
        $sql = "UPDATE configuracoes_seguranca SET dois_fatores = ? WHERE adm_id = ?";
    } else {
        // Cria uma nova configuração
        $sql = "INSERT INTO configuracoes_seguranca (dois_fatores, adm_id) VALUES (?, ?)";
    }
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $dois_fatores, $adm_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar configuração']);
    }
    exit();
}

// Processa o encerramento de sessão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['encerrar_sessao'])) {
    $session_id = $_POST['session_id'];
    $adm_id = $_SESSION['id_adm'];
    
    $sql = "DELETE FROM adm_sessions WHERE session_id = ? AND adm_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("si", $session_id, $adm_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao encerrar sessão']);
    }
    exit();
}

// Processa a redefinição de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redefinir_senha'])) {
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $adm_id = $_SESSION['id_adm'];
    
    // Verifica se a senha atual está correta
    $sql_verificar = "SELECT senha FROM adm WHERE id_adm = ?";
    $stmt_verificar = $mysqli->prepare($sql_verificar);
    $stmt_verificar->bind_param("i", $adm_id);
    $stmt_verificar->execute();
    $result = $stmt_verificar->get_result();
    $usuario = $result->fetch_assoc();
    
    if (!$usuario || !password_verify($senha_atual, $usuario['senha'])) {
        echo json_encode(['success' => false, 'error' => 'Senha atual incorreta']);
        exit();
    }
    
    // Verifica se as novas senhas coincidem
    if ($nova_senha !== $confirmar_senha) {
        echo json_encode(['success' => false, 'error' => 'As novas senhas não coincidem']);
        exit();
    }
    
    // Verifica se a nova senha tem pelo menos 8 caracteres
    if (strlen($nova_senha) < 8) {
        echo json_encode(['success' => false, 'error' => 'A nova senha deve ter pelo menos 8 caracteres']);
        exit();
    }
    
    // Atualiza a senha
    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    $sql_atualizar = "UPDATE adm SET senha = ? WHERE id_adm = ?";
    $stmt_atualizar = $mysqli->prepare($sql_atualizar);
    $stmt_atualizar->bind_param("si", $senha_hash, $adm_id);
    
    if ($stmt_atualizar->execute()) {
        // Registra a alteração de senha no log
        $ip = getRealIP();
        $sql_log = "INSERT INTO log_atividades (adm_id, acao, ip_address, data_hora) VALUES (?, 'Alteração de Senha', ?, NOW())";
        $stmt_log = $mysqli->prepare($sql_log);
        $stmt_log->bind_param("is", $adm_id, $ip);
        $stmt_log->execute();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar senha: ' . $mysqli->error]);
    }
    exit();
}

// Registra login no log de atividades
if (isset($_SESSION['id_adm']) && !isset($_SESSION['log_registrado'])) {
    $adm_id = $_SESSION['id_adm'];
    $ip = getRealIP();
    $sql_log = "INSERT INTO log_atividades (adm_id, acao, ip_address, data_hora) VALUES (?, 'Login Efetuado', ?, NOW())";
    $stmt_log = $mysqli->prepare($sql_log);
    $stmt_log->bind_param("is", $adm_id, $ip);
    $stmt_log->execute();
    $_SESSION['log_registrado'] = true;
}

// Busca o log de atividades
$sql_log = "SELECT acao, ip_address, data_hora 
            FROM log_atividades 
            WHERE adm_id = ? 
            ORDER BY data_hora DESC 
            LIMIT 10";
$stmt_log = $mysqli->prepare($sql_log);
$stmt_log->bind_param("i", $_SESSION['id_adm']);
$stmt_log->execute();
$result_log = $stmt_log->get_result();
$logs = $result_log->fetch_all(MYSQLI_ASSOC);

// Busca a configuração atual
$adm_id = $_SESSION['id_adm'];
$sql = "SELECT dois_fatores FROM configuracoes_seguranca WHERE adm_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $adm_id);
$stmt->execute();
$result = $stmt->get_result();
$config = $result->fetch_assoc();

$dois_fatores_ativado = false;

// Busca as sessões ativas
$sql_sessoes = "SELECT session_id, user_agent, ip_address, last_activity 
                FROM adm_sessions 
                WHERE adm_id = ? 
                AND last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                ORDER BY last_activity DESC";
$stmt_sessoes = $mysqli->prepare($sql_sessoes);
$stmt_sessoes->bind_param("i", $adm_id);
$stmt_sessoes->execute();
$result_sessoes = $stmt_sessoes->get_result();
$sessoes = $result_sessoes->fetch_all(MYSQLI_ASSOC);

// Função para identificar o navegador
function getBrowserInfo($user_agent) {
    $browser = "Navegador Desconhecido";
    $os = "Sistema Operacional Desconhecido";
    
    // Identifica o navegador
    if (strpos($user_agent, 'Chrome')) {
        $browser = "Google Chrome";
    } elseif (strpos($user_agent, 'Firefox')) {
        $browser = "Mozilla Firefox";
    } elseif (strpos($user_agent, 'Safari')) {
        $browser = "Safari";
    } elseif (strpos($user_agent, 'Edge')) {
        $browser = "Microsoft Edge";
    } elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) {
        $browser = "Internet Explorer";
    }
    
    // Identifica o sistema operacional
    if (strpos($user_agent, 'Windows')) {
        $os = "Windows";
    } elseif (strpos($user_agent, 'Mac')) {
        $os = "MacOS";
    } elseif (strpos($user_agent, 'Linux')) {
        $os = "Linux";
    } elseif (strpos($user_agent, 'Android')) {
        $os = "Android";
    } elseif (strpos($user_agent, 'iOS')) {
        $os = "iOS";
    }
    
    return [
        'browser' => $browser,
        'os' => $os
    ];
}

// Função para formatar a data
function formatLastActivity($timestamp) {
    $now = new DateTime();
    $last = new DateTime($timestamp);
    $diff = $now->diff($last);
    
    if ($diff->i < 1) {
        return "Online agora";
    } elseif ($diff->h < 1) {
        return $diff->i . " minutos atrás";
    } elseif ($diff->d < 1) {
        return $diff->h . " horas atrás";
    } else {
        return $diff->d . " dias atrás";
    }
}

// Função para obter o IP real
function getRealIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// Atualiza o last_activity da sessão atual
$session_id = session_id();
$current_ip = getRealIP();
$user_agent = $_SERVER['HTTP_USER_AGENT'];

$sql_update = "INSERT INTO adm_sessions (session_id, adm_id, user_agent, ip_address, last_activity) 
               VALUES (?, ?, ?, ?, NOW())
               ON DUPLICATE KEY UPDATE 
               last_activity = NOW(),
               session_id = VALUES(session_id)";
$stmt_update = $mysqli->prepare($sql_update);
$stmt_update->bind_param("siss", $session_id, $_SESSION['id_adm'], $user_agent, $current_ip);
$stmt_update->execute();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Segurança - Dashboard RH</title>
    <link rel="stylesheet" href="../all.css/registro3.css">
    <link rel="stylesheet" href="../all.css/configuracoes.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3EB489;
            --background-light: #f4f4f4;
            --text-color: #333;
            --white: #ffffff;
            --border-color: #e0e0e0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-light);
            color: var(--text-color);
        }

        .profile-card {
            background-color: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .security-section {
            background-color: var(--background-light);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .security-section h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .detail-item label {
            font-weight: 500;
            color: var(--text-color);
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 54px;
            height: 28px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        .toggle-switch input:checked + .slider {
            background-color: var(--primary-color);
        }

        .toggle-switch input:checked + .slider:before {
            transform: translateX(26px);
        }

        .active-sessions {
            background-color: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .session-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.3s ease;
        }

        .session-item:hover {
            background-color: rgba(62, 180, 137, 0.05);
        }

        .session-item:last-child {
            border-bottom: none;
        }

        .session-item strong {
            color: var(--text-color);
            display: block;
            margin-bottom: 5px;
        }

        .session-item p {
            color: #6c757d;
            font-size: 0.9em;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #32a177;
        }

        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background-color: var(--white);
            transition: border-color 0.3s ease;
        }

        input[type="password"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(62, 180, 137, 0.2);
        }

        body.dark {
    background-color: #121212;
    color: #e0e0e0;
}

body.dark .dashboard-container {
    background-color: #1e1e1e;
}

body.dark .main-content {
    background-color: #2a2a2a;
}

body.dark .profile-card {
    background-color: #262626;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
}

body.dark .profile-card h1 {
    color: var(--primary-color);
}

body.dark .security-section {
    background-color: #1a1a1a;
}

body.dark .security-section h3 {
    color: var(--primary-color);
    border-bottom: 2px solid var(--primary-color);
}

body.dark .detail-item label {
    color: #c0c0c0;
}

body.dark .active-sessions {
    background-color: #1f1f1f;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

body.dark .session-item {
    border-bottom: 1px solid #333;
}

body.dark .session-item strong {
    color: #e0e0e0;
}

body.dark .session-item p {
    color: #888;
}

body.dark .toggle-switch .slider {
    background-color: #555;
}

body.dark .toggle-switch .slider:before {
    background-color: #aaa;
}

body.dark .toggle-switch input:checked + .slider {
    background-color: var(--primary-color);
}

body.dark input[type="password"] {
    background-color: #333;
    border-color: #444;
    color: #e0e0e0;
}

body.dark input[type="password"]:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(62, 180, 137, 0.3);
}

body.dark .btn-primary {
    background-color: var(--primary-color);
    color: #f4f4f4;
    transition: background-color 0.3s ease;
}

body.dark .btn-primary:hover {
    background-color: #3EB489;
}

body.dark .sidebar {
    background-color: #1a1a1a;
    box-shadow: 2px 0 5px rgba(0,0,0,0.3);
}

body.dark .sidebar .logo img {
    filter: brightness(0.8) contrast(1.2);
}

body.dark .sidebar .nav-select {
    background-color: #262626;
    color: #e0e0e0;
    border-color: #444;
}

body.dark .nav-menu li {
    color: #b0b0b0;
    transition: all 0.3s ease;
}

body.dark .nav-menu li:hover {
    background-color: rgba(62, 180, 137, 0.2);
    color: var(--primary-color);
}

body.dark .nav-menu li.active {
    background-color: rgba(62, 180, 137, 0.2);
    color: var(--primary-color);
}

.current-session {
    color: var(--primary-color);
    font-weight: 500;
    padding: 8px 15px;
    border: 1px solid var(--primary-color);
    border-radius: 6px;
}

.btn-primary.encerrar-sessao {
    background-color: #dc3545;
}

.btn-primary.encerrar-sessao:hover {
    background-color: #c82333;
}

.status {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.85em;
    background-color: #f0f0f0;
}

.status.online {
    background-color: #32a177;
    color: white;
}
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="sidebar">
        <div class="logo">
            <a href="../UI.php">
                <img src="../img/sam2logo-32.png" alt="SAM Logo">
            </a>
        </div>
        <select class="nav-select">
            <option>sam</option>
        </select>
        <ul class="nav-menu">           
            <a href="conf.sistema.php"><li>Configurações do Sistema</li></a>
            <a href="perfil_adm.php"><li>Perfil do Usuário</li></a>
            <a href="seguranca.php"><li class="active">Segurança</li></a>
            <a href="privacidade.php"><li>Privacidade</li></a>
            <a href="rh_config.php"><li>Configurações de RH</li></a>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="profile-card">
            <h1>Configurações de Segurança</h1>

            <div class="security-section">
                <h3>Configurações de Segurança</h3>
                <div class="detail-item">
                    <label>Autenticação de Dois Fatores</label>
                    <label class="toggle-switch">
                        <input type="checkbox" id="dois_fatores" <?php echo $dois_fatores_ativado ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="detail-item">
                    <label>Login com Biometria</label>
                    <label class="toggle-switch">
                        <input type="checkbox">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <div class="security-section">
                <h3>Sessões Ativas</h3>
                <div class="active-sessions">
                    <?php if (empty($sessoes)): ?>
                    <div class="session-item">
                        <div>
                                <strong>Nenhuma sessão ativa no momento</strong>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($sessoes as $sessao): 
                            $browser_info = getBrowserInfo($sessao['user_agent']);
                            $is_current_session = $sessao['session_id'] === session_id();
                        ?>
                    <div class="session-item">
                        <div>
                                <strong><?php echo htmlspecialchars($browser_info['browser']); ?></strong>
                                <p>
                                    <?php echo htmlspecialchars($browser_info['os']); ?> | 
                                    IP: <?php echo htmlspecialchars($sessao['ip_address']); ?> | 
                                    <span class="status <?php echo $is_current_session ? 'online' : ''; ?>">
                                        <?php echo formatLastActivity($sessao['last_activity']); ?>
                                    </span>
                                </p>
                            </div>
                            <?php if (!$is_current_session): ?>
                            <button class="btn-primary encerrar-sessao" data-session-id="<?php echo htmlspecialchars($sessao['session_id']); ?>">Encerrar Sessão</button>
                            <?php else: ?>
                            <span class="current-session">Sessão Atual</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="security-section">
                <h3>Log de Atividades</h3>
                <div class="active-sessions">
                    <?php if (empty($logs)): ?>
                    <div class="session-item">
                        <div>
                                <strong>Nenhuma atividade registrada</strong>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                    <div class="session-item">
                        <div>
                                <strong><?php echo htmlspecialchars($log['acao']); ?></strong>
                                <p>
                                    <?php echo date('d/m/Y H:i', strtotime($log['data_hora'])); ?> | 
                                    IP: <?php echo htmlspecialchars($log['ip_address']); ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="security-section">
                <h3>Redefinição de Senha</h3>
                <div class="detail-item">
                    <label>Senha Atual</label>
                    <input type="password" id="senha_atual" placeholder="Digite sua senha atual">
                </div>
                <div class="detail-item">
                    <label>Nova Senha</label>
                    <input type="password" id="nova_senha" placeholder="Digite sua nova senha">
                </div>
                <div class="detail-item">
                    <label>Confirmar Nova Senha</label>
                    <input type="password" id="confirmar_senha" placeholder="Confirme sua nova senha">
                </div>
                <button class="btn-primary" id="btn_redefinir_senha" style="margin-top: 10px;">Redefinir Senha</button>
            </div>
        </div>
    </div>
</div>
<script src="../js/theme.js"></script>
<script>
document.getElementById('dois_fatores').addEventListener('change', function() {
    const isChecked = this.checked;
    
    fetch('seguranca.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'dois_fatores=' + isChecked
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostra mensagem de sucesso
            alert(isChecked ? 'Autenticação de dois fatores ativada!' : 'Autenticação de dois fatores desativada!');
        } else {
            // Mostra mensagem de erro
            alert('Erro ao atualizar configuração. Tente novamente.');
            // Reverte o estado do checkbox
            this.checked = !isChecked;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao atualizar configuração. Tente novamente.');
        // Reverte o estado do checkbox
        this.checked = !isChecked;
    });
});

// Adiciona o código para encerrar sessões
document.querySelectorAll('.encerrar-sessao').forEach(button => {
    button.addEventListener('click', function() {
        const sessionId = this.dataset.sessionId;
        
        if (confirm('Tem certeza que deseja encerrar esta sessão?')) {
            fetch('seguranca.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'encerrar_sessao=1&session_id=' + sessionId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove o elemento da sessão da interface
                    this.closest('.session-item').remove();
                    alert('Sessão encerrada com sucesso!');
                } else {
                    alert('Erro ao encerrar sessão. Tente novamente.');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao encerrar sessão. Tente novamente.');
            });
        }
    });
});

// Adiciona atualização automática das sessões a cada 30 segundos
setInterval(function() {
    fetch('seguranca.php')
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newSessions = doc.querySelector('.active-sessions').innerHTML;
            document.querySelector('.active-sessions').innerHTML = newSessions;
        });
}, 30000);

// Adiciona o código para redefinição de senha
document.getElementById('btn_redefinir_senha').addEventListener('click', function() {
    const senhaAtual = document.getElementById('senha_atual').value;
    const novaSenha = document.getElementById('nova_senha').value;
    const confirmarSenha = document.getElementById('confirmar_senha').value;
    
    if (!senhaAtual || !novaSenha || !confirmarSenha) {
        alert('Por favor, preencha todos os campos');
        return;
    }
    
    const formData = new FormData();
    formData.append('redefinir_senha', '1');
    formData.append('senha_atual', senhaAtual);
    formData.append('nova_senha', novaSenha);
    formData.append('confirmar_senha', confirmarSenha);
    
    fetch('seguranca.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Senha atualizada com sucesso!');
            // Limpa os campos
            document.getElementById('senha_atual').value = '';
            document.getElementById('nova_senha').value = '';
            document.getElementById('confirmar_senha').value = '';
        } else {
            alert(data.error || 'Erro ao atualizar senha. Tente novamente.');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao atualizar senha. Tente novamente.');
    });
});
</script>
</body>
</html> 