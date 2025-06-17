<?php
session_start();
if (!isset($_SESSION["empresa_id"])) {
    header("Location: login.php");
    exit;
}

require_once 'config/database.php';

// Updated query to use candidatos table instead of usuarios
$stmt = $pdo->prepare("
    SELECT c.*, v.titulo as vaga_titulo, cd.nome as candidato_nome 
    FROM candidaturas c 
    JOIN vagas v ON c.vaga_id = v.id 
    JOIN candidatos cd ON c.candidato_id = cd.id 
    WHERE v.empresa_id = ? 
    ORDER BY c.data_candidatura DESC
");
$stmt->execute([$_SESSION['empresa_id']]);
$candidaturas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Candidatos - Dashboard RH</title>
    <link rel="stylesheet" href="../all.css/registro3.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .candidatos-lista {
            background: white;
            border-radius: 8px;
            margin: 20px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .candidato-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .candidato-item:last-child {
            border-bottom: none;
        }
        .status-pendente {
            color: orange;
        }
        .status-aprovado {
            color: green;
        }
        .status-rejeitado {
            color: red;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="logo">
                <a href="painel_empresa.php">
                    <img src="../img/sam2logo-32.png" alt="SAM Logo">
                </a>
            </div>
            <select class="nav-select">
                <option>empresa</option>
            </select>
            <ul class="nav-menu">
                <a href="painel_empresa.php"><li>Painel Principal</li></a>
                <a href="configuracoes_empresa.php"><li>Configurações</li></a>
                <a href="vagas_empresa.php"><li>Minhas Vagas</li></a>
                <a href="candidatos.php"><li class="active">Candidatos</li></a>
                <a href="logout.php"><li>Sair</li></a>
            </ul>
        </div>

        <div class="main-content">
            <h1>Candidaturas Recebidas</h1>
            
            <div class="candidatos-lista">
                <?php foreach ($candidaturas as $candidatura): ?>
                    <div class="candidato-item">
                        <h3><?php echo htmlspecialchars($candidatura['candidato_nome']); ?></h3>
                        <p>Vaga: <?php echo htmlspecialchars($candidatura['vaga_titulo']); ?></p>
                        <p>Data: <?php echo date('d/m/Y', strtotime($candidatura['data_candidatura'])); ?></p>
                        <p>Status: <span class="status-<?php echo $candidatura['status']; ?>">
                            <?php echo ucfirst($candidatura['status']); ?>
                        </span></p>
                        <a href="ver_candidato.php?id=<?php echo $candidatura['id']; ?>">Ver Detalhes</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <script src="../js/theme.js"></script>
</body>
</html>
