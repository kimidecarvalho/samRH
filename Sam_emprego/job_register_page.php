<?php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado como candidato
if (!isset($_SESSION['candidato_id'])) {
    // Redireciona para a página de login
    header("Location: login.php");
    exit();
}

// Verifica se o perfil do candidato já está completo
$candidato_id = $_SESSION['candidato_id'];
$stmt = $conn->prepare("SELECT perfil_completo FROM candidatos WHERE id = ?");
$stmt->bind_param("i", $candidato_id);
$stmt->execute();
$result = $stmt->get_result();
$candidato = $result->fetch_assoc();

// Se o perfil já estiver completo, redireciona para o painel
if ($candidato['perfil_completo'] == 1) {
    header("Location: painel_candidato.php");
    exit();
}

// Recupera erros, se existirem
$erros = $_SESSION['erros_registro_completo'] ?? [];
unset($_SESSION['erros_registro_completo']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" class="ico" href="sam2-05.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../all.css/emprego.css/emp_register.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <title>SAM Emprego - Completar Perfil</title>
    <style>
        .alert {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        /* Estilo para o select de prefixo */
        .phone-prefix {
            position: relative;
            min-width: 85px;
            max-width: 85px;
        }

        .prefix-select {
            background: transparent;
            border: none;
            color: inherit;
            font-size: 12px;
            padding: 0 25px 0 10px;
            margin: 0;
            width: 100%;
            cursor: pointer;
            outline: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        .prefix-select option {
            background: white;
            color: #333;
            font-size: 12px;
            padding: 8px;
        }

        .phone-prefix::after {
            content: "";
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 12px;
            height: 12px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='24' height='24' stroke='%232ca378' stroke-width='2' fill='none'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-size: contain;
            pointer-events: none;
        }

        .phone-sufix {
            flex: 1;
            margin-left: 5px;
        }

        .phone-input {
            text-align: center;
            width: 100%;
            padding: 4px 10px;
            border: 1px solid #ccc;
            border-radius: 25px;
            font-size: 12px;
        }

        /* Estilo atualizado para o layout 2x2 */
        .form-sections-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Estilo para cada seção do formulário */
        .form-section {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        @media (max-width: 768px) {
            .form-sections-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
                <img src="../fotos/sam30-13.png" alt="" class="logo">
            </div>
            <div class="welcome-text">
                Bem-vind@ à <span class="sam-emprego">SAM Emprego, </span>Complete o seu perfil para ter acesso ao sistema:
            </div>
            <div class="form-container">
                <?php if (!empty($erros)): ?>
                    <div class="alert alert-danger">
                        <ul style="margin: 0; padding-left: 20px;">
                            <?php foreach ($erros as $erro): ?>
                                <li><?php echo htmlspecialchars($erro); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <form id="completeProfileForm" action="processar_registro_completo.php" method="POST" enctype="multipart/form-data">
                <!-- Layout 2x2 para as seções do formulário -->
                <div class="form-sections-grid">
                    <!-- 1ª Seção: Informações Pessoais -->
                    <div class="form-section">
                        <h2 class="section-title">
                            Informações Pessoais*
                        </h2>
                        <div class="form-group">
                            <label>Nome Completo</label>
                            <input type="text" name="nome" class="form-control" placeholder="Kimi Amaro Teixeira Carvalho" required>
                        </div>
                        <div class="form-group">
                            <label>Data de Nascimento</label>
                            <div class="date-inputs">
                                <input type="text" class="date-input-date" id="dia" placeholder="dd" maxlength="2">
                                <input type="text" class="date-input-date" id="mes" placeholder="mm" maxlength="2">
                                <input type="text" class="date-input" id="ano" placeholder="aaaa" maxlength="4">
                                <input type="hidden" id="data_nascimento" name="data_nascimento">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Número de Telefone</label>
                            <div style="display: flex; flex: 1;">
                                <div class="phone-prefix">
                                    <select name="phone_prefix" id="phone_prefix" class="prefix-select">
                                        <option value="+244">+244 (Angola)</option>
                                        <option value="+55">+55 (Brasil)</option>
                                        <option value="+351">+351 (Portugal)</option>
                                        <option value="+258">+258 (Moçambique)</option>
                                        <option value="+238">+238 (Cabo Verde)</option>
                                        <option value="+245">+245 (Guiné-Bissau)</option>
                                        <option value="+239">+239 (São Tomé e Príncipe)</option>
                                        <option value="+240">+240 (Guiné Equatorial)</option>
                                        <option value="+241">+241 (Gabão)</option>
                                        <option value="+242">+242 (Congo)</option>
                                    </select>
                                </div>
                                <div class="phone-sufix">
                                    <input type="text" name="telefone" class="phone-input" placeholder="Ex: 923 456 789" pattern="[0-9]{9}" maxlength="9" required>   
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email Profissional</label>
                            <input type="text" class="form-control"  placeholder="exemplo@gmail.com">
                        </div>
                        <div class="form-group">
                            <label>Endereço</label>
                            <input type="text" name="endereco" class="form-control" placeholder="Vila E. Casa Nº4" required>
                        </div>
                    </div>

                    <!-- 2ª Seção: Currículo -->
                    <div class="form-section">
                        <h2 class="section-title">
                            Currículo(CV) / Carta de Apresentação*
                        </h2>
                        <div class="file-upload">
                            <input type="file" name="curriculo" id="curriculo" style="display: none;" accept=".pdf,.doc,.docx">
                            <button type="button" class="file-btn choose-file" onclick="document.getElementById('curriculo').click()">Escolher arquivos</button>
                            <span id="selected-file">Nenhum arquivo selecionado</span>
                        </div>
                    </div>

                    <!-- 3ª Seção: Informações Profissionais -->
                    <div class="form-section">
                        <h2 class="section-title">
                            Informações Profissionais
                        </h2>
                        <div class="form-group">
                            <label>Experiêcia</label>
                            <div class="select-wrapper">
                                <select class="form-control" name="experiencia">
                                    <option selected>1 ano</option>
                                    <option>2 anos</option>
                                    <option>3 anos</option>
                                    <option>4 anos</option>
                                    <option>5 anos</option>
                                    <option>6 anos</option>
                                    <option>7 anos</option>
                                    <option>8 anos</option>
                                    <option>9 anos</option>
                                    <option>+10 anos</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Formação Académica</label>
                            <div class="select-wrapper">
                                <select class="form-control" name="formacao">
                                    <option selected>Ensino Médio</option>
                                    <option>Ensino Superior</option>
                                    <option>Pós-Graduação</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Áreas de Atuação</label>
                            <input type="text" name="area_atuacao" class="form-control" placeholder="Computação">
                        </div>
                        <div class="form-group">
                            <label>Nota Extra</label>
                            <textarea name="nota_extra" class="form-control" placeholder="Curso médio ainda em curso, 13ª Classe, Informática."></textarea>
                        </div>
                    </div>

                    <!-- 4ª Seção: Competências -->
                    <div class="form-section">
                        <h2 class="section-title">Competências</h2>
                        <div class="form-row">
                            <div class="left-column">
                                <div class="competencias-label">
                                    Competências
                                    <br>
                                    <small>Escreva a competência e clique em "Enter".</small>
                                </div>
                                <div class="tag-container" id="tag-container">
                                    <!-- As tags serão adicionadas via JavaScript -->
                                </div>
                                <input type="hidden" name="habilidades" id="habilidades-input">
                            </div>
                            <div class="right-column">
                                <input type="text" id="new-tag" class="form-control" placeholder="Programação" onkeydown="if(event.key === 'Enter') { event.preventDefault(); addTag(); }">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="submit-container">
                    <button type="submit" class="submit-btn">Continuar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Lista de tags/habilidades
        let tags = [];
        
        // Função para adicionar uma tag
        function addTag() {
            const input = document.getElementById('new-tag');
            const tagText = input.value.trim();
            
            if (tagText && !tags.includes(tagText)) {
                tags.push(tagText);
                renderTags();
                input.value = '';
                
                // Atualiza o campo oculto com as habilidades
                document.getElementById('habilidades-input').value = tags.join(', ');
            }
        }
        
        // Função para remover uma tag
        function removeTag(index) {
            tags.splice(index, 1);
            renderTags();
            
            // Atualiza o campo oculto com as habilidades
            document.getElementById('habilidades-input').value = tags.join(', ');
        }
        
        // Função para renderizar as tags
        function renderTags() {
            const container = document.getElementById('tag-container');
            container.innerHTML = '';
            
            tags.forEach((tag, index) => {
                const tagElement = document.createElement('div');
                tagElement.className = 'tag';
                tagElement.innerHTML = `${tag} <span class="tag-remove" onclick="removeTag(${index})">×</span>`;
                container.appendChild(tagElement);
            });
        }
        
        // Função para formatar a data
        function formatarData() {
            const dia = document.getElementById('dia');
            const mes = document.getElementById('mes');
            const ano = document.getElementById('ano');
            
            // Move focus to next input when current is filled
            if (dia.value.length === 2) mes.focus();
            if (mes.value.length === 2) ano.focus();
        }
        
        // Set up quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar os eventos da data
            document.getElementById('dia').addEventListener('input', formatarData);
            document.getElementById('mes').addEventListener('input', formatarData);
            document.getElementById('ano').addEventListener('input', formatarData);
            
            // Configurar o evento de upload de arquivo
            document.getElementById('curriculo').addEventListener('change', function() {
                const fileName = this.files[0] ? this.files[0].name : 'Nenhum arquivo selecionado';
                document.getElementById('selected-file').textContent = fileName;
            });
            
            // Configurar evento de envio do formulário
            document.getElementById('completeProfileForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Formatar a data antes do envio
                const dia = document.getElementById('dia').value.padStart(2, '0');
                const mes = document.getElementById('mes').value.padStart(2, '0');
                const ano = document.getElementById('ano').value;
                
                // Formatar o número de telefone
                const telefoneInput = document.querySelector('input[name="telefone"]');
                const prefixSelect = document.getElementById('phone_prefix');
                if (telefoneInput.value) {
                    telefoneInput.value = prefixSelect.value + telefoneInput.value.replace(/\s/g, '');
                }
                
                if (dia && mes && ano && ano.length === 4) {
                    document.getElementById('data_nascimento').value = `${ano}-${mes}-${dia}`;
                    this.submit();
                } else {
                    alert('Por favor, preencha a data de nascimento corretamente.');
                }
            });
        });
    </script>
</body>
</html>