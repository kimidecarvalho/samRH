document.addEventListener("DOMContentLoaded", () => {
    function applyTheme(theme) {
        document.body.classList.remove("dark");

        const elementsToToggle = document.querySelectorAll(
            ".sidebar, .nav-select, .nav-menu li, .main-content, " +
            ".sistema-section, .sistema-details, .detail-item, .select-input, " +
            ".profile-card" 
        );

        if (theme === "dark") {
            document.body.classList.add("dark");
            elementsToToggle.forEach((element) => element.classList.add("dark"));
        } else {
            elementsToToggle.forEach((element) => element.classList.remove("dark"));
        }

        // Configuração do tema do sistema
        if (theme === "system") {
            const prefersDarkScheme = window.matchMedia("(prefers-color-scheme: dark)");
            applyTheme(prefersDarkScheme.matches ? "dark" : "light");

            prefersDarkScheme.addEventListener("change", (e) => {
                applyTheme(e.matches ? "dark" : "light");
            });
        }
    }

    function loadTheme() {
        const savedTheme = localStorage.getItem("theme") || "light";
        applyTheme(savedTheme);
        
        // Se existir um seletor de tema na página, definir o valor correto
        const themeSelector = document.getElementById("theme-selector");
        if (themeSelector) {
            themeSelector.value = savedTheme;
            themeSelector.addEventListener("change", (event) => {
                const selectedTheme = event.target.value;
                localStorage.setItem("theme", selectedTheme);
                applyTheme(selectedTheme);
            });
        }
    }

    // Carregar o tema ao iniciar a página
    loadTheme();
});
