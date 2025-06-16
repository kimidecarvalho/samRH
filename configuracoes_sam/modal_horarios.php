<?php
// Verificar se o arquivo está sendo incluído
if (!defined('INCLUDED_FROM_RH_CONFIG')) {
    die('Acesso direto não permitido');
}
?>

<style>
#modalHorarios .table {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 0;
}
#modalHorarios th,
#modalHorarios td {
    text-align: center !important;
    vertical-align: middle;
}
#modalHorarios td {
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}
#modalHorarios tr:nth-child(even) td {
    background: #f1f7f6;
}
#modalHorarios input[type="time"] {
    width: 110px;
    border-radius: 6px;
    border: 1px solid #b2dfdb;
    padding: 4px 8px;
    background: #fff;
    color: #222;
    display: inline-block;
    margin: 0 auto;
    text-align: center;
}
#modalHorarios .btn-primary.btn-sm {
    background: #2b7a78;
    border: none;
    border-radius: 6px;
    padding: 5px 18px;
    font-weight: 500;
    font-size: 0.98em;
    transition: background 0.2s;
}
#modalHorarios .btn-primary.btn-sm:hover {
    background: #205c5a;
}
</style>

<!-- Modal de Horários -->
<div class="modal fade" id="modalHorarios" tabindex="-1" aria-labelledby="modalHorariosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" style="max-width: 1000px;">
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
                                <th>Matrícula</th>
                                <th>Departamento</th>
                                <th>Cargo</th>
                                <th>H. Entrada</th>
                                <th>H. Saída</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaHorarios">
                            <?php
                            $sql_funcionarios = "SELECT f.id_fun, f.nome, f.num_mecanografico, 
                                               d.nome as departamento_nome, c.nome as cargo_nome,
                                               h.hora_entrada, h.hora_saida 
                                               FROM funcionario f 
                                               LEFT JOIN horarios_funcionarios h ON f.id_fun = h.funcionario_id 
                                               LEFT JOIN departamentos d ON f.departamento = d.id
                                               LEFT JOIN cargos c ON f.cargo = c.id
                                               WHERE f.empresa_id = ? AND f.estado = 'Ativo'
                                               ORDER BY f.num_mecanografico ASC";
                            $stmt = $conn->prepare($sql_funcionarios);
                            $stmt->bind_param("i", $empresa_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            while ($row = $result->fetch_assoc()) {
                                $hora_entrada = $row['hora_entrada'] ?? '08:00';
                                $hora_saida = $row['hora_saida'] ?? '16:00';
                                echo "<tr>";
                                echo "<td>{$row['nome']}</td>";
                                echo "<td>{$row['num_mecanografico']}</td>";
                                echo "<td>{$row['departamento_nome']}</td>";
                                echo "<td>{$row['cargo_nome']}</td>";
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
</script> 