<?php 
include('protect.php');
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="all.css/registro3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAM - Novo Funcionário</title>

</head>
<body>

    <div class="sidebar">
        <div class="logo">
            <a href="home.html">
                <img src="img/sam2logo-32.png" alt="SAM Logo">
            </a>
        </div>
        <select class="nav-select">
            <option>sam</option>
        </select>
        <ul class="nav-menu">
            <a href="home.php"><li class="active">Home</li></a>            
            <a href="funcionarios.php" ><li>Funcionários</li></a>
            <a href="registro.php"><li>Novo Funcionário</li></a>
            <li>Processamento Salarial</li>
            <a href="docs.php"><li>Documentos</li></a>
            <a href="registro_ponto.php"><li>Registro de Ponto</li></a>
            <a href="ausencias.php"><li>Ausências</li></a>
            <a href="recrutamento.php"><li>Recrutamento</li></a>
        </ul>
    </div>

    <div class="main-content">
        <header class="header">
            <h1 class="page-title">Dashboard</h1>
            <div class="header-buttons">
                <div class="time" id="current-time"></div>
                <div class="user-profile">
                    <img src="Apresentação1 (1).png" alt="User" width="20">
                    <span><?php echo $_SESSION['nome']; ?></span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>

                </div>
            </div>
        </header>

        <div class="search-container">
            <input type="text" placeholder="">
            <input type="text" placeholder="">
            <input type="text" placeholder="Pesquisar..." class="search-bar">
        </div>

    </div>
    <script src="UI.js"></script>
</body>
</html>