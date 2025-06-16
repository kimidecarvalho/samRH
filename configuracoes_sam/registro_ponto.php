    <!-- Modal para Exportar Relatório -->
    <div id="modal-exportar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modal-exportar').style.display='none'">&times;</span>
            <h2>Exportar Relatório</h2>
            <form id="formExportarRelatorio" onsubmit="return exportarRelatorioPDF(event)">
                <div class="form-group">
                    <label for="tipo_relatorio">Tipo de Relatório:</label>
                    <select name="tipo_relatorio" id="tipo_relatorio" onchange="toggleFuncionarioSelect()">
                        <option value="empresa">Relatório da Empresa</option>
                        <option value="funcionario">Relatório de Funcionário</option>
                    </select>
                </div>
                <div class="form-group" id="funcionario-select-group" style="display: none;">
                    <label for="funcionario_relatorio">Funcionário:</label>
                    <select name="funcionario_relatorio" id="funcionario_relatorio">
                        <option value="">Selecione um funcionário</option>
                        <?php
                        $sql_funcionarios = "SELECT id_fun, nome, num_mecanografico FROM funcionario 
                                           WHERE empresa_id = $empresa_id AND estado = 'Ativo'
                                           ORDER BY CAST(num_mecanografico AS UNSIGNED) ASC";
                        $result_funcionarios = mysqli_query($conn, $sql_funcionarios);
                        while ($funcionario = mysqli_fetch_assoc($result_funcionarios)) {
                            echo "<option value='{$funcionario['id_fun']}'>{$funcionario['nome']} (#{$funcionario['num_mecanografico']})</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="periodo_relatorio">Período:</label>
                    <select name="periodo_relatorio" id="periodo_relatorio">
                        <option value="semana">Última semana</option>
                        <option value="mes" selected>Último mês</option>
                        <option value="trimestre">Último trimestre</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="formato">Formato:</label>
                    <select name="formato" id="formato">
                        <option value="pdf">PDF</option>
                        <option value="csv">CSV</option>
                        <option value="excel">Excel</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-info" onclick="exportarRelatorioPDF(event, 'visualizar')">
                        <i class="fas fa-eye"></i> Visualizar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-exportar').style.display='none'">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

<script>
function exportarRelatorioPDF(event, acao) {
    event.preventDefault();
    var formato = document.getElementById('formato').value;
    var periodo = document.getElementById('periodo_relatorio').value;
    var tipoRelatorio = document.getElementById('tipo_relatorio').value;
    var funcionarioId = document.getElementById('funcionario_relatorio').value;

    if (formato === 'pdf') {
        let url = 'configuracoes_sam/generate_pdf_ponto.php?periodo=' + encodeURIComponent(periodo);
        if (tipoRelatorio === 'funcionario') {
            if (!funcionarioId) {
                alert('Por favor, selecione um funcionário.');
                return false;
            }
            url += '&tipo=funcionario&funcionario_id=' + encodeURIComponent(funcionarioId);
        } else {
            url += '&tipo=empresa';
        }
        
        if (acao === 'visualizar') {
            url += '&acao=visualizar';
        }
        
        window.open(url, '_blank');
        document.getElementById('modal-exportar').style.display='none';
        return false;
    }
    // Para outros formatos, pode mostrar alerta ou implementar depois
    alert('Exportação apenas em PDF está disponível no momento.');
    return false;
}
</script> 