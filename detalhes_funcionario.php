<?php
include 'protect.php';
// Conectar ao banco de dados
include 'config.php';

// Pegar o ID do funcionário da URL
$id_fun = $_GET['id'];

// Consulta SQL
$sql = "SELECT f.num_mecanografico, f.nome, f.foto, f.bi, f.emissao_bi, f.validade_bi, 
               f.data_nascimento, f.pais, f.morada, f.genero, f.num_agregados, 
               f.contato_emergencia, f.nome_contato_emergencia, f.telemovel, f.email, f.estado, 
               c.nome as cargo_nome, d.nome as departamento_nome, f.tipo_trabalhador, 
               f.num_conta_bancaria, b.banco_nome, f.iban, 
               f.salario_base, f.num_ss, f.data_admissao 
        FROM funcionario f
        LEFT JOIN cargos c ON f.cargo = c.id
        LEFT JOIN departamentos d ON f.departamento = d.id
        LEFT JOIN bancos_ativos b ON f.banco = b.banco_codigo
        WHERE f.id_fun = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_fun);
$stmt->execute();
$result = $stmt->get_result();

// Verificar se encontrou o funcionário
if ($result->num_rows > 0) {
    $dados = $result->fetch_assoc();
} else {
    echo "Funcionário não encontrado!";
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Detalhes do Funcionário</title>
    <link rel="stylesheet" href="all.css/registro3.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
/* Container for Employee Details */
.container {
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-left: 290px;
    padding: 0 30px 30px;
    position: relative;
}
/* Styling for Profile Photo */
.foto-perfil {
    position: absolute;
    top: 50px;
    right: 100px;
    width: 150px;
    height: 150px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid white;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    background: linear-gradient(to right, #5cbea5, #77d6c1);
}

.foto-perfil img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Styling for Information Sections */
.secao {
    background-color: white;
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}
.juntos{display: flex;width: 100%; justify-content: space-between;}
.m{width: 100%; margin-bottom: 20px;background: linear-gradient(to right, rgb(255, 255, 255) 80% ,  #82dab7 );
}
.teste{display: flex; justify-content: space-evenly; margin-left: 0px; width: 100%;}
.n{width: 49%;}
.o{width: 49%;}


.secao h2 {
    color: #333;
    font-size: 18px;
    margin-bottom: 15px;
    font-weight: 600;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.secao p {
    margin: 8px 0;
    font-size: 14px;
}

.secao strong {
    font-weight: 600;
    color: #555;
}

/* Bottom Document Icons */
.doc-icons {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-top: 30px;
    border: 1px dashed #ccc;
    border-radius: 12px;
    padding: 30px;
}

.doc-icon {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.icon-circle {
    width: 80px;
    height: 80px;
    background-color: rgba(92, 190, 165, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
}

.icon-circle i {
    color: #5cbea5;
    font-size: 30px;
}

.doc-icon p {
    font-size: 14px;
    font-weight: 500;
    color: #555;
    margin-bottom: 5px;
}

.doc-icon small {
    font-size: 12px;
    color: #999;
}

/* Edit Button */
.edit-button {
    margin-top: 50px;
    justify-self: right;
    background-color: #5cbea5;
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-weight: 500;
    box-shadow: 0 3px 10px rgba(92, 190, 165, 0.3);

}

.edit-button i {
    font-size: 14px;
}

.edit-button1{
    text-decoration: none;
}
    </style>
</head>
<body>
    <div class="sidebar">
    <div class="logo">
        <a href="UI.php">
            <img src="img/sam2logo-32.png" alt="SAM Logo">
        </a>
        </div>
        <select class="nav-select">
            <option>sam</option>
        </select>
        <ul class="nav-menu">        
            <a href="funcionarios.php"><li class="active">Funcionários</li></a>
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
            <h1 class="page-title">Funcionário | #<?php echo $dados['num_mecanografico']; ?></h1>
            <div class="header-buttons">
                <div class="time" id="current-time"></div>
                <a class="exit-tag" href="logout.php">Sair</a>
                <div class="user-profile">
                    <img src="Apresentação1 (1).png" alt="User" width="20">
                    <span><?php echo $_SESSION['nome']; ?></span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </div>
            </div>
        </header>

        <a href="editar_funcionario.php?id=<?php echo $id_fun; ?>" class="edit-button1">
    <button class="edit-button">
        <i class="fas fa-pencil"></i> Editar Funcionário
    </button>
</a>
    </div>

    <div class="container">

        <div class="foto-perfil">
            <img src="<?php echo !empty($dados['foto']) ? $dados['foto'] : 'icones/icons-sam-18.svg'; ?>" alt="Foto de <?php echo $dados['nome']; ?>">
        </div>


        <div class="secao m">
            <h2>Informações Pessoais</h2>
            <div class="teste" >
                <div style="margin-left:-200px;">
                    <p><strong>Nome:</strong> <?php echo $dados['nome']; ?></p>
                    <p><strong>Nº BI:</strong> <?php echo $dados['bi']; ?></p>
                    <p><strong>Emissão:</strong> <?php echo $dados['emissao_bi']; ?></p>
                    <p><strong>Validade:</strong> <?php echo $dados['validade_bi']; ?></p> 
                    <br>
                    <p style="margin-top:-10px; margin-bottom:10px;"><strong>Telefone:</strong> <?php echo $dados['telemovel']; ?></p>
               
                </div>
                <div style="margin-left:-100px;">
                    <p><strong>Nascimento:</strong> <?php echo $dados['data_nascimento']; ?></p>
                    <p><strong>Nacionalidade:</strong> <?php echo $dados['pais']; ?></p>
                    <p><strong>Morada:</strong> <?php echo $dados['morada']; ?></p>    
                    <p><strong>Gênero:</strong> <?php echo $dados['genero']; ?></p>
                    <br>
                    <p style="margin-top:-10px; margin-bottom:10px;"><strong>Email:</strong> <?php echo $dados['email']; ?></p>                

                </div>
                <div style="margin-left:-130px;">
                    <p><strong>Nº de Agregados:</strong> <?php echo $dados['num_agregados']; ?></p>
                    <p><strong>Salário Base:</strong> <?php echo number_format($dados['salario_base'], 2, ',', '.'); ?> AOA</p>
                    <p><strong>Nº da SS:</strong> <?php echo $dados['num_ss']; ?></p>

                </div>                
            </div>


        </div>

        <div class="juntos">
        <!-- Informações Profissionais -->
        <div class="secao n">
            <h2>Informações Profissionais</h2>
            <p><strong>Nº Mecanográfico:</strong> <?php echo $dados['num_mecanografico']; ?></p>
            <p><strong>Departamento:</strong> <?php echo $dados['departamento_nome']; ?></p>
            <p><strong>Cargo:</strong> <?php echo $dados['cargo_nome']; ?></p>
            <p><strong>Tipo:</strong> <?php echo $dados['tipo_trabalhador']; ?></p>
            <p><strong>Estado:</strong> <?php echo $dados['estado']; ?></p>
            <p><strong>Data de Admissão:</strong> <?php echo $dados['data_admissao']; ?></p>
        </div>

        <!-- Informações Bancárias -->
        <div class="secao o">
            <h2>Informações Bancárias</h2>
            <p><strong>Nº Conta:</strong> <?php echo $dados['num_conta_bancaria']; ?></p>
            <p><strong>Banco:</strong> <?php echo $dados['banco_nome']; ?></p>
            <p><strong>IBAN:</strong> <?php echo $dados['iban']; ?></p>
        </div>

        </div>
        
        <!-- Document Icons -->
        <div class="doc-icons" style="grid-column: span 2;">
            <div class="doc-icon">
                <div class="icon-circle">
                    <i class="fas fa-folder"></i>
                </div>
                <p>Documentação do Funcionário</p>
                <small id="docCount">0 elementos</small>
            </div>
            
            <div class="doc-icon">
                <div class="icon-circle">
                    <i class="fas fa-clock"></i>
                </div>
                <p>Frequência e Pontualidade</p>
                <small id="freqCount">0 elementos</small>
            </div>
            
            <div class="doc-icon">
                <div class="icon-circle">
                    <i class="fas fa-file-alt"></i>
                </div>
                <p>Solicitações e Autorizações</p>
                <small id="solicCount">0 elementos</small>
            </div>
            
            <div class="doc-icon">
                <div class="icon-circle">
                    <i class="fas fa-folder-open"></i>
                </div>
                <p>Outros</p>
                <small id="outrosCount">0 elementos</small>
            </div>
        </div>
    </div>

    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('current-time').textContent = `${hours}:${minutes}:${seconds}`;
        }

        // Initial update
        updateTime();
        
        // Update every second
        setInterval(updateTime, 1000);

        // Função para carregar a contagem de documentos
        function loadDocumentCounts() {
            const employeeId = <?php echo $id_fun; ?>;
            
            fetch(`get_document_counts.php?employeeId=${employeeId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('docCount').textContent = `${data.documentacao || 0} ${data.documentacao === 1 ? 'documento' : 'documentos'}`;
                    document.getElementById('freqCount').textContent = `${data.frequencia || 0} ${data.frequencia === 1 ? 'documento' : 'documentos'}`;
                    document.getElementById('solicCount').textContent = `${data.solicitacoes || 0} ${data.solicitacoes === 1 ? 'documento' : 'documentos'}`;
                    document.getElementById('outrosCount').textContent = `${data.outros || 0} ${data.outros === 1 ? 'documento' : 'documentos'}`;
                })
                .catch(error => console.error('Erro ao carregar contadores:', error));
        }

        // Carregar contadores quando a página carregar
        document.addEventListener('DOMContentLoaded', loadDocumentCounts);
    </script>
</body>
</html>