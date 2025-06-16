<?php 
// Adicionar logs no início do arquivo
error_log("Debug - UI.php - Sessão ID: " . session_id());
error_log("Debug - UI.php - Verificando sessão antes de include protect.php");
if(isset($_SESSION['id_adm'])) {
    error_log("Debug - UI.php - id_adm: " . $_SESSION['id_adm']);
} else {
    error_log("Debug - UI.php - id_adm não está definido na sessão");
}

include 'protect.php';
include 'config.php';

// Mais logs após os includes
error_log("Debug - UI.php - Após include protect.php");
error_log("Debug - UI.php - id_adm: " . (isset($_SESSION['id_adm']) ? $_SESSION['id_adm'] : 'Não definido'));
error_log("Debug - UI.php - id_empresa: " . (isset($_SESSION['id_empresa']) ? $_SESSION['id_empresa'] : 'Não definido'));

$intervalo = 7;

// Obter o ID da empresa da sessão
if (!isset($_SESSION['id_empresa'])) {
    die("ID da empresa não está definido na sessão.");
}
$id_empresa = $_SESSION['id_empresa'];  

// Consulta para buscar os próximos aniversários
$sql_aniversarios = "
    SELECT id_fun, nome, foto, data_nascimento,
           DAY(data_nascimento) as dia,
           MONTH(data_nascimento) as mes,
           -- Calcula a data do próximo aniversário (neste ano ou no próximo)
           CASE 
               WHEN DATE_FORMAT(data_nascimento, '%m-%d') >= DATE_FORMAT(CURRENT_DATE, '%m-%d')
               THEN DATE_FORMAT(data_nascimento, '%Y-%m-%d') + INTERVAL (YEAR(CURRENT_DATE) - YEAR(data_nascimento)) YEAR
               ELSE DATE_FORMAT(data_nascimento, '%Y-%m-%d') + INTERVAL (YEAR(CURRENT_DATE) - YEAR(data_nascimento) + 1) YEAR
           END as proxima_data_aniversario
    FROM funcionario 
    WHERE empresa_id = ?
    ORDER BY proxima_data_aniversario ASC
    LIMIT 3";

$stmt_aniversarios = $conn->prepare($sql_aniversarios);
if (!$stmt_aniversarios) {
    die("Erro na preparação da consulta de aniversários: " . $conn->error);
}
$stmt_aniversarios->bind_param("i", $id_empresa);
$stmt_aniversarios->execute();
$result_aniversarios = $stmt_aniversarios->get_result();

$proximos_aniversarios = [];
while ($row = $result_aniversarios->fetch_assoc()) {
    // Formatar a data para exibição (dia/mês)
    $data_aniversario = new DateTime($row['proxima_data_aniversario']);
    $row['data_formatada'] = $data_aniversario->format('d/m');
    $proximos_aniversarios[] = $row;
}

$stmt_aniversarios->close();

// Consulta para buscar os feriados angolanos
$sql_feriados = "
    SELECT 
        id,
        data_feriado,
        nome_feriado,
        -- Calcula a data da próxima ocorrência do feriado (neste ano ou no próximo)
        CASE 
            WHEN DATE_FORMAT(data_feriado, '%m-%d') >= DATE_FORMAT(CURRENT_DATE, '%m-%d')
            THEN data_feriado + INTERVAL (YEAR(CURRENT_DATE) - YEAR(data_feriado)) YEAR
            ELSE data_feriado + INTERVAL (YEAR(CURRENT_DATE) - YEAR(data_feriado) + 1) YEAR
        END as proxima_data_feriado
    FROM feriados_angola
    ORDER BY proxima_data_feriado ASC
    LIMIT 3";

$stmt_feriados = $conn->prepare($sql_feriados);
if (!$stmt_feriados) {
    die("Erro na preparação da consulta de feriados: " . $conn->error);
}
$stmt_feriados->execute();
$result_feriados = $stmt_feriados->get_result();

$proximos_feriados = [];
while ($row = $result_feriados->fetch_assoc()) {
    // Formatar a data para exibição (dia/mês)
    $data_feriado = new DateTime($row['proxima_data_feriado']);
    $row['data_feriado_formatada'] = $data_feriado->format('d/m');
    $proximos_feriados[] = $row;
}

$stmt_feriados->close();

// Consulta para buscar todos os feriados do ano atual para o calendário
$sql_feriados_calendario = "
    SELECT 
        data_feriado,
        nome_feriado
    FROM feriados_angola
    WHERE YEAR(data_feriado) = YEAR(CURRENT_DATE)";

$stmt_feriados_calendario = $conn->prepare($sql_feriados_calendario);
if (!$stmt_feriados_calendario) {
    die("Erro na preparação da consulta de feriados do calendário: " . $conn->error);
}
$stmt_feriados_calendario->execute();
$result_feriados_calendario = $stmt_feriados_calendario->get_result();

$feriados_calendario = [];
while ($row = $result_feriados_calendario->fetch_assoc()) {
    $feriados_calendario[] = $row;
}

$stmt_feriados_calendario->close();

$sql_novos_funcionarios = "
    SELECT f.id_fun, f.nome, d.nome as departamento_nome, f.foto 
    FROM funcionario f
    LEFT JOIN departamentos d ON f.departamento = d.id
    WHERE f.data_admissao >= NOW() - INTERVAL ? DAY
    AND f.empresa_id = ?
    ORDER BY f.data_admissao DESC
    LIMIT 4";

$stmt_novos_funcionarios = $conn->prepare($sql_novos_funcionarios);
if (!$stmt_novos_funcionarios) {
    die("Erro na preparação da consulta: " . $conn->error);
}
$stmt_novos_funcionarios->bind_param("ii", $intervalo, $id_empresa);
$stmt_novos_funcionarios->execute();
$result_novos_funcionarios = $stmt_novos_funcionarios->get_result();

$novos_funcionarios = [];
while ($row = $result_novos_funcionarios->fetch_assoc()) {
    $novos_funcionarios[] = $row;
}

$stmt_novos_funcionarios->close();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <script src="../js/theme.js"></script>
    <title>Dashboard RH</title>
</head>
<style>

    
    .exit-tag {
    background-color: #FF6B6B;
    padding: 8px 16px;
    border-radius: 20px;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
}

.employee-item {
        cursor: pointer;
    }

 .add-button1{
    list-style: none;
}

.header-buttons {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px; 
    height: 50px;
    background: white;
    padding: 8px 15px; 
    border-radius: 25px;
    width: 32%;
}

.btn-enter {
    background-color: hwb(158 24% 29%);
    margin-left: 0;
    margin-right: auto;
}

.time-container {
    min-width: 120px;
    text-align: center;
    font-family: 'Poppins', sans-serif;
}



#current-time {
    display: inline-flex; 
    align-items: center;
    justify-content: center;
    height: 34px; 
    padding: 0 16px;
    border-radius: 20px;
    font-size: 15px;
    font-weight: 500;
    background: white;
    color: #000;
    white-space: nowrap;
}

/* Dark Mode Styles */
body.dark {
    background-color:#1E1E1E;
    color: #ffffff;

}

body.dark .card {
    background-color:rgb(26, 26, 26);
    box-shadow: 0 2px 4px rgba(255, 255, 255, 0.1);
}


body.dark .sidebar {
    background-color: #121212;
}

body.dark .header-buttons {
    background-color: #2C2C2C;
}

body.dark #current-time {
    background-color: #2C2C2C;
    color: #ffffff;
}

body.dark .welcome-card {
    background-color: #3EB489;
}

body.dark .employee-item {
    background-color: #2C2C2C;
}

body.dark .employee-name {
    color: #ffffff;
}

body.dark .employee-sector {
    color: #aaaaaa;
}

body.dark .status-label {
    color: #aaaaaa;
}

body.dark .birthday-name {
    color: #aaaaaa;
}

.calendar-day.feriado {
    background-color: #ffebee;
    color: #d32f2f;
    font-weight: bold;
}

/* Adicionado para formatar os itens de feriado */
.holiday-item {
    display: flex;
    align-items: flex-start; /* Alinha o topo dos elementos */
    margin-bottom: 10px;
}

.holiday-date {
    flex-shrink: 0; /* Impede que a data diminua */
    width: 60px; /* Largura fixa para a data */
    font-weight: bold;
    margin-right: 10px;
}

.holiday1 {
    flex-grow: 1; /* Permite que o nome ocupe o espaço restante */
    word-break: break-word; /* Permite quebra de palavra se necessário */
}

</style>
<body>
    <div class="sidebar">
        <div class="logo">
        </div>
    </div>
    
    <div class="main-content">
        <div class="header">
            <div></div>
            <div class="header-buttons">
    <div class="time-container">
        <div class="time" id="current-time"></div>
    </div>
    <a href="registro.php">
        <button class="btn btn-enter">Gerenciar</button>
    </a>
    <a class="exit-tag" href="logout.php">Sair</a>
    <a href="configuracoes_sam/perfil_adm.php" class="settings-icon" style="margin-top: 7.6px; margin-left:-7px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#3EB489" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
        </svg>
    </a>
</div>
        </div>

        <div class="dashboard-grid">
            <div>
                <div class="welcome-card">
                    <div class="welcome-title">Gestor</div>
                    <div class="welcome-text">Olá, <span><?php echo $_SESSION['nome']; ?></span>. Bem-vind@ de volta</div>
                </div>

                <div class="card status-section">
                    <div class="section-header">
                        <img src="icones/icons-sam-17.svg" alt="" class="who-is-icon">
                        <h2 class="status-title">Quem está...</h2>
                    </div>
                    
                    <div class="status-group">
                        <div class="status-label">A trabalhar</div>
                        <div class="avatar-group">
                            <img src="icones/icons-sam-18.svg" alt="" class="avatar">
                            <img src="icones/icons-sam-19.svg" alt="" class="avatar">
                            <img src="icones/icons-sam-18.svg" alt="" class="avatar">
                            <img src="icones/icons-sam-19.svg" alt="" class="avatar">
                            <img src="icones/icons-sam-18.svg" alt="" class="avatar">
                            <img src="icones/icons-sam-19.svg" alt="" class="avatar">
                            <img src="icones/icons-sam-18.svg" alt="" class="avatar">
                            <img src="icones/icons-sam-19.svg" alt="" class="avatar">
                            <img src="icones/icons-sam-18.svg" alt="" class="avatar">
                            <div class="avatar">+63</div>
                        </div>
                    </div>

                    <div class="status-group">
                        <div class="status-label">Descanso</div>
                        <div class="avatar-group">
                            <img src="icones/icons-sam-18.svg" alt="" class="avatar">
                            <img src="icones/icons-sam-19.svg" alt="" class="avatar">
                            <img src="icones/icons-sam-18.svg" alt="" class="avatar">

                        </div>
                    </div>

                    <div class="status-group">
                        <div class="status-label">Férias</div>
                        <div class="avatar-group">
                            <img src="icones/icons-sam-18.svg" alt="" class="avatar">
                            <img src="icones/icons-sam-19.svg" alt="" class="avatar">
                            <img src="icones/icons-sam-18.svg" alt="" class="avatar">
                            <img src="icones/icons-sam-19.svg" alt="" class="avatar">
                            <img src="icones/icons-sam-18.svg" alt="" class="avatar">
                        </div>

                    </div>

                    <div class="status-group">
                        <div class="status-label">Faltas</div>
                        <div class="avatar-group">
                            <div class="avatar">N/D</div>
                        </div>
                    </div>

                    <div class="status-group">
                        <div class="status-label">Todos</div>
                        <div class="avatar-group">
                            <img src="icones/icons-sam-18.svg" alt="" class="avatar">
                            <img src="icones/icons-sam-19.svg" alt="" class="avatar">
                            <img src="icones/icons-sam-18.svg" alt="" class="avatar">
                            <img src="icones/icons-sam-19.svg" alt="" class="avatar">
                            <img src="icones/icons-sam-18.svg" alt="" class="avatar">
                        </div>
                        
                    </div>

                </div>
            </div>

            <div>
                <div class="card">
                    <div class="section-header">
                        <img src="icones/icons-sam-15.svg" alt="" class="section-icon">
                        <h2>Próximos aniversários</h2>
                    </div>

                    <div class="birthdays-container">
                        <?php if (!empty($proximos_aniversarios)): ?>
                            <?php foreach ($proximos_aniversarios as $aniversariante): ?>
                                <div class="birthday-item">
                                    <div class="birthday-date"><?php echo $aniversariante['data_formatada']; ?></div>
                                    <?php
                                    $foto = !empty($aniversariante['foto']) && file_exists($aniversariante['foto']) ? $aniversariante['foto'] : 'icones/icons-sam-18.svg';
                                    ?>
                                    <img src="<?php echo $foto; ?>" alt="" class="birthday-avatar">
                                    <div class="birthday-name"><?php echo htmlspecialchars($aniversariante['nome']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="birthday-item">
                                <div class="birthday-name">Nenhum aniversário próximo encontrado.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card" style="margin-top: 20px;">
                    <div class="section-header">
                        <img src="icones/icons-sam-16.svg" alt="" class="section-icon">
                        <h2>Próximos Feriados</h2>
                    </div>

                    <?php if (!empty($proximos_feriados)): ?>
                        <?php foreach ($proximos_feriados as $feriado): ?>
                            <div class="holiday-item">
                                <div class="holiday-date"><?php echo $feriado['data_feriado_formatada']; ?></div>
                                <div class="holiday1"><?php echo htmlspecialchars($feriado['nome_feriado']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="holiday-item">
                            <div class="holiday1">Nenhum feriado próximo encontrado.</div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card" style="margin-top: 20px; min-height: 22.8%;">
                </div>
            </div>

            <div>
                <div class="card calendar">
                    <div class="calendar-header">
                        <span id="prevMonth">&lt;</span>
                        <span id="currentMonth"></span>
                        <span id="nextMonth">&gt;</span>
                    </div>

                    <div class="calendar-grid calendar-weekdays">
                        <div>D</div>
                        <div>S</div>
                        <div>T</div>
                        <div>Q</div>
                        <div>Q</div>
                        <div>S</div>
                        <div>S</div>
                    </div>

                    <div class="calendar-grid" id="calendar-days">
                    </div>
                </div>

                <div class="card new-employees">
                    <div class="new-employees-header">
                        <div class="new-employees-header1">Novos Empregados</div>
                        <div class="add-button"><a href="registro.php" class="add-button1">+ Adicionar</a></div>
                    </div>

                    <?php if (!empty($novos_funcionarios)): ?>
                        <?php foreach ($novos_funcionarios as $funcionario): ?>
                            <div class="employee-item" onclick="window.location.href='detalhes_funcionario.php?id=<?php echo $funcionario['id_fun']; ?>'">
                                <div class="employee-avatar">
                                    <?php
                                    // Verifica se a foto existe
                                    $foto = !empty($funcionario['foto']) && file_exists($funcionario['foto']) ? $funcionario['foto'] : 'icones/icons-sam-18.svg';
                                    ?>
                                    <img src="<?php echo $foto; ?>" alt="Foto de <?php echo htmlspecialchars($funcionario['nome']); ?>">
                                </div>
                                <div class="employee-info">
                                    <div class="employee-name"><?php echo htmlspecialchars($funcionario['nome']); ?></div>
                                    <div class="employee-sector"><?php echo htmlspecialchars($funcionario['departamento_nome']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="employee-item">
                            <div class="employee-info">
                                <div class="employee-name">Nenhum novo funcionário admitido recentemente.</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
        function updateTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('current-time').textContent = `${hours}:${minutes}:${seconds}`;
        }
        updateTime();

        setInterval(updateTime, 1000);
    </script>
    <script src="./js/UI.js"></script>
    <script src="./js/theme.js"></script>
    <script>
        // Array com os feriados do ano atual
        const feriados = <?php echo json_encode($feriados_calendario); ?>;
        
        // Função para verificar se uma data é feriado
        function isFeriado(date) {
            const dateStr = date.toISOString().split('T')[0];
            return feriados.some(feriado => feriado.data_feriado === dateStr);
        }

        // Função para formatar o nome do mês em português
        function getNomeMes(mes) {
            const meses = [
                'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
            ];
            return meses[mes];
        }

        // Função para atualizar o calendário
        function updateCalendar(year, month) {
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const daysInMonth = lastDay.getDate();
            const startingDay = firstDay.getDay();

            // Atualizar o título do mês
            document.getElementById('currentMonth').textContent = 
                `${getNomeMes(month)} ${year}`;

            const calendarDays = document.getElementById('calendar-days');
            calendarDays.innerHTML = '';

            // Adicionar dias vazios no início
            for (let i = 0; i < startingDay; i++) {
                const emptyDay = document.createElement('div');
                calendarDays.appendChild(emptyDay);
            }

            // Adicionar os dias do mês
            for (let day = 1; day <= daysInMonth; day++) {
                const dayElement = document.createElement('div');
                dayElement.textContent = day;
                
                const currentDate = new Date(year, month, day);
                if (isFeriado(currentDate)) {
                    dayElement.classList.add('feriado');
                }
                
                calendarDays.appendChild(dayElement);
            }
        }

        // Inicializar o calendário com o mês atual
        const currentDate = new Date();
        updateCalendar(currentDate.getFullYear(), currentDate.getMonth());

        // Adicionar eventos para os botões de navegação
        document.getElementById('prevMonth').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            updateCalendar(currentDate.getFullYear(), currentDate.getMonth());
        });

        document.getElementById('nextMonth').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            updateCalendar(currentDate.getFullYear(), currentDate.getMonth());
        });
    </script>
</body>
</html>

<style>
    * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background-color: #f5f5f5;
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 80px;
    background: white;
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.logo {
    width: 60px;
    height: 60px;
    background: url('sam2-05.png') no-repeat center center;
    background-size: contain;
    border-radius: 8px;
}

.main-content {
    flex: 1;
    padding: 10px 35px;
    max-width: 1250px;
    margin: 0 auto;
}

.header {
    display: flex;
    justify-content: end;
    align-items: center;
    margin-bottom: 10px;
    padding: 10px;
    width: 100%;
    margin-left: 1%;
    
}

.header-buttons {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px; 
    height: 50px;
    background: white;
    padding: 8px 15px; 
    border-radius: 25px;
    width: 32%;
}

.time {
    background-color: white;
    color: rgb(0, 0, 0);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px; 
    font-weight: bold;
    font-size: 15px;
}

.btn {
    padding: 8px 16px;
    border-radius: 20px;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 14px;
}

.btn-enter {
    background-color: hwb(158 24% 29%);
    margin-left: 0;
    margin-right: auto;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 40px;

}

.card {
    background: white;
    border-radius: 25px;
    padding: 25px;
    box-shadow: 0 2px 4px rgba(50, 206, 146, 0.1);
}

.welcome-card {
    position: relative;
    height: 150px; 
    background-color: #3EB489;
    padding: 25px;
    border-radius: 20px;
    overflow: hidden;
    background-size: 50% auto;
    background-position: center; 
    
}

.welcome-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('sam2-04.png') no-repeat center;
    background-size: cover;
    opacity: 0.7; 
    z-index: 0;
}
.welcome-title {
    color: #f5f5f5;
    font-size: 18px;
    margin-bottom: 10px;
}

.welcome-text {
    color: #f5f5f5;
    font-size: 24px;
    font-weight: bold;
}

.card.status-section {
    height: 74.5%; 
    overflow: hidden; 
}


.status-section {
    margin-top: 20px;
}

.section-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.section-header h2{
    font-size: 20px;
}

.who-is-icon {
    margin-left: -10px;
    width: 55px;
    height: 55px;
    margin-right: 1px;
}

.status-title {
    font-size: 21px;
}

.status-group {
    margin-bottom: 20px;
}

.status-label {
    color: #666;
    margin-bottom: 5px;
    font-size: 12px;
}

.avatar-group {
    display: flex;
    margin-left: 10px;
}

.avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: -10px;
    border: 2px solid white;
    font-size: 16px;
}

.avatar:nth-child(odd) {
    background-color: #3EB489;
    color: white;
}

.avatar:nth-child(even) {
    background-color: white;
    color: #3EB489;
    border: 2px solid #3EB489;
}

.birthdays-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: -10px;
}

.birthday-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.birthday-date {
    margin-bottom: 10px;
    color: #666;
    font-weight: bold;
}

.birthday-avatar {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
    font-weight: bold;
    background-color: #3EB489;
}

.birthday-avatar:nth-child(odd) {
    background-color: #3EB489;
    color: white;
}

.birthday-avatar:nth-child(even) {
    background-color: #3EB489;
    color: #3EB489;
    border: 2px solid #3EB489;
}

.birthday-name {
    font-size: 12px;
    color: #666;
}

.section-icon {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 8px;
}

.holiday-item {
    display: flex;
    margin-bottom: 8px;
}

.holiday-date {
    color: #FF6B6B;
    font-weight: bold;
    margin-right: 15px;
    min-width: 45px;
}

.holiday1{
    font-size: 12.5px;
    color: #666;
    font: bold;
}

.calendar {
    padding: 20px;
}

.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
    color: #3EB489;
    font-weight: bold;
}

.calendar-header span {
    cursor: pointer;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
    text-align: center;
    margin-bottom: 10px;
}

.calendar-weekdays {
    font-size: 14px;
    color: #666;
}

.calendar-day {
    padding: 8px;
    border-radius: 8px;
    font-size: 14px;
}

.calendar-day.active {
    background: #3EB489;
    color: white;
}

.calendar-day.weekend {
    background: #2a9c6f;
    color: white;
}

.calendar-day.empty {
    background: transparent;
}

.new-employees {
    margin-top: 20px;
    height: 360px;
    overflow-y: auto;
}

.new-employees::-webkit-scrollbar {
    width: 6px;
}


.new-employees::-webkit-scrollbar-thumb {
    background-color: #aaa;
    border-radius: 10px;  
}

.new-employees::-webkit-scrollbar-track {
    background-color: #f0f0f0;
    border-radius: 10px;  
}

.new-employees-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    font-size: 13.5px;

}

.new-employees-header1{
    color: #666;
    font: bold;
    
}

.add-button1 {
    text-decoration: none;
    color: #3EB489;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    font-size: 13.5px;
    

}

.employee-item {
    display: flex;
    align-items: center;
    padding: 10px;
    background: #f5f5f5;
    border-radius: 10px;
    margin-bottom: 10px;
    flex-shrink: 0;
    width: 100%;
    box-sizing: border-box;
}

.employee-avatar {
    width: 35px;
    height: 35px;
    background: #3EB489;
    border-radius: 50%;
    margin-right: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
    overflow: hidden;
    flex-shrink: 0;
    position: relative;
}

.employee-avatar img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    position: absolute;
    top: 0;
    left: 0;
}

.employee-info {
    flex: 1;
}

.employee-name {
    font-weight: bold;
}

.employee-sector {
    color: #666;
    font-size: 11px;
}

@media (max-width: 1200px) {
    .dashboard-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .main-content {
        padding: 10px 20px;
    }
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr; /* Uma coluna em telas menores */
    }

    .sidebar {
        width: 60px; /* Reduzir a largura da sidebar */
    }

    .header-buttons {
        width: 100%; /* Ajustar a largura dos botões no cabeçalho */
    }

    .btn {
        padding: 6px 12px; /* Ajustar o padding dos botões */
    }

    .welcome-card {
        height: 120px; /* Ajustar a altura do cartão de boas-vindas */
    }

    .calendar-header {
        flex-direction: column; /* Colocar os elementos em coluna */
        align-items: flex-start; /* Alinhar à esquerda */
    }

    .calendar-header span {
        margin-bottom: 5px; /* Espaçamento entre os elementos */
    }
}

@media (max-width: 480px) {
    .sidebar {
        display: none; /* Ocultar a sidebar em telas muito pequenas */
    }

    .main-content {
        padding: 10px; /* Reduzir o padding do conteúdo principal */
    }

    .header {
        flex-direction: column; /* Colocar os elementos em coluna */
        align-items: flex-start; /* Alinhar à esquerda */
    }

    .header-buttons {
        width: 100%; /* Ajustar a largura dos botões no cabeçalho */
        justify-content: space-between; /* Espaçar os botões */
    }

    .welcome-card {
        height: 100px; /* Ajustar a altura do cartão de boas-vindas */
    }

    .employee-item {
        flex-direction: column; /* Colocar os elementos em coluna */
        align-items: flex-start; /* Alinhar à esquerda */
    }

    .employee-avatar {
        margin-bottom: 5px; /* Espaçamento entre a imagem e o texto */
    }
}
</style>