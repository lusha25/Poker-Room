function revealCards(playerIndex) {
    const player = document.getElementById(`player-${playerIndex}`);
    const cards = player.querySelectorAll(".card");
    cards.forEach(card => {
        if (card.classList.contains("hidden")) {
            card.src = card.getAttribute("data-src");
            card.alt = card.getAttribute("data-alt");
            card.classList.remove("hidden");
        }
    });
    const revealButton = player.querySelector(".reveal-button");
    revealButton.disabled = true;
    revealButton.style.backgroundColor = "#424242";
    revealButton.style.cursor = "not-allowed";
    
    fetch(window.location.href, {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "reveal_player=" + playerIndex
    }).then(() => {
        window.location.reload();
    });
}

function revealTiebreakerCards(playerIndex, tiebreakerRound) {
    const player = document.getElementById(`tiebreaker-player-${playerIndex}-${tiebreakerRound}`);
    const cards = player.querySelectorAll(".card");
    cards.forEach(card => {
        if (card.classList.contains("hidden")) {
            card.src = card.getAttribute("data-src");
            card.alt = card.getAttribute("data-alt");
            card.classList.remove("hidden");
        }
    });
    const revealButton = player.querySelector(".reveal-button");
    revealButton.disabled = true;
    revealButton.style.backgroundColor = "#424242";
    revealButton.style.cursor = "not-allowed";
    
    fetch(window.location.href, {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "reveal_tiebreaker_player=" + playerIndex + "&tiebreaker_round=" + tiebreakerRound
    }).then(() => {
        window.location.reload();
    });
}

const userFields = document.getElementById('userFields');
const numUsersInput = document.querySelector('input[name="num_users"]');
const form = document.getElementById('gameForm');
const submitButton = document.getElementById('submitButton');
const rulesContainer = document.getElementById('rulesContainer');

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function generateUserFields(count) {
    userFields.innerHTML = '';
    for (let i = 1; i <= Math.min(count, 3); i++) {
        userFields.innerHTML += `
            <fieldset>
                <legend>Uporabnik ${i}</legend>
                <label>Ime:
                    <input type="text" name="ime${i}" required aria-label="Ime uporabnika ${i}">
                </label>
            </fieldset>`;
    }
    validateForm();
}

function validateForm() {
    const inputs = form.querySelectorAll('input[required]');
    let allValid = true;
    inputs.forEach(input => {
        if (!input.value.trim() || 
            (input.type === 'number' && (input.value < input.min || input.value > input.max))) {
            allValid = false;
        }
    });
    submitButton.disabled = !allValid;
}

function toggleRules() {
    rulesContainer.style.display = rulesContainer.style.display === 'block' ? 'none' : 'block';
}

const debouncedGenerateUserFields = debounce((val) => {
    if (val >= 1 && val <= 3) {
        generateUserFields(val);
    }
}, 300);

numUsersInput.addEventListener('input', (e) => {
    const val = parseInt(e.target.value);
    debouncedGenerateUserFields(val);
});

form.addEventListener('input', debounce(validateForm, 100));

form.addEventListener('submit', (e) => {
    if (submitButton.disabled) {
        e.preventDefault();
        return;
    }
    submitButton.disabled = true;
    submitButton.textContent = 'Obdelava...';
});

generateUserFields(3);