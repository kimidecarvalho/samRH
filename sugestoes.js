document.getElementById('search-input').addEventListener('input', function() {
    const query = this.value;

    if (query.length > 0) {
        fetch(`sugestoes.php?query=${query}`)
            .then(response => response.json())
            .then(data => {
                const suggestionsBox = document.getElementById('suggestions');
                suggestionsBox.innerHTML = ''; // Limpar sugestões anteriores
                suggestionsBox.style.display = 'block'; // Mostrar a caixa de sugestões

                data.forEach(suggestion => {
                    const div = document.createElement('div');
                    div.classList.add('suggestion-item');
                    div.textContent = suggestion;
                    div.onclick = function() {
                        document.getElementById('search-input').value = suggestion; // Preencher o campo de pesquisa
                        suggestionsBox.style.display = 'none'; // Esconder sugestões
                    };
                    suggestionsBox.appendChild(div);
                });

                // Se não houver sugestões, esconder a caixa
                if (data.length === 0) {
                    suggestionsBox.style.display = 'none';
                }
            });
    } else {
        document.getElementById('suggestions').style.display = 'none'; // Esconder se o campo estiver vazio
    }
});