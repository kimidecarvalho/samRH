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