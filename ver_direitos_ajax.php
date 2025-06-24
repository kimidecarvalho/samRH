<?php
require_once 'direitos_ausencias.php';

$tipo = $_POST['tipo'] ?? '';
$dias_uteis = (int)($_POST['dias_uteis'] ?? 0);
$salario_base = (float)($_POST['salario_base'] ?? 0);
$subsidiados = isset($_POST['subsidiados']) ? explode(',', $_POST['subsidiados']) : [];
$mes_doenca = (int)($_POST['mes_doenca'] ?? 1);
$justificada = ($_POST['justificada'] ?? 'true') === 'true';
$promovida_empresa = ($_POST['promovida_empresa'] ?? 'true') === 'true';

$direitos = calcularDireitosAusencia($tipo, $dias_uteis, $salario_base, $subsidiados, $mes_doenca, $justificada, $promovida_empresa);

$html = '';
$html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">';
$html .= '<h3 style="margin-top: 0; color: #333;">' . htmlspecialchars($direitos['direito']) . '</h3>';

// Informações principais
$html .= '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">';
$html .= '<div><strong>Remuneração:</strong> ' . number_format($direitos['remuneracao'], 2, ',', '.') . ' Kz</div>';
$html .= '<div><strong>Subsídios:</strong> ' . (empty($direitos['subsidiados']) ? 'Nenhum' : htmlspecialchars(implode(', ', $direitos['subsidiados']))) . '</div>';
$html .= '</div>';

// Observações
if (!empty($direitos['observacoes'])) {
    $html .= '<div style="margin-bottom: 15px;">';
    $html .= '<strong>Observações:</strong><ul style="margin: 5px 0; padding-left: 20px;">';
    foreach ($direitos['observacoes'] as $obs) {
        $html .= '<li style="margin-bottom: 5px;">' . htmlspecialchars($obs) . '</li>';
    }
    $html .= '</ul></div>';
}

// Base legal
if (!empty($direitos['base_legal'])) {
    $html .= '<div style="margin-bottom: 15px;">';
    $html .= '<strong>Base Legal:</strong><ul style="margin: 5px 0; padding-left: 20px;">';
    foreach ($direitos['base_legal'] as $base) {
        $html .= '<li style="margin-bottom: 5px; font-style: italic; color: #666;">' . htmlspecialchars($base) . '</li>';
    }
    $html .= '</ul></div>';
}

$html .= '</div>';

echo $html; 