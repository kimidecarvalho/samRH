<?php
session_start();
include('../config.php');

if (!isset($_SESSION['id_adm'])) {
    die("Acesso negado");
}

// Obter o id_empresa do administrador
$admin_id = $_SESSION['id_adm'];
$sql_admin = "SELECT e.id_empresa FROM empresa e WHERE e.adm_id = ?";
$stmt_admin = $conn->prepare($sql_admin);
$stmt_admin->bind_param("i", $admin_id);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();
$admin = $result_admin->fetch_assoc();

if (!$admin) {
    die("Nenhuma empresa encontrada para este administrador.");
}

$empresa_id = $admin['id_empresa'];

// Buscar departamentos
$sql_departamentos = "SELECT id, nome FROM departamentos WHERE empresa_id = ? ORDER BY nome";
$stmt_departamentos = $conn->prepare($sql_departamentos);
$stmt_departamentos->bind_param("i", $empresa_id);
$stmt_departamentos->execute();
$result_departamentos = $stmt_departamentos->get_result();
$departamentos = array();
while ($row = $result_departamentos->fetch_assoc()) {
    $departamentos[] = $row;
}

// Buscar cargos
$sql_cargos = "SELECT c.id, c.nome, c.salario_base, d.nome as departamento_nome FROM cargos c JOIN departamentos d ON c.departamento_id = d.id WHERE c.empresa_id = ? ORDER BY d.nome, c.nome";
$stmt_cargos = $conn->prepare($sql_cargos);
$stmt_cargos->bind_param("i", $empresa_id);
$stmt_cargos->execute();
$result_cargos = $stmt_cargos->get_result();
$cargos = array();
while ($row = $result_cargos->fetch_assoc()) {
    $cargos[] = $row;
}

// Buscar bancos ativos e inativos
$sql_bancos = "SELECT id, banco_nome, banco_codigo, ativo FROM bancos_ativos WHERE empresa_id = ? ORDER BY banco_nome";
$stmt_bancos = $conn->prepare($sql_bancos);
$stmt_bancos->bind_param("i", $empresa_id);
$stmt_bancos->execute();
$result_bancos = $stmt_bancos->get_result();
$bancos = array();
while ($row = $result_bancos->fetch_assoc()) {
    $bancos[] = $row;
}

// Lista de bancos predefinidos (nomes/códigos conhecidos)
$predefinidos = [
    'Banco Angolano de Investimentos (BAI)', 'Banco BIC', 'Banco Caixa Geral Angola',
    'Banco Comercial Angolano (BCA)', 'Banco de Desenvolvimento de Angola (BDA)',
    'Banco de Poupança e Crédito (BPC)', 'Banco Económico', 'Banco Fomento Angola (BFA)',
    'Banco Millennium Atlântico', 'Banco Sol', 'Banco Valor', 'Banco VTB África', 'Banco Yetu'
];
$predef_codigos = ['BAI','BIC','BCGA','BCA','BDA','BPC','BE','BFA','BMA','SOL','VALOR','VTB','YETU'];
$bancos_predefinidos = [];
$bancos_usuario = [];
foreach ($bancos as $banco) {
    if (in_array($banco['banco_nome'], $predefinidos) || in_array($banco['banco_codigo'], $predef_codigos)) {
        $bancos_predefinidos[] = $banco;
    } else {
        $bancos_usuario[] = $banco;
    }
}

// Buscar status e valores dos subsídios opcionais
$subs_ativos = [];
$subs_valores = [];
$sql = "SELECT nome, ativo, valor_padrao FROM subsidios_padrao WHERE empresa_id = ? AND tipo = 'opcional'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $subs_ativos[$row['nome']] = (int)$row['ativo'];
    $subs_valores[$row['nome']] = $row['valor_padrao'];
}

// Buscar subsídios obrigatórios
$sql_subsidios = "SELECT nome, valor_padrao FROM subsidios_padrao WHERE empresa_id = ? AND tipo = 'obrigatorio'";
$stmt_subsidios = $conn->prepare($sql_subsidios);
$stmt_subsidios->bind_param("i", $empresa_id);
$stmt_subsidios->execute();
$result_subsidios = $stmt_subsidios->get_result();
$subsidios_valores = [];
while ($row = $result_subsidios->fetch_assoc()) {
    $subsidios_valores[$row['nome']] = $row['valor_padrao'];
}

// Definir valores padrão se não existirem
if (!isset($subsidios_valores['noturno'])) $subsidios_valores['noturno'] = 35.00;
if (!isset($subsidios_valores['horas_extras'])) $subsidios_valores['horas_extras'] = 50.00;
if (!isset($subsidios_valores['risco'])) $subsidios_valores['risco'] = 20.00;

// Criar tabela de horários personalizados se não existir
$sql_create_horarios = "CREATE TABLE IF NOT EXISTS horarios_funcionarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    funcionario_id INT NOT NULL,
    hora_entrada TIME NOT NULL,
    hora_saida TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (funcionario_id) REFERENCES funcionario(id_fun),
    UNIQUE KEY (funcionario_id)
)";
$conn->query($sql_create_horarios);

// Processar atualização de horários
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_horarios'])) {
    $funcionario_id = $_POST['funcionario_id'];
    $hora_entrada = $_POST['hora_entrada'];
    $hora_saida = $_POST['hora_saida'];
    
    $sql_update = "INSERT INTO horarios_funcionarios (funcionario_id, hora_entrada, hora_saida) 
                   VALUES (?, ?, ?) 
                   ON DUPLICATE KEY UPDATE 
                   hora_entrada = VALUES(hora_entrada),
                   hora_saida = VALUES(hora_saida)";
    
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("iss", $funcionario_id, $hora_entrada, $hora_saida);
    
    if ($stmt->execute()) {
        echo "<script>alert('Horários atualizados com sucesso!');</script>";
    } else {
        echo "<script>alert('Erro ao atualizar horários: " . $stmt->error . "');</script>";
    }
}

// Processar adição de novo banco
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['banco_nome'], $_POST['banco_codigo'])) {
    $novo_nome = trim($_POST['banco_nome']);
    $novo_codigo = trim($_POST['banco_codigo']);
    if ($novo_nome && $novo_codigo) {
        $stmt_add = $conn->prepare("INSERT INTO bancos_ativos (empresa_id, banco_nome, banco_codigo, ativo) VALUES (?, ?, ?, 1)");
        $stmt_add->bind_param("iss", $empresa_id, $novo_nome, $novo_codigo);
        $stmt_add->execute();
        header('Location: rh_config.php');
        exit;
    }
}

// Garantir que todos os funcionários ativos estejam na tabela de horários
$sql_popular_horarios = "INSERT IGNORE INTO horarios_funcionarios (funcionario_id, hora_entrada, hora_saida)
SELECT f.id_fun, '08:00', '16:00'
FROM funcionario f
WHERE f.estado = 'Ativo' AND NOT EXISTS (
    SELECT 1 FROM horarios_funcionarios h WHERE h.funcionario_id = f.id_fun
)";
$conn->query($sql_popular_horarios);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Configurações de RH - Dashboard RH</title>
    <link rel="stylesheet" href="../all.css/registro3.css">
    <link rel="stylesheet" href="../all.css/configuracoes.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/rh_config.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="preload" href="https://apps.rokt.com/icons/rokt-icons.woff" as="font" type="font/woff" crossorigin>
    <script src="js/utils.js"></script>
    <!-- Adicionar CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-switch .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .toggle-switch .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        .toggle-switch input:checked + .slider {
            background-color: #3EB489;
        }

        .toggle-switch input:checked + .slider:before {
            transform: translateX(26px);
        }

        .toggle-switch input:focus + .slider {
            box-shadow: 0 0 1px #3EB489;
        }

        .toggle-switch input:disabled + .slider {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Estilos para os sliders de porcentagem */
        .slider-container {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 10px 0 0 0;
        }

        .custom-slider {
            -webkit-appearance: none;
            width: 100%;
            height: 6px;
            border-radius: 3px;
            background: #e0e0e0;
            outline: none;
            transition: background 0.2s;
        }

        .custom-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #3EB489;
            cursor: pointer;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }

        .custom-slider::-webkit-slider-thumb:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        .custom-slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #3EB489;
            cursor: pointer;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }

        .custom-slider::-moz-range-thumb:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        .slider-value {
            font-weight: 600;
            color: #3EB489;
            font-size: 0.93em;
        }

        .slider-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #888;
            font-size: 0.93em;
            margin-top: 10px;
        }

        .subsidio-card {
            background: #fff;
            border: 1.5px solid #e0e0e0;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            padding: 22px 18px 18px 18px;
            position: relative;
            margin-bottom: 0;
            min-height: 150px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .subsidio-card > div:not(:last-child) {
            margin-bottom: 14px;
        }
        .subsidio-card .input-subsidio-mes {
            width: 100px;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95em;
            color: #333;
        }

        .subsidio-card .input-subsidio-mes:focus {
            border-color: #3EB489;
            outline: none;
            box-shadow: 0 0 0 2px rgba(62, 180, 137, 0.1);
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
            <a href="seguranca.php"><li>Segurança</li></a>
            <a href="privacidade.php"><li>Privacidade</li></a>
            <a href="rh_config.php"><li class="active">Configurações de RH</li></a>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="profile-card">
            <h1>Configurações de Recursos Humanos</h1>

            <!-- Políticas de Trabalho (agora no topo) -->
            <div class="rh-section">
                <h3>Políticas de Trabalho</h3>
                <div class="rh-details">
                    <div class="policies-grid">
                        <div class="policy-card">
                            <h4>Horário de Trabalho</h4>
                            <p>Segunda a Sexta: 8h - 18h</p>
                            <button class="btn-primary btn-editar-horarios">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                        </div>
                        <div class="policy-card">
                            <h4>Política de Home Office</h4>
                            <p>2 dias por semana permitidos</p>
                            <button class="btn-primary">Editar</button>
                        </div>
                        <div class="policy-card">
                            <h4>Código de Vestimenta</h4>
                            <p>Vestuário casual e profissional</p>
                            <button class="btn-primary">Editar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Container de Departamentos e Cargos -->
            <div class="rh-section" style="margin-bottom: 30px;">
                <h3 style="margin-top:0;">Gestão de Departamentos e Cargos</h3>
                <div class="custom-table-container" style="box-shadow:none; padding:0; background:transparent;">
                    <h4 style="margin:0 0 10px 0; font-size:18px; color:#3EB489;">Departamentos Cadastrados</h4>
                    <p style="margin-bottom: 10px; color:#555;">Departamentos organizam sua empresa. Veja abaixo os cadastrados:</p>
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Nome do Departamento</th>
                                <th style="text-align:center; width:160px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departamentos as $dep): ?>
                                <tr>
                                    <td><?= htmlspecialchars($dep['nome']) ?></td>
                                    <td style="text-align:center;">
                                        <div class="action-btns">
                                            <form method="get" action="#" style="display:inline;">
                                                <input type="hidden" name="acao" value="editar">
                                                <input type="hidden" name="id" value="<?= $dep['id'] ?>">
                                                <button type="button" class="btn-edit"><i class="fa-solid fa-pen-to-square"></i> Editar</button>
                                            </form>
                                            <form method="get" action="gerenciar_departamentos.php" style="display:inline;">
                                                <input type="hidden" name="acao" value="excluir">
                                                <input type="hidden" name="id" value="<?= $dep['id'] ?>">
                                                <button type="submit" class="btn-danger"><i class="fa-solid fa-trash"></i> Excluir</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p style="margin:18px 0 10px 0; color:#555;">Adicione um novo departamento para organizar melhor sua empresa.</p>
                    <form method="post" action="gerenciar_departamentos.php" class="add-form" style="margin-bottom: 20px;">
                        <div class="add-form-row">
                            <input type="hidden" name="acao" value="adicionar_departamento">
                            <input type="text" name="nome_departamento" placeholder="Nome do Departamento" required style="flex:1;">
                        </div>
                        <button type="submit" class="add-form-btn"><i class="fa-solid fa-plus"></i> Adicionar Departamento</button>
                    </form>
                </div>
                <div class="custom-table-container" style="box-shadow:none; padding:0; background:transparent; margin-top:30px;">
                    <h4 style="margin:0 0 10px 0; font-size:18px; color:#3EB489;">Cargos Cadastrados</h4>
                    <p style="margin-bottom: 10px; color:#555;">Cargos são funções associadas a departamentos. Veja abaixo os cadastrados:</p>
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Departamento</th>
                                <th>Cargo</th>
                                <th>Salário Base</th>
                                <th style="text-align:center; width:160px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cargos as $cargo): ?>
                                <tr>
                                    <td><?= htmlspecialchars($cargo['departamento_nome']) ?></td>
                                    <td><?= htmlspecialchars($cargo['nome']) ?></td>
                                    <td><?= number_format($cargo['salario_base'], 2, ',', '.') ?> Kz</td>
                                    <td style="text-align:center;">
                                        <div class="action-btns">
                                            <form method="get" action="#" style="display:inline;">
                                                <input type="hidden" name="acao" value="editar">
                                                <input type="hidden" name="id" value="<?= $cargo['id'] ?>">
                                                <button type="button" class="btn-edit"><i class="fa-solid fa-pen-to-square"></i> Editar</button>
                                            </form>
                                            <form method="get" action="gerenciar_cargos.php" style="display:inline;">
                                                <input type="hidden" name="acao" value="excluir">
                                                <input type="hidden" name="id" value="<?= $cargo['id'] ?>">
                                                <button type="submit" class="btn-danger"><i class="fa-solid fa-trash"></i> Excluir</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p style="margin:18px 0 10px 0; color:#555;">Adicione um novo cargo e associe a um departamento existente.</p>
                    <form method="post" action="gerenciar_cargos.php" class="add-form" style="margin-bottom: 20px;">
                        <div class="add-form-row">
                            <input type="hidden" name="acao" value="adicionar_cargo">
                            <select name="departamento_id" required style="flex:1;">
                                <option value="">Selecione o Departamento</option>
                                <?php foreach ($departamentos as $dep): ?>
                                    <option value="<?= $dep['id'] ?>"><?= htmlspecialchars($dep['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="nome_cargo" placeholder="Nome do Cargo" required style="flex:1;">
                            <input type="number" name="salario_base" placeholder="Salário Base" min="0" step="0.01" required style="width:150px;">
                        </div>
                        <button type="submit" class="add-form-btn"><i class="fa-solid fa-plus"></i> Adicionar Cargo</button>
                    </form>
                </div>
            </div>

            <!-- Container de Bancos -->
            <div class="rh-section" style="margin-bottom: 30px;">
                <h3 style="margin-top:0;">Configuração de Bancos</h3>
                <p>Selecione os bancos que deseja disponibilizar para pagamentos ou adicione um novo banco.</p>
                <div class="bancos-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 18px;">
                    <?php foreach (array_merge($bancos_predefinidos, $bancos_usuario) as $banco): ?>
                        <?php $isUser = !in_array($banco['banco_nome'], $predefinidos) && !in_array($banco['banco_codigo'], $predef_codigos); ?>
                        <div class="banco-card<?= $banco['ativo'] ? ' banco-ativo' : '' ?><?= $isUser ? ' banco-user' : '' ?>" style="border-radius: 12px; border: 1.5px solid #e0e0e0; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 18px 18px 12px 18px; background: #fff; display: flex; flex-direction: column; position: relative; min-height: 160px;">
                            <div class="banco-nome" style="font-weight: 600; font-size: 1.08em; color: #222; margin-bottom: 2px; max-width: 75%; word-break: break-word;"> <?= htmlspecialchars($banco['banco_nome']) ?> </div>
                            <div class="banco-codigo" style="font-size: 0.98em; color: #888; margin-bottom: 10px;"> <?= htmlspecialchars($banco['banco_codigo']) ?> </div>
                            <div class="banco-toggle" style="margin-top: auto;">
                                <label class="toggle-switch">
                                    <input type="checkbox" class="toggle-banco" data-id="<?= $banco['id'] ?>" <?= $banco['ativo'] ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                                <?php $ativo = (int)$banco['ativo']; ?>
                                <span class="banco-status <?= $ativo === 1 ? 'ativo' : 'inativo' ?>"
                                      style="margin-left: 8px; font-size: 0.97em; font-weight: 500; color: <?= $ativo === 1 ? '#3EB489' : '#888' ?>;">
                                    <?= $ativo === 1 ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </div>
                            <?php if ($isUser): ?>
                                <div class="banco-actions" style="position: absolute; right: 12px; top: 12px; display: flex; gap: 8px;">
                                    <button type="button" class="btn-edit" style="background: none; border: none; color: #3EB489; font-size: 1.1em; cursor: pointer; padding: 2px 6px;"><i class="fa-solid fa-pen-to-square"></i></button>
                                    <form method="post" action="gerenciar_bancos.php" style="display:inline;">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id" value="<?= $banco['id'] ?>">
                                        <button type="submit" class="btn-danger" style="background: none; border: none; color: #e74c3c; font-size: 1.1em; cursor: pointer; padding: 2px 6px;"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p style="margin:18px 0 10px 0; color:#555;">Adicione um novo banco para disponibilizar para pagamentos.</p>
                <form method="post" action="" class="add-form" style="margin-bottom: 20px;">
                    <div class="add-form-row">
                        <input type="text" name="banco_nome" placeholder="Nome do Banco" required style="flex:1;">
                        <input type="text" name="banco_codigo" placeholder="Sigla do Banco" required style="flex:1;">
                    </div>
                    <button type="submit" class="add-form-btn"><i class="fa-solid fa-plus"></i> Adicionar Novo Banco</button>
                </form>
            </div>

            <!-- Configuração de Subsídios -->
            <div class="rh-section" style="margin-bottom: 30px;">
                <h3 style="margin-top:0; color:#3EB489;">Configuração de Subsídios</h3>
                <p>Configure os subsídios padrão da empresa e defina quais podem ser personalizados por funcionário.</p>
                <div class="subsidios-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 18px;">
                    <!-- Férias -->
                    <div class="subsidio-card" style="background:#fff; border:1.5px solid #e0e0e0; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.04); padding:18px; position:relative;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <span style="font-weight:600; color:#222;">Férias</span>
                            <span style="background:#ffeaea; color:#e74c3c; font-size:0.93em; padding:2px 10px; border-radius:6px; font-weight:500;">Obrigatório</span>
                        </div>
                        <div style="color:#555; font-size:0.98em; margin-bottom:8px;">100% do salário base</div>
                        <div style="color:#888; font-size:0.93em;">Subsídio obrigatório por lei</div>
                    </div>
                    <!-- 13º Mês -->
                    <div class="subsidio-card" style="background:#fff; border:1.5px solid #e0e0e0; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.04); padding:18px; position:relative;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <span style="font-weight:600; color:#222;">13.º Mês</span>
                            <span style="background:#ffeaea; color:#e74c3c; font-size:0.93em; padding:2px 10px; border-radius:6px; font-weight:500;">Obrigatório</span>
                        </div>
                        <div style="color:#555; font-size:0.98em; margin-bottom:8px;">100% do salário base</div>
                        <div style="color:#888; font-size:0.93em;">Subsídio obrigatório por lei</div>
                    </div>
                    <!-- Nocturno / Turno (com slider) -->
                    <div class="subsidio-card" style="background:#fff; border:1.5px solid #e0e0e0; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.04); padding:18px; position:relative;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <span style="font-weight:600; color:#222;">Nocturno / Turno</span>
                            <span style="background:#ffeaea; color:#e74c3c; font-size:0.93em; padding:2px 10px; border-radius:6px; font-weight:500;">Obrigatório</span>
                        </div>
                        <div class="slider-container">
                            <input type="range" min="20" max="50" id="slider-nocturno" class="custom-slider" style="flex:1;" value="<?php echo $subsidios_valores['noturno']; ?>">
                        </div>
                        <div class="slider-info">
                            <span>Percentual sobre o salário por hora noturna</span>
                            <span id="valor-nocturno-info" class="slider-value"><?php echo $subsidios_valores['noturno']; ?>%</span>
                        </div>
                    </div>
                    <!-- Horas Extras (novo obrigatório) -->
                    <div class="subsidio-card" style="background:#fff; border:1.5px solid #e0e0e0; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.04); padding:18px; position:relative;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <span style="font-weight:600; color:#222;">Horas Extras</span>
                            <span style="background:#ffeaea; color:#e74c3c; font-size:0.93em; padding:2px 10px; border-radius:6px; font-weight:500;">Obrigatório</span>
                        </div>
                        <div class="slider-container">
                            <input type="range" min="20" max="100" id="slider-horas-extras" class="custom-slider" style="flex:1;" value="<?php echo $subsidios_valores['horas_extras']; ?>">
                        </div>
                        <div class="slider-info">
                            <span>Percentual sobre o valor da hora normal</span>
                            <span id="valor-horas-extras-info" class="slider-value"><?php echo $subsidios_valores['horas_extras']; ?>%</span>
                        </div>
                    </div>
                    <!-- Risco / Periculosidade (com slider) -->
                    <div class="subsidio-card" style="background:#fff; border:1.5px solid #e0e0e0; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.04); padding:18px; position:relative;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <span style="font-weight:600; color:#222;">Risco / Periculosidade</span>
                            <span style="background:#ffeaea; color:#e74c3c; font-size:0.93em; padding:2px 10px; border-radius:6px; font-weight:500;">Obrigatório</span>
                        </div>
                        <div class="slider-container">
                            <input type="range" min="10" max="30" id="slider-risco" class="custom-slider" style="flex:1;" value="<?php echo $subsidios_valores['risco']; ?>">
                        </div>
                        <div class="slider-info">
                            <span>Percentual sobre o salário base</span>
                            <span id="valor-risco-info" class="slider-value"><?php echo $subsidios_valores['risco']; ?>%</span>
                        </div>
                    </div>
                    <!-- Alimentação (opcional, com switch) -->
                    <div class="subsidio-card subsidio-opcional" data-subsidio="alimentacao" style="background:#fff; border:1.5px solid #e0e0e0; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.04); padding:18px; position:relative;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <span style="font-weight:600; color:#222;">Alimentação</span>
                            <span style="background:#eafaf1; color:#3EB489; font-size:0.93em; padding:2px 10px; border-radius:6px; font-weight:500;">Opcional</span>
                        </div>
                        <div style="color:#555; font-size:0.98em; margin-bottom:8px; display:flex; align-items:center; gap:8px;">
                            <input type="number" class="input-subsidio-mes" value="<?= isset($subs_valores['alimentacao']) ? $subs_valores['alimentacao'] : 0 ?>"> kz/mês
                        </div>
                        <div style="color:#888; font-size:0.93em; margin-bottom:8px; display: flex; align-items: center; justify-content: space-between;">
                            <span>Subsídio Opcional</span>
                            <label class="toggle-switch" style="position: relative; z-index: 2;">
                                <input type="checkbox" class="toggle-subsidio" data-subsidio="alimentacao" <?= !empty($subs_ativos['alimentacao']) ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <!-- Transporte (opcional, com switch) -->
                    <div class="subsidio-card subsidio-opcional" data-subsidio="transporte" style="background:#fff; border:1.5px solid #e0e0e0; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.04); padding:18px; position:relative;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <span style="font-weight:600; color:#222;">Transporte</span>
                            <span style="background:#eafaf1; color:#3EB489; font-size:0.93em; padding:2px 10px; border-radius:6px; font-weight:500;">Opcional</span>
                        </div>
                        <div style="color:#555; font-size:0.98em; margin-bottom:8px; display:flex; align-items:center; gap:8px;">
                            <input type="number" class="input-subsidio-mes" value="<?= isset($subs_valores['transporte']) ? $subs_valores['transporte'] : 0 ?>"> kz/mês
                        </div>
                        <div style="color:#888; font-size:0.93em; margin-bottom:8px; display: flex; align-items: center; justify-content: space-between;">
                            <span>Subsídio Opcional</span>
                            <label class="toggle-switch" style="position: relative; z-index: 2;">
                                <input type="checkbox" class="toggle-subsidio" data-subsidio="transporte" <?= !empty($subs_ativos['transporte']) ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <!-- Comunicação (opcional, com switch) -->
                    <div class="subsidio-card subsidio-opcional" data-subsidio="comunicacao" style="background:#fff; border:1.5px solid #e0e0e0; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.04); padding:18px; position:relative;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <span style="font-weight:600; color:#222;">Comunicação</span>
                            <span style="background:#eafaf1; color:#3EB489; font-size:0.93em; padding:2px 10px; border-radius:6px; font-weight:500;">Opcional</span>
                        </div>
                        <div style="color:#555; font-size:0.98em; margin-bottom:8px; display:flex; align-items:center; gap:8px;">
                            <input type="number" class="input-subsidio-mes" value="<?= isset($subs_valores['comunicacao']) ? $subs_valores['comunicacao'] : 0 ?>"> kz/mês
                        </div>
                        <div style="color:#888; font-size:0.93em; margin-bottom:8px; display: flex; align-items: center; justify-content: space-between;">
                            <span>Subsídio Opcional</span>
                            <label class="toggle-switch" style="position: relative; z-index: 2;">
                                <input type="checkbox" class="toggle-subsidio" data-subsidio="comunicacao" <?= !empty($subs_ativos['comunicacao']) ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <!-- Saúde / Seguro (opcional, com switch) -->
                    <div class="subsidio-card subsidio-opcional" data-subsidio="saude" style="background:#fff; border:1.5px solid #e0e0e0; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.04); padding:18px; position:relative;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <span style="font-weight:600; color:#222;">Saúde / Seguro</span>
                            <span style="background:#eafaf1; color:#3EB489; font-size:0.93em; padding:2px 10px; border-radius:6px; font-weight:500;">Opcional</span>
                        </div>
                        <div style="color:#555; font-size:0.98em; margin-bottom:8px; display:flex; align-items:center; gap:8px;">
                            <input type="number" class="input-subsidio-mes" value="<?= isset($subs_valores['saude']) ? $subs_valores['saude'] : 0 ?>"> kz/mês
                        </div>
                        <div style="color:#888; font-size:0.93em; margin-bottom:8px; display: flex; align-items: center; justify-content: space-between;">
                            <span>Subsídio Opcional</span>
                            <label class="toggle-switch" style="position: relative; z-index: 2;">
                                <input type="checkbox" class="toggle-subsidio" data-subsidio="saude" <?= !empty($subs_ativos['saude']) ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
                <button class="add-form-btn" style="background:#3EB489; color:#fff; font-weight:600; padding: 10px 28px; border-radius:6px; font-size:1.08em;">Salvar Configurações de Subsídios</button>
            </div>

            <!-- Modal Funcionários Subsídio -->
            <div class="modal fade" id="modalFuncionariosSubsidio" tabindex="-1" aria-labelledby="modalFuncionariosSubsidioLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" style="min-width:1000px;max-width:1300px;width:90vw;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalFuncionariosSubsidioLabel">Gerenciar Subsídio para Funcionários</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            <div id="lista-funcionarios-subsidio">
                                Carregando funcionários...
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rh-section">
                <h3>Férias e Licenças</h3>
                <div class="rh-details">
                    <div class="detail-item">
                        <div>
                            <strong>Dias de Férias Disponíveis</strong>
                            <p>30 dias por ano</p>
                        </div>
                        <button class="btn-primary">Configurar</button>
                    </div>
                    <div class="detail-item">
                        <div>
                            <strong>Tipos de Licença</strong>
                            <p>Maternidade, Paternidade, Médica</p>
                        </div>
                        <button class="btn-primary">Gerenciar</button>
                    </div>
                </div>
            </div>

            <div class="rh-section">
                <h3>Benefícios</h3>
                <div class="rh-details">
                    <div class="policies-grid">
                        <div class="policy-card">
                            <h4>Plano de Saúde</h4>
                            <p>Cobertura para funcionário e dependentes</p>
                            <button class="btn-primary">Personalizar</button>
                        </div>
                        <div class="policy-card">
                            <h4>Vale Refeição</h4>
                            <p>R$ 35,00 por dia útil</p>
                            <button class="btn-primary">Ajustar</button>
                        </div>
                        <div class="policy-card">
                            <h4>Auxílio Educação</h4>
                            <p>Reembolso de 50% de cursos</p>
                            <button class="btn-primary">Detalhes</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rh-section">
                <h3>Avaliação de Desempenho</h3>
                <div class="rh-details">
                    <div class="detail-item">
                        <div>
                            <strong>Ciclo de Avaliação</strong>
                            <p>Semestral, com feedback contínuo</p>
                        </div>
                        <button class="btn-primary">Configurar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../js/theme.js"></script>
<!-- Adicionar scripts do Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
<script>
// Função para mostrar mensagens de feedback
function mostrarMensagem(tipo, mensagem) {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999;';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${tipo}`;
    toast.style.cssText = `
        background: ${tipo === 'success' ? '#e8f5e9' : tipo === 'error' ? '#ffebee' : '#fff3e0'};
        color: ${tipo === 'success' ? '#2e7d32' : tipo === 'error' ? '#c62828' : '#ef6c00'};
        padding: 12px 20px;
        border-radius: 8px;
        margin-bottom: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-width: 300px;
        animation: slideIn 0.3s ease-out;
    `;

    const messageSpan = document.createElement('span');
    messageSpan.textContent = mensagem;
    toast.appendChild(messageSpan);

    const closeButton = document.createElement('button');
    closeButton.innerHTML = '&times;';
    closeButton.style.cssText = `
        background: none;
        border: none;
        color: inherit;
        font-size: 20px;
        cursor: pointer;
        padding: 0 0 0 10px;
    `;
    closeButton.onclick = () => {
        toast.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    };
    toast.appendChild(closeButton);

    document.getElementById('toast-container').appendChild(toast);

    // Adiciona estilos para as animações
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);

    // Remove a mensagem após 5 segundos
    setTimeout(() => {
        if (toast.parentNode) {
            toast.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }
    }, 5000);
}

// Função para tratar erros
function handleError(error, mensagemPadrao) {
    console.error(error);
    mostrarMensagem('error', error.message || mensagemPadrao);
}

document.querySelectorAll('.toggle-banco').forEach(toggle => {
    toggle.addEventListener('change', async function() {
        try {
        const bancoId = this.getAttribute('data-id');
        const ativo = this.checked ? 1 : 0;
            
            const response = await fetch('atualizar_banco.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${bancoId}&ativo=${ativo}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.closest('.banco-card').classList.toggle('banco-ativo', ativo === 1);
                const statusSpan = this.closest('.banco-card').querySelector('.banco-status');
                statusSpan.textContent = ativo ? 'Ativo' : 'Inativo';
                statusSpan.className = 'banco-status ' + (ativo ? 'ativo' : 'inativo');
                statusSpan.style.color = ativo ? '#3EB489' : '#888';
                mostrarMensagem('success', 'Status do banco atualizado com sucesso!');
            } else {
                throw new Error(data.message || 'Erro ao atualizar banco');
            }
        } catch (error) {
            handleError(error, 'Erro ao atualizar status do banco');
            // Reverter o toggle em caso de erro
            this.checked = !this.checked;
        }
    });
});

// Adicionar event listeners para os botões de editar
document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', async function() {
        try {
            const form = this.closest('form');
            const id = form.querySelector('input[name="id"]').value;
            const tipo = form.closest('.custom-table-container').querySelector('h4').textContent.includes('Departamentos') ? 'departamento' : 'cargo';
            
            const response = await fetch(`get_item.php?tipo=${tipo}&id=${id}`);
            const data = await response.json();
            
            if (data.success) {
                await abrirModalEdicao(tipo, data.item);
            } else {
                throw new Error(data.message || 'Erro ao carregar dados');
            }
        } catch (error) {
            handleError(error, 'Erro ao carregar dados do item');
        }
    });
});

async function abrirModalEdicao(tipo, item) {
    return new Promise((resolve, reject) => {
        try {
            // Remover modal anterior se existir
            const modalAnterior = document.getElementById('modalEdicao');
            if (modalAnterior) {
                modalAnterior.remove();
            }

            const modalHtml = `
                <div class="modal fade" id="modalEdicao" tabindex="-1" aria-labelledby="modalEdicaoLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalEdicaoLabel">Editar ${tipo === 'departamento' ? 'Departamento' : 'Cargo'}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>
                            <div class="modal-body">
                                <form id="formEdicao">
                                    <input type="hidden" name="id" value="${item.id}">
                                    <input type="hidden" name="tipo" value="${tipo}">
                                    ${tipo === 'cargo' ? `
                                        <div class="mb-3">
                                            <label for="departamento_id" class="form-label">Departamento</label>
                                            <select class="form-control" id="departamento_id" name="departamento_id" required>
                                                ${gerarOpcoesDepartamentos(item.departamento_id)}
                                            </select>
                                        </div>
                                    ` : ''}
                                    <div class="mb-3">
                                        <label for="nome" class="form-label">Nome</label>
                                        <input type="text" class="form-control" id="nome" name="nome" value="${item.nome}" required>
                                    </div>
                                    ${tipo === 'cargo' ? `
                                        <div class="mb-3">
                                            <label for="salario_base" class="form-label">Salário Base</label>
                                            <input type="number" class="form-control" id="salario_base" name="salario_base" value="${item.salario_base}" step="0.01" required>
                                        </div>
                                    ` : ''}
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" onclick="salvarEdicao()">Salvar Alterações</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Adicionar novo modal ao DOM
            document.body.insertAdjacentHTML('beforeend', modalHtml);

            // Inicializar e mostrar o modal
            const modalElement = document.getElementById('modalEdicao');
            const modal = new bootstrap.Modal(modalElement);
            
            // Adicionar evento para quando o modal for fechado
            modalElement.addEventListener('hidden.bs.modal', () => {
                resolve();
            });

            modal.show();
        } catch (error) {
            reject(error);
        }
    });
}

function gerarOpcoesDepartamentos(departamentoIdSelecionado) {
    const departamentos = <?php echo json_encode($departamentos); ?>;
    return departamentos.map(dep => 
        `<option value="${dep.id}" ${dep.id == departamentoIdSelecionado ? 'selected' : ''}>${dep.nome}</option>`
    ).join('');
}

async function salvarEdicao() {
    try {
        const form = document.getElementById('formEdicao');
        const formData = new FormData(form);
        const tipo = formData.get('tipo');

        const response = await fetch('atualizar_item.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            mostrarMensagem('success', 'Alterações salvas com sucesso!');
            // Fechar o modal
            const modalElement = document.getElementById('modalEdicao');
            const modal = bootstrap.Modal.getInstance(modalElement);
            modal.hide();
            // Recarregar a página após um pequeno delay
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            throw new Error(data.message || 'Erro ao salvar alterações');
        }
    } catch (error) {
        handleError(error, 'Erro ao salvar alterações');
    }
}

// Adicionar event listeners para os botões de editar banco
document.querySelectorAll('.banco-actions .btn-edit').forEach(btn => {
    btn.addEventListener('click', async function() {
        try {
            const bancoCard = this.closest('.banco-card');
            const bancoId = bancoCard.querySelector('.toggle-banco').getAttribute('data-id');
            const bancoNome = bancoCard.querySelector('.banco-nome').textContent.trim();
            const bancoCodigo = bancoCard.querySelector('.banco-codigo').textContent.trim();
            
            await abrirModalEdicaoBanco({
                id: bancoId,
                nome: bancoNome,
                codigo: bancoCodigo
            });
        } catch (error) {
            handleError(error, 'Erro ao carregar dados do banco');
        }
    });
});

async function abrirModalEdicaoBanco(banco) {
    return new Promise((resolve, reject) => {
        try {
            // Remover modal anterior se existir
            const modalAnterior = document.getElementById('modalEdicaoBanco');
            if (modalAnterior) {
                modalAnterior.remove();
            }

            const modalHtml = `
                <div class="modal fade" id="modalEdicaoBanco" tabindex="-1" aria-labelledby="modalEdicaoBancoLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalEdicaoBancoLabel">Editar Banco</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>
                            <div class="modal-body">
                                <form id="formEdicaoBanco">
                                    <input type="hidden" name="id" value="${banco.id}">
                                    <div class="mb-3">
                                        <label for="banco_nome" class="form-label">Nome do Banco</label>
                                        <input type="text" class="form-control" id="banco_nome" name="banco_nome" value="${banco.nome}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="banco_codigo" class="form-label">Código do Banco</label>
                                        <input type="text" class="form-control" id="banco_codigo" name="banco_codigo" value="${banco.codigo}" required>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" onclick="salvarEdicaoBanco()">Salvar Alterações</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Adicionar novo modal ao DOM
            document.body.insertAdjacentHTML('beforeend', modalHtml);

            // Inicializar e mostrar o modal
            const modalElement = document.getElementById('modalEdicaoBanco');
            const modal = new bootstrap.Modal(modalElement);
            
            // Adicionar evento para quando o modal for fechado
            modalElement.addEventListener('hidden.bs.modal', () => {
                resolve();
            });

            modal.show();
        } catch (error) {
            reject(error);
        }
    });
}

async function salvarEdicaoBanco() {
    try {
        const form = document.getElementById('formEdicaoBanco');
        const formData = new FormData(form);

        const response = await fetch('atualizar_banco.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            mostrarMensagem('success', 'Banco atualizado com sucesso!');
            // Fechar o modal
            const modalElement = document.getElementById('modalEdicaoBanco');
            const modal = bootstrap.Modal.getInstance(modalElement);
            modal.hide();
            // Recarregar a página após um pequeno delay
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            throw new Error(data.message || 'Erro ao atualizar banco');
        }
    } catch (error) {
        handleError(error, 'Erro ao atualizar banco');
    }
}

// Slider de porcentagem dos subsídios
const sliderNocturno = document.getElementById('slider-nocturno');
const valorNocturnoInfo = document.getElementById('valor-nocturno-info');
if(sliderNocturno && valorNocturnoInfo) {
    sliderNocturno.addEventListener('input', function() {
        const valor = this.value + '%';
        valorNocturnoInfo.textContent = valor;
        valorNocturnoInfo.style.transform = 'scale(1.1)';
        setTimeout(() => {
            valorNocturnoInfo.style.transform = 'scale(1)';
        }, 150);
    });
    sliderNocturno.addEventListener('change', function() {
        salvarPercentualSubsidio('noturno', this.value);
    });
}

const sliderHorasExtras = document.getElementById('slider-horas-extras');
const valorHorasExtrasInfo = document.getElementById('valor-horas-extras-info');
if(sliderHorasExtras && valorHorasExtrasInfo) {
    sliderHorasExtras.addEventListener('input', function() {
        const valor = this.value + '%';
        valorHorasExtrasInfo.textContent = valor;
        valorHorasExtrasInfo.style.transform = 'scale(1.1)';
        setTimeout(() => {
            valorHorasExtrasInfo.style.transform = 'scale(1)';
        }, 150);
    });
    sliderHorasExtras.addEventListener('change', function() {
        salvarPercentualSubsidio('horas_extras', this.value);
    });
}

const sliderRisco = document.getElementById('slider-risco');
const valorRiscoInfo = document.getElementById('valor-risco-info');
if(sliderRisco && valorRiscoInfo) {
    sliderRisco.addEventListener('input', function() {
        const valor = this.value + '%';
        valorRiscoInfo.textContent = valor;
        valorRiscoInfo.style.transform = 'scale(1.1)';
        setTimeout(() => {
            valorRiscoInfo.style.transform = 'scale(1)';
        }, 150);
    });
    sliderRisco.addEventListener('change', function() {
        salvarPercentualSubsidio('risco', this.value);
    });
}

// Função para salvar percentual do subsídio
async function salvarPercentualSubsidio(tipo, valor) {
    try {
        const response = await fetch('atualizar_subsidio_empresa.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                tipo: tipo,
                valor_padrao: valor,
                unidade: 'percentual'
            })
        });
        
        const data = await response.json();
        if (data.success) {
            mostrarMensagem('success', 'Percentual atualizado com sucesso!');
            // Atualizar o valor no slider e no texto
            const slider = document.getElementById(`slider-${tipo}`);
            const valorInfo = document.getElementById(`valor-${tipo}-info`);
            if (slider && valorInfo) {
                slider.value = data.valor_padrao;
                valorInfo.textContent = `${data.valor_padrao}%`;
            }
            
            // Atualizar os valores na tabela
            const rows = document.querySelectorAll('tr[data-funcionario-id]');
            for (const row of rows) {
                const funcionarioId = row.dataset.funcionarioId;
                try {
                    const response = await fetch(`calcular_subsidios.php?funcionario_id=${funcionarioId}&tipo=${tipo}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        const horasCell = row.querySelector('.horas-extras');
                        const valorCell = row.querySelector('.valor-calculado');
                        if (horasCell && valorCell) {
                            horasCell.textContent = formatarHoras(data.horas);
                            valorCell.textContent = `${data.valor_calculado} Kz`;
                        }
                    }
                } catch (error) {
                    console.error(`Erro ao atualizar ${tipo}:`, error);
                }
            }
        } else {
            throw new Error(data.error || 'Erro ao atualizar percentual');
        }
    } catch (error) {
        handleError(error, 'Erro ao atualizar percentual do subsídio');
    }
}

// Função para carregar valores dos subsídios
async function carregarValoresSubsidios() {
    try {
        const response = await fetch('gerir_subsidios.php');
        const data = await response.json();
        
        if (data.success && Array.isArray(data.subsidios)) {
            data.subsidios.forEach(sub => {
                const slider = document.getElementById(`slider-${sub.nome}`);
                const valorInfo = document.getElementById(`valor-${sub.nome}-info`);
                if (slider && valorInfo) {
                    slider.value = sub.valor_padrao;
                    valorInfo.textContent = `${sub.valor_padrao}%`;
                }
            });
        }
    } catch (error) {
        console.error('Erro ao carregar valores dos subsídios:', error);
    }
}

// Configurar os event listeners dos sliders
document.addEventListener('DOMContentLoaded', function() {
    // Carregar valores iniciais
    carregarValoresSubsidios();

    // Configurar sliders
    const sliders = {
        'noturno': { min: 20, max: 50 },
        'horas_extras': { min: 20, max: 100 },
        'risco': { min: 10, max: 30 }
    };

    Object.entries(sliders).forEach(([tipo, config]) => {
        const slider = document.getElementById(`slider-${tipo}`);
        const valorInfo = document.getElementById(`valor-${tipo}-info`);
        
        if (slider && valorInfo) {
            slider.min = config.min;
            slider.max = config.max;
            
            slider.addEventListener('input', function() {
                const valor = this.value + '%';
                valorInfo.textContent = valor;
                valorInfo.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    valorInfo.style.transform = 'scale(1)';
                }, 150);
            });
            
            slider.addEventListener('change', function() {
                salvarPercentualSubsidio(tipo, this.value);
            });
        }
    });
});

// Evento para switches dos subsídios opcionais
const modalFuncionarios = new bootstrap.Modal(document.getElementById('modalFuncionariosSubsidio'));
document.querySelectorAll('.toggle-subsidio').forEach(toggle => {
    toggle.addEventListener('change', async function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const tipo = this.getAttribute('data-subsidio');
        const ativo = this.checked;
        
        try {
            const response = await fetch('atualizar_subsidio_empresa.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    tipo: tipo,
                    ativo: ativo
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                mostrarMensagem('success', 'Subsídio atualizado com sucesso!');
            } else if (data.requires_confirmation) {
                if (confirm(data.message)) {
                    const responseConfirm = await fetch('atualizar_subsidio_empresa.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            tipo: tipo,
                            ativo: ativo,
                            confirmed: true
                        })
                    });
                    
                    const dataConfirm = await responseConfirm.json();
                    
                    if (dataConfirm.success) {
                        mostrarMensagem('success', 'Subsídio atualizado com sucesso!');
                    } else {
                        throw new Error(dataConfirm.error || 'Erro ao atualizar subsídio');
                    }
                } else {
                    this.checked = !this.checked;
                }
            } else {
                throw new Error(data.error || 'Erro ao atualizar subsídio');
            }
        } catch (error) {
            handleError(error, 'Erro ao atualizar subsídio');
            this.checked = !this.checked;
        }
    });
});

// Função para ativar/desativar subsídio para funcionário
async function toggleSubsidioFuncionario(id, tipo, btn) {
    btn.disabled = true;
    
    try {
        const response = await fetch('atualizar_subsidio_funcionario.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                funcionario_id: id,
                tipo: tipo,
                ativo: btn.checked
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarMensagem('success', 'Subsídio atualizado para o funcionário!');
        } else {
            throw new Error(data.error || 'Erro ao atualizar subsídio do funcionário');
        }
    } catch (error) {
        handleError(error, 'Erro ao atualizar subsídio do funcionário');
        btn.checked = !btn.checked; // Reverter o toggle em caso de erro
    } finally {
        btn.disabled = false;
    }
}

// Função para abrir o modal e buscar funcionários
function abrirModalFuncionariosSubs(tipo) {
    const lista = document.getElementById('lista-funcionarios-subsidio');
    lista.innerHTML = 'Carregando funcionários...';
    
    // Nome amigável do subsídio
    const nomes = {
        alimentacao: 'Alimentação',
        transporte: 'Transporte',
        comunicacao: 'Comunicação',
        saude: 'Saúde / Seguro',
        decimo_terceiro: '13.º Mês',
        noturno: 'Nocturno / Turno',
        horas_extras: 'Horas Extras',
        risco: 'Risco / Periculosidade',
        ferias: 'Férias'
    };
    const nomeSubsidio = nomes[tipo] || tipo;
    
    fetch('get_funcionarios_subsidio.php?tipo=' + tipo)
        .then(async res => {
            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.error || 'Erro ao buscar funcionários');
            }
            return data;
        })
        .then(data => {
            if (!data.success || !Array.isArray(data.funcionarios)) {
                throw new Error('Formato de resposta inválido');
            }

            const funcionarios = data.funcionarios;
            if(funcionarios.length === 0) {
                lista.innerHTML = '<div class="alert alert-info">Nenhum funcionário encontrado.</div>';
                return;
            }

            // Se for 13º mês, noturno ou horas_extras, mostrar todos ativos e valor calculado
            if (tipo === 'decimo_terceiro' || tipo === 'noturno' || tipo === 'horas_extras') {
                let html = `<div style='font-weight:600; color:#3EB489; font-size:1.15em; margin-bottom:15px;'>Gerenciando Subsídio: ${nomeSubsidio}</div>`;
                html += `<div class=\"table-responsive\" style=\"min-width:900px;\">`;
                html += `<table class=\"table table-striped table-hover tabela-subsidio-modal\" style=\"border-radius:10px;overflow:hidden;min-width:900px;max-width:1200px;\">\n                  <thead style=\"background:#f5f5f5;\">\n                    <tr>\n                      <th style='padding:12px 18px;white-space:nowrap;'>Nome</th>\n                      <th style='padding:12px 18px;white-space:nowrap;'>Matrícula</th>\n                      <th style='padding:12px 18px;white-space:nowrap;'>Cargo</th>\n                      <th style='padding:12px 18px;white-space:nowrap;'>Departamento</th>\n                      <th style='padding:12px 18px;white-space:nowrap;'>${tipo === 'noturno' ? 'Horas Noturnas' : tipo === 'horas_extras' ? 'Horas Extras' : 'Meses'}</th>\n                      <th style='padding:12px 18px;white-space:nowrap;'>Salário Base</th>\n                      <th style='padding:12px 18px;white-space:nowrap;'>${tipo === 'decimo_terceiro' ? 'Valor 13º Mês' : tipo === 'noturno' ? 'Valor Noturno' : 'Valor Horas Extras'}</th>\n                    </tr>\n                  </thead>\n                  <tbody>`;
                
                // Função para formatar horas
                function formatarHoras(horas) {
                    const horasInt = Math.floor(horas);
                    const minutos = Math.round((horas % 1) * 60);
                    return `${horasInt}:${minutos.toString().padStart(2, '0')}`;
                }

                // Função para calcular valor das horas extras
                function calcularValorHorasExtras(salarioBase, horasExtras, percentual) {
                    const valorHoraNormal = parseFloat(salarioBase) / 160; // 160 horas mensais
                    const valorHoraExtra = valorHoraNormal * (1 + percentual/100);
                    return (valorHoraExtra * horasExtras).toFixed(2);
                }

                // Função para calcular valor do noturno
                function calcularValorNoturno(salarioBase, horasNoturnas, percentual) {
                    const valorHoraNormal = parseFloat(salarioBase) / 160;
                    const valorHoraNoturna = valorHoraNormal * (percentual/100);
                    return (valorHoraNoturna * horasNoturnas).toFixed(2);
                }

                // Função para obter o percentual atual do subsídio
                async function obterPercentualSubsidio(tipo) {
                    try {
                        const response = await fetch('verificar_subsidios.php');
                        const data = await response.json();
                        if (data.success) {
                            const subsidio = data.subsidios.find(s => s.nome === tipo);
                            return subsidio ? parseFloat(subsidio.valor_padrao) : (tipo === 'noturno' ? 35 : 50);
                        }
                        return tipo === 'noturno' ? 35 : 50; // Valores padrão em caso de erro
                    } catch (error) {
                        console.error('Erro ao obter percentual:', error);
                        return tipo === 'noturno' ? 35 : 50; // Valores padrão em caso de erro
                    }
                }

                funcionarios.forEach(f => {
                    let valorCalculado = 'N/A';
                    let salarioBaseStr = f.salario_base ? f.salario_base : '-';
                    let horas = 0;
                    
                    if (f.salario_base) {
                        if (tipo === 'decimo_terceiro' && f.data_admissao) {
                            // Cálculo do 13º mês
                            let dataParts = f.data_admissao.split('T')[0].split('-');
                            let admissao = new Date(dataParts[0], dataParts[1] - 1, dataParts[2]);
                            const agora = new Date();
                            let meses = (agora.getFullYear() - admissao.getFullYear()) * 12 + (agora.getMonth() - admissao.getMonth()) + 1;
                            if (meses > 12) meses = 12;
                            if (meses < 1) meses = 1;
                            valorCalculado = ((parseFloat(f.salario_base) * meses) / 12).toFixed(2);
                        } else if (tipo === 'noturno') {
                            // Buscar horas noturnas do funcionário
                            fetch(`get_horas_noturnas.php?funcionario_id=${f.id}`)
                                .then(response => response.json())
                                .then(async data => {
                                    if (data.success) {
                                        horas = parseFloat(data.horas_noturnas) || 0;
                                        const percentual = await obterPercentualSubsidio('noturno');
                                        valorCalculado = calcularValorNoturno(f.salario_base, horas, percentual);
                                        const row = document.querySelector(`tr[data-funcionario-id=\"${f.id}\"]`);
                                        if (row) {
                                            row.querySelector('.horas-extras').textContent = formatarHoras(horas);
                                            row.querySelector('.valor-calculado').textContent = `${valorCalculado} Kz`;
                                        }
                                    }
                                })
                                .catch(error => console.error('Erro ao buscar horas noturnas:', error));
                        } else if (tipo === 'horas_extras') {
                            // Buscar horas extras do funcionário
                            fetch(`get_horas_extras.php?funcionario_id=${f.id}`)
                                .then(response => response.json())
                                .then(async data => {
                                    if (data.success) {
                                        horas = parseFloat(data.horas_extras) || 0;
                                        const percentual = await obterPercentualSubsidio('horas_extras');
                                        valorCalculado = calcularValorHorasExtras(f.salario_base, horas, percentual);
                                        const row = document.querySelector(`tr[data-funcionario-id=\"${f.id}\"]`);
                                        if (row) {
                                            row.querySelector('.horas-extras').textContent = formatarHoras(horas);
                                            row.querySelector('.valor-calculado').textContent = `${valorCalculado} Kz`;
                                        }
                                    }
                                })
                                .catch(error => console.error('Erro ao buscar horas extras:', error));
                        }
                    }
                    
                    html += `<tr data-funcionario-id=\"${f.id}\">\n`
                        + `<td style='padding:8px 12px;'>${f.nome}</td>`
                        + `<td style='padding:8px 12px;'>${f.num_mecanografico}</td>`
                        + `<td style='padding:8px 12px;'>${f.cargo}</td>`
                        + `<td style='padding:8px 12px;'>${f.departamento}</td>`
                        + `<td style='padding:8px 12px;' class="horas-extras">${formatarHoras(horas)}</td>`
                        + `<td style='padding:8px 12px;'>${salarioBaseStr}</td>`
                        + `<td style='padding:8px 12px;' class="valor-calculado">${valorCalculado} Kz</td>`
                        + `</tr>`;
                });
                html += '</tbody></table>';
                html += '</div>';
                lista.innerHTML = html;
                return;
            }

            // ...restante do código para outros tipos...
            let html = `<div style='display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;'>
                <div style='font-weight:600; color:#3EB489; font-size:1.15em;'>Gerenciando Subsídio: ${nomeSubsidio}</div>
                <div style='display:flex; align-items:center; gap:10px;'>
                    <span style='color:#666; font-size:0.95em;'>Ativar/Desativar Todos</span>
                    <label class='toggle-switch'>
                        <input type='checkbox' id='toggle-todos' onchange='toggleTodosFuncionarios("${tipo}", this)'>
                        <span class='slider'></span>
                    </label>
                </div>
            </div>`;
            html += `<table class="table table-striped table-hover" style="border-radius:10px;overflow:hidden;min-width:600px;">
              <thead style="background:#f5f5f5;">
                <tr>
                  <th style='padding:10px 12px;'>Nome</th>
                  <th style='padding:10px 12px;'>Matrícula</th>
                  <th style='padding:10px 12px;'>Cargo</th>
                  <th style='padding:10px 12px;'>Departamento</th>
                  <th style='padding:10px 12px;text-align:center;'>Subsídio</th>
                </tr>
              </thead>
              <tbody>`;
            funcionarios.forEach(f => {
                const terminado = f.estado && f.estado.toLowerCase() === 'terminado';
                const ativo = f.subsidios && f.subsidios[tipo] === true;
                html += `<tr style="background:${terminado ? '#ffebee' : (f.id%2===0?'#fafbfc':'#fff')}; color:${terminado ? '#c62828' : '#222'};"${terminado ? " title='Funcionário Terminado'" : ''}>
                    <td style='padding:8px 12px;'>${f.nome}</td>
                    <td style='padding:8px 12px;'>${f.num_mecanografico}</td>
                    <td style='padding:8px 12px;'>${f.cargo}</td>
                    <td style='padding:8px 12px;'>${f.departamento}</td>
                    <td style='padding:8px 12px;text-align:center;'>
                        <label class='toggle-switch' style='${terminado ? 'pointer-events:none;opacity:0.5;cursor:not-allowed;' : ''}'>
                            <input type='checkbox' onchange='toggleSubsidioFuncionario(${f.id}, "${tipo}", this)' ${ativo ? 'checked' : ''} ${terminado ? 'disabled' : ''}>
                            <span class='slider'></span>
                        </label>
                    </td>
                </tr>`;
            });
            html += '</tbody></table>';
            lista.innerHTML = html;
        })
        .catch(error => {
            console.error('Erro:', error);
            lista.innerHTML = `<div class="alert alert-danger">
                <strong>Erro ao carregar funcionários:</strong><br>
                ${error.message}<br>
                Por favor, tente novamente ou contate o suporte.
            </div>`;
        });
    
    modalFuncionarios.show();
}

// Adicionar tooltip customizado para funcionários terminados
(function(){
  let tooltip = document.getElementById('tooltip-terminado');
  if (tooltip) tooltip.remove();
  tooltip = document.createElement('div');
  tooltip.id = 'tooltip-terminado';
  tooltip.style.position = 'fixed';
  tooltip.style.background = 'rgba(198,40,40,0.85)';
  tooltip.style.color = '#fff';
  tooltip.style.padding = '3px 10px';
  tooltip.style.borderRadius = '5px';
  tooltip.style.fontSize = '13px';
  tooltip.style.fontWeight = '400';
  tooltip.style.pointerEvents = 'none';
  tooltip.style.zIndex = '9999';
  tooltip.style.display = 'block';
  tooltip.style.opacity = '0';
  tooltip.style.transition = 'opacity 0.2s';
  tooltip.textContent = 'Funcionário Terminado';
  document.body.appendChild(tooltip);

  document.addEventListener('mouseover', function(e) {
    if(e.target.closest('tr[title="Funcionário Terminado"]')) {
      tooltip.style.opacity = '1';
    }
  });
  document.addEventListener('mousemove', function(e) {
    if(tooltip.style.opacity === '1') {
      tooltip.style.left = (e.clientX + 14) + 'px';
      tooltip.style.top = (e.clientY + 8) + 'px';
    }
  });
  document.addEventListener('mouseout', function(e) {
    if(e.target.closest('tr[title="Funcionário Terminado"]')) {
      tooltip.style.opacity = '0';
    }
  });
})();

// Evento para abrir modal ao clicar no card do subsídio opcional
document.querySelectorAll('.subsidio-card.subsidio-opcional').forEach(card => {
    card.style.cursor = 'pointer';
    card.addEventListener('click', function(e) {
        // Verifica se o clique foi no toggle switch ou seus elementos
        const toggleSwitch = card.querySelector('.toggle-switch');
        if (toggleSwitch && (e.target === toggleSwitch || toggleSwitch.contains(e.target))) {
            return;
        }
        
        // Verifica se o clique foi no input de valor
        const inputValor = card.querySelector('.input-subsidio-mes');
        if (inputValor && (e.target === inputValor || inputValor.contains(e.target))) {
            return;
        }
        
        // Verifica se o toggle está ativo antes de abrir o modal
        const toggle = card.querySelector('.toggle-subsidio');
        if (!toggle.checked) {
            mostrarMensagem('warning', 'Ative o subsídio primeiro para gerenciar os funcionários');
            return;
        }
        
        const tipo = card.getAttribute('data-subsidio');
        abrirModalFuncionariosSubs(tipo);
    });
});

// Adicionar evento para salvar valor_padrao ao sair do campo ou pressionar Enter

document.querySelectorAll('.subsidio-card.subsidio-opcional .input-subsidio-mes').forEach(input => {
    const card = input.closest('.subsidio-card.subsidio-opcional');
    const tipo = card ? card.getAttribute('data-subsidio') : null;
    if (!tipo) return;

    // Salvar ao sair do campo
    input.addEventListener('blur', function() {
        salvarValorPadraoSubs(tipo, this.value);
    });
    // Salvar ao pressionar Enter
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            this.blur(); // dispara o blur
        }
    });
});

async function salvarValorPadraoSubs(tipo, valor) {
    try {
        const response = await fetch('atualizar_subsidio_empresa.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tipo: tipo, valor_padrao: valor })
        });
        const data = await response.json();
        if (data.success) {
            mostrarMensagem('success', 'Valor atualizado com sucesso!');
        } else {
            throw new Error(data.error || 'Erro ao atualizar valor');
        }
    } catch (error) {
        handleError(error, 'Erro ao atualizar valor do subsídio');
    }
}

// Adicionar a função toggleTodosFuncionarios após a função toggleSubsidioFuncionario
async function toggleTodosFuncionarios(tipo, btn) {
    const checkboxes = document.querySelectorAll(`#lista-funcionarios-subsidio input[type="checkbox"]:not([id="toggle-todos"])`);
    const ativo = btn.checked;
    
    // Desabilitar todos os checkboxes durante a operação
    checkboxes.forEach(cb => cb.disabled = true);
    btn.disabled = true;
    
    try {
        // Array para armazenar todas as promessas
        const promises = Array.from(checkboxes).map(async (checkbox) => {
            if (!checkbox.closest('tr').title) { // Ignora funcionários terminados
                const id = checkbox.getAttribute('onchange').match(/\d+/)[0];
                const response = await fetch('atualizar_subsidio_funcionario.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        funcionario_id: id,
                        tipo: tipo,
                        ativo: ativo
                    })
                });
                
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.error || 'Erro ao atualizar subsídio do funcionário');
                }
                
                checkbox.checked = ativo;
            }
        });
        
        // Aguarda todas as operações terminarem
        await Promise.all(promises);
        mostrarMensagem('success', 'Subsídios atualizados com sucesso!');
    } catch (error) {
        handleError(error, 'Erro ao atualizar subsídios');
        // Reverter o toggle em caso de erro
        btn.checked = !btn.checked;
        checkboxes.forEach(cb => {
            if (!cb.closest('tr').title) {
                cb.checked = !ativo;
            }
        });
    } finally {
        // Reabilitar todos os checkboxes
        checkboxes.forEach(cb => cb.disabled = false);
        btn.disabled = false;
    }
}

// Remover o HTML do modal daqui e mover para fora do script
</script>

<!-- Modal de Horários -->
<div class="modal fade" id="modalHorarios" tabindex="-1" aria-labelledby="modalHorariosLabel" aria-hidden="true">
<div class="modal-dialog modal-lg" style="min-width:1000px;max-width:1300px;width:90vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalHorariosLabel">Configurar Horários dos Funcionários</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Funcionário</th>
                                <th>Horário de Entrada</th>
                                <th>Horário de Saída</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaHorarios">
                            <?php
                            $sql_funcionarios = "SELECT f.id_fun, f.nome, h.hora_entrada, h.hora_saida 
                                               FROM funcionario f 
                                               LEFT JOIN horarios_funcionarios h ON f.id_fun = h.funcionario_id 
                                               WHERE f.empresa_id = ? AND f.estado = 'Ativo'
                                               ORDER BY f.nome";
                            $stmt = $conn->prepare($sql_funcionarios);
                            $stmt->bind_param("i", $empresa_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            while ($row = $result->fetch_assoc()) {
                                $hora_entrada = $row['hora_entrada'] ?? '08:30';
                                $hora_saida = $row['hora_saida'] ?? '17:30';
                                echo "<tr>";
                                echo "<td>{$row['nome']}</td>";
                                echo "<td><input type='time' class='form-control hora-entrada' value='{$hora_entrada}'></td>";
                                echo "<td><input type='time' class='form-control hora-saida' value='{$hora_saida}'></td>";
                                echo "<td><button class='btn btn-primary btn-sm salvar-horario' data-funcionario-id='{$row['id_fun']}'>Salvar</button></td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

     // Adicionar cursor pointer e evento de clique para os obrigatórios
     document.querySelectorAll('.subsidio-card:not(.subsidio-opcional)').forEach(card => {
        card.style.cursor = 'pointer';
        card.addEventListener('click', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.classList.contains('custom-slider')) return;
            let tipo = '';
            const nome = card.querySelector('span').textContent.trim().toLowerCase();
            if (nome.includes('nocturno') || nome.includes('noturno')) tipo = 'noturno';
            else if (nome.includes('horas extras')) tipo = 'horas_extras';
            else if (nome.includes('risco')) tipo = 'risco';
            else if (nome.includes('férias')) tipo = 'ferias';
            else if (nome.includes('13')) tipo = 'decimo_terceiro';
            if (tipo) {
                abrirModalFuncionariosSubs(tipo);
            }
        });
    });
    
    // Adicionar evento de clique para o botão de editar horários
    const btnEditarHorarios = document.querySelector('.btn-editar-horarios');
    if (btnEditarHorarios) {
        btnEditarHorarios.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('modalHorarios'));
            modal.show();
        });
    }

    // Adicionar eventos para os botões de salvar
    document.querySelectorAll('.salvar-horario').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const funcionarioId = this.dataset.funcionarioId;
            const horaEntrada = row.querySelector('.hora-entrada').value;
            const horaSaida = row.querySelector('.hora-saida').value;

            // Enviar dados via AJAX
            fetch('rh_config.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `atualizar_horarios=1&funcionario_id=${funcionarioId}&hora_entrada=${horaEntrada}&hora_saida=${horaSaida}`
            })
            .then(response => response.text())
            .then(data => {
                alert('Horários atualizados com sucesso!');
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao atualizar horários');
            });
        });
    });
});

// ... existing code ...
// Função para atualizar valores dos subsídios
async function atualizarValoresSubsidios() {
    const rows = document.querySelectorAll('tr[data-funcionario-id]');
    for (const row of rows) {
        const funcionarioId = row.dataset.funcionarioId;
        const tipo = row.closest('table').dataset.tipo;
        
        if (tipo === 'noturno' || tipo === 'horas_extras') {
            try {
                const response = await fetch(`calcular_subsidios.php?funcionario_id=${funcionarioId}&tipo=${tipo}`);
                const data = await response.json();
                
                if (data.success) {
                    row.querySelector('.horas-extras').textContent = formatarHoras(data.horas);
                    row.querySelector('.valor-calculado').textContent = `${data.valor_calculado} Kz`;
                }
            } catch (error) {
                console.error(`Erro ao atualizar ${tipo}:`, error);
            }
        }
    }
}

// Adicionar evento para atualizar valores quando o slider mudar
document.querySelectorAll('.custom-slider').forEach(slider => {
    slider.addEventListener('change', async function() {
        const tipo = this.id.replace('slider-', '');
        await salvarPercentualSubsidio(tipo, this.value);
        // Atualizar os valores na tabela
        const rows = document.querySelectorAll('tr[data-funcionario-id]');
        for (const row of rows) {
            const funcionarioId = row.dataset.funcionarioId;
            try {
                const response = await fetch(`calcular_subsidios.php?funcionario_id=${funcionarioId}&tipo=${tipo}`);
                const data = await response.json();
                
                if (data.success) {
                    const horasCell = row.querySelector('.horas-extras');
                    const valorCell = row.querySelector('.valor-calculado');
                    if (horasCell && valorCell) {
                        horasCell.textContent = formatarHoras(data.horas);
                        valorCell.textContent = `${data.valor_calculado} Kz`;
                    }
                }
            } catch (error) {
                console.error(`Erro ao atualizar ${tipo}:`, error);
            }
        }
    });
});
// ... existing code ...
</script>

<!-- Adicionar antes do fechamento do body -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php define('INCLUDED_FROM_RH_CONFIG', true);
include 'modal_horarios.php';
?>

</body>
</html>