document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('senha');

    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
    });

    // Form submission
    const form = document.getElementById('empresaForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        // Aqui você pode adicionar a lógica de envio do formulário
    });
});