<style>
    /* Sobrescrever estilos azuis do Bootstrap em botões primários focados/ativos */
    .btn-primary:focus,
    .btn-primary:active,
    .btn-primary.focus,
    .btn-primary.active {
        background-color: var(--primary-color) !important; /* Garante que o fundo seja verde */
        border-color: var(--primary-color) !important; /* Garante que a borda seja verde */
        color: #fff !important; /* Garante que o texto seja branco para contraste */
        box-shadow: 0 0 0 0.25rem rgba(62, 180, 137, 0.5) !important; /* Opcional: sombra verde ao focar */
    }

    /* Ajuste específico para o estado 'active' para dar feedback visual */
    .btn-primary:active,
    .btn-primary.active {
         background-color: #32a177 !important; /* Tom um pouco mais escuro ao clicar */
         border-color: #32a177 !important; /* Tom um pouco mais escuro ao clicar */
    }
</style>

<script>
    // Elementos do modal de Consentimento
</script> 