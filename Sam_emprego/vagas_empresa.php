<?php
session_start();
if (!isset($_SESSION["empresa_id"])) {
    header("Location: login.php");
    exit;
}

require_once 'config/database.php';

// Buscar vagas da empresa
$stmt = $pdo->prepare("SELECT * FROM vagas WHERE empresa_id = ? ORDER BY data_publicacao DESC");
$stmt->execute([$_SESSION['empresa_id']]);
$vagas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Minhas Vagas - Dashboard RH</title>
    <link rel="stylesheet" href="../all.css/registro3.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .vagas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .vaga-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-nova-vaga {
            background: #3EB489;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin: 20px;
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
                <a href="vagas_empresa.php"><li class="active">Minhas Vagas</li></a>
                <a href="candidatos.php"><li>Candidatos</li></a>
                <a href="logout.php"><li>Sair</li></a>
            </ul>
        </div>

        <div class="main-content">
            <a href="nova_vaga.php" class="btn-nova-vaga">+ Nova Vaga</a>
            
            <div class="vagas-grid">
                <?php foreach ($vagas as $vaga): ?>
                    <div class="vaga-card">
                        <h3><?php echo htmlspecialchars($vaga['titulo']); ?></h3>
                        <p><?php echo htmlspecialchars($vaga['descricao']); ?></p>
                        <p>Salário: R$ <?php echo number_format($vaga['salario'], 2, ',', '.'); ?></p>
                        <a href="editar_vaga.php?id=<?php echo $vaga['id']; ?>">Editar</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <script src="../js/theme.js"></script>
</body>
</html>
