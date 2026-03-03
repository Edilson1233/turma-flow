// ============================================================
//  assets/js/app.js — JavaScript da TurmaApp
//  Interações de UI: toasts, confirmações, toggles
// ============================================================

// === Auto-desaparece mensagens flash após 5 segundos ===
document.addEventListener('DOMContentLoaded', function () {

    const flashes = document.querySelectorAll('.flash');
    flashes.forEach(function (el) {
        // Adiciona botão de fechar
        const btnFechar = document.createElement('button');
        btnFechar.innerHTML = '×';
        btnFechar.className = 'flash-fechar';
        btnFechar.onclick   = () => el.remove();
        el.appendChild(btnFechar);

        // Desaparece automaticamente em 6 segundos
        setTimeout(function () {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity    = '0';
            setTimeout(() => el.remove(), 500);
        }, 6000);
    });

    // === Toggle de formulários ===
    // Usado em materiais.php para mostrar/ocultar o formulário de adição
    window.toggleForm = function (id) {
        const el = document.getElementById(id);
        if (!el) return;
        if (el.style.display === 'none' || el.style.display === '') {
            el.style.display = 'block';
        } else {
            el.style.display = 'none';
        }
    };

    // === Código de convite: sempre maiúsculas ===
    document.querySelectorAll('input[name="codigo"]').forEach(function (input) {
        input.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });
    });

    // === Confirmar exclusões perigosas ===
    document.querySelectorAll('[data-confirmar]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const msg = this.dataset.confirmar || 'Tens a certeza?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // === Highlight da linha ativa no horário ao hover ===
    const aulaCards = document.querySelectorAll('.aula-card');
    aulaCards.forEach(card => {
        card.addEventListener('mouseenter', function () {
            this.style.transform = 'scale(1.03)';
        });
        card.addEventListener('mouseleave', function () {
            this.style.transform = '';
        });
    });

    // === Indica visualmente o dia de hoje no horário ===
    const hoje = new Date().getDay(); // 0=Dom, 1=Seg, 2=Ter, ...
    const mapDiaPHP = { 1: 1, 2: 2, 3: 3, 4: 4, 5: 5 }; // JS→PHP dia
    const thDias = document.querySelectorAll('.th-dia');

    if (thDias.length > 0 && hoje >= 1 && hoje <= 5) {
        // Índice da coluna hoje (hoje-1 porque índice 0 = Segunda)
        const idx = hoje - 1;
        if (thDias[idx]) {
            thDias[idx].classList.add('th-dia-hoje');
        }
    }
});
