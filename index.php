<?php
session_start();

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    // Spróbuj przywrócić sesję z cookie
    if (isset($_COOKIE['shop_bilans_user'])) {
        require_once 'config.php';
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT id, username FROM users WHERE id = ?");
        $stmt->execute([$_COOKIE['shop_bilans_user']]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
        } else {
            header('Location: login.php');
            exit;
        }
    } else {
        header('Location: login.php');
        exit;
    }
}

$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Bilans">
    <title>Rozliczenie Zakupów</title>
    <link rel="stylesheet" href="style.css">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icon-192.png">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="user-info">
                <span class="username">👤 <?php echo htmlspecialchars($username); ?></span>
                <a href="logout.php" class="logout-btn">Wyloguj</a>
            </div>
            <div class="total-amount" id="totalAmount">0.00 zł</div>
        </div>

        <div class="main-content">
            <!-- Widok dodawania -->
            <div class="add-view" id="addView">
                <div class="input-display">
                    <input type="text" id="amountDisplay" value="0" readonly>
                </div>

                <div class="description-input">
                    <input type="text" id="descriptionInput" placeholder="Opis (np. Zakupy)">
                </div>

                <div class="keyboard">
                    <button class="key" data-value="7">7</button>
                    <button class="key" data-value="8">8</button>
                    <button class="key" data-value="9">9</button>
                    <button class="key" data-value="4">4</button>
                    <button class="key" data-value="5">5</button>
                    <button class="key" data-value="6">6</button>
                    <button class="key" data-value="1">1</button>
                    <button class="key" data-value="2">2</button>
                    <button class="key" data-value="3">3</button>
                    <button class="key" data-value="0">0</button>
                    <button class="key special" data-value=".">.</button>
                    <button class="key delete" data-action="backspace">⌫</button>
                    <button class="key delete" data-action="clear" style="grid-column: span 3;">Wyczyść</button>
                </div>

                <button class="add-btn" id="addEntryBtn">Dodaj</button>
            </div>

            <!-- Widok listy -->
            <div class="list-view hidden" id="listView">
                <div id="entriesList" class="entries-list">
                    <div class="empty-state">
                        <h3>Brak wpisów</h3>
                        <p>Dodaj pierwszy wpis</p>
                    </div>
                </div>
            </div>

            <!-- Przyciski nawigacji na dole -->
            <div class="view-toggle">
                <button class="btn btn-primary" id="showAddView">Dodaj wpis</button>
                <button class="btn btn-secondary" id="showListView">Pokaż rachunek</button>
            </div>
        </div>
    </div>

    <script>
        const currentUser = '<?php echo htmlspecialchars($username); ?>';
        let currentAmount = '0';
        let entries = [];

        // Elementy DOM
        const amountDisplay = document.getElementById('amountDisplay');
        const descriptionInput = document.getElementById('descriptionInput');
        const totalAmount = document.getElementById('totalAmount');
        const addView = document.getElementById('addView');
        const listView = document.getElementById('listView');
        const entriesList = document.getElementById('entriesList');
        const showAddViewBtn = document.getElementById('showAddView');
        const showListViewBtn = document.getElementById('showListView');
        const addEntryBtn = document.getElementById('addEntryBtn');

        // Klawiatura
        document.querySelectorAll('.key').forEach(key => {
            key.addEventListener('click', () => {
                const value = key.getAttribute('data-value');
                const action = key.getAttribute('data-action');

                if (value) {
                    handleKeyPress(value);
                } else if (action) {
                    handleAction(action);
                }
            });
        });

        function handleKeyPress(value) {
            if (currentAmount === '0') {
                currentAmount = value;
            } else {
                // Zapobiegaj wielokrotnym kropkom
                if (value === '.' && currentAmount.includes('.')) {
                    return;
                }
                currentAmount += value;
            }
            updateDisplay();
        }

        function handleAction(action) {
            switch(action) {
                case 'backspace':
                    if (currentAmount.length > 1) {
                        currentAmount = currentAmount.slice(0, -1);
                    } else {
                        currentAmount = '0';
                    }
                    break;
                case 'clear':
                    currentAmount = '0';
                    break;
            }
            updateDisplay();
        }

        function updateDisplay() {
            amountDisplay.value = currentAmount;
        }

        // Przełączanie widoków
        showAddViewBtn.addEventListener('click', () => {
            addView.classList.remove('hidden');
            listView.classList.add('hidden');
            showAddViewBtn.classList.remove('btn-secondary');
            showAddViewBtn.classList.add('btn-primary');
            showListViewBtn.classList.remove('btn-primary');
            showListViewBtn.classList.add('btn-secondary');
        });

        showListViewBtn.addEventListener('click', () => {
            console.log('Przełączanie na widok listy');
            addView.classList.add('hidden');
            listView.classList.remove('hidden');
            showListViewBtn.classList.remove('btn-secondary');
            showListViewBtn.classList.add('btn-primary');
            showAddViewBtn.classList.remove('btn-primary');
            showAddViewBtn.classList.add('btn-secondary');
            console.log('Widok listy visible:', !listView.classList.contains('hidden'));
            console.log('entriesList element:', entriesList);
            loadEntries();
        });

        // Dodawanie wpisu
        addEntryBtn.addEventListener('click', async () => {
            const amount = parseFloat(currentAmount);
            const description = descriptionInput.value.trim() || '';

            if (amount === 0) {
                alert('Kwota musi być różna od 0');
                return;
            }

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        amount: amount,
                        description: description
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Reset formularza
                    currentAmount = '0';
                    updateDisplay();
                    descriptionInput.value = '';
                    
                    // Odśwież sumę
                    loadEntries();
                    
                    alert('Wpis dodany pomyślnie!');
                } else {
                    alert('Błąd: ' + result.message);
                }
            } catch (error) {
                console.error('Błąd:', error);
                alert('Wystąpił błąd podczas dodawania wpisu');
            }
        });

        // Ładowanie wpisów
        async function loadEntries() {
            try {
                const response = await fetch('api.php');
                const result = await response.json();
                console.log('API Response:', result);

                if (result.redirect) {
                    window.location.href = result.redirect;
                    return;
                }

                if (result.success) {
                    entries = result.data;
                    console.log('Entries before conversion:', entries);
                    // Konwertuj amount na number
                    entries.forEach(entry => {
                        entry.amount = parseFloat(entry.amount);
                    });
                    console.log('Entries after conversion:', entries);
                    renderEntries();
                    updateTotal();
                } else {
                    console.error('Błąd API:', result.message);
                }
            } catch (error) {
                console.error('Błąd:', error);
            }
        }

        function renderEntries() {
            console.log('renderEntries called, entries count:', entries.length);
            if (entries.length === 0) {
                entriesList.innerHTML = `
                    <div class="empty-state">
                        <h3>Brak wpisów</h3>
                        <p>Dodaj pierwszy wpis klikając "Dodaj wpis"</p>
                    </div>
                `;
                return;
            }

            const html = entries.map(entry => {
                const amount = parseFloat(entry.amount);
                // Dla autora wpisu kwota jest dodatnia (wydał), dla innych ujemna (są dłużni)
                const isAuthor = entry.username === currentUser;
                const displayAmount = isAuthor ? amount : -amount;
                const canDelete = isAuthor;
                
                return `
                <div class="entry-item">
                    <div class="entry-info">
                        <div class="entry-description">${entry.description}</div>
                        <div class="entry-meta">
                            <span class="entry-author">${isAuthor ? '🙋 Ty' : '👤 ' + entry.username}</span>
                            <span class="entry-date">${entry.timestamp}</span>
                        </div>
                    </div>
                    <div class="entry-amount ${displayAmount >= 0 ? 'positive' : 'negative'}">
                        ${displayAmount >= 0 ? '+' : ''}${displayAmount.toFixed(2)} zł
                    </div>
                    ${canDelete ? `<button class="delete-entry-btn" onclick="deleteEntry('${entry.id}')">Usuń</button>` : '<div style="width: 60px;"></div>'}
                </div>
                `;
            }).join('');
            console.log('Generated HTML:', html);
            entriesList.innerHTML = html;
        }

        function updateTotal() {
            // Suma dla użytkownika: co wydał minus co wydali inni
            const total = entries.reduce((sum, entry) => {
                const amount = parseFloat(entry.amount);
                const isAuthor = entry.username === currentUser;
                // Jeśli autor: +amount (wydał), jeśli nie: -amount (jest dłużny)
                return sum + (isAuthor ? amount : -amount);
            }, 0);
            totalAmount.textContent = total.toFixed(2) + ' zł';
            // Dodatnia suma = użytkownik ma kredyt (inni są mu dłużni)
            // Ujemna suma = użytkownik ma debet (jest dłużny)
            totalAmount.style.color = total >= 0 ? '#10b981' : '#ef4444';
        }

        async function deleteEntry(id) {
            if (!confirm('Czy na pewno chcesz usunąć ten wpis?')) {
                return;
            }

            try {
                const response = await fetch('api.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: id })
                });

                const result = await response.json();

                if (result.success) {
                    loadEntries();
                } else {
                    alert('Błąd: ' + result.message);
                }
            } catch (error) {
                console.error('Błąd:', error);
                alert('Wystąpił błąd podczas usuwania wpisu');
            }
        }

        // Załaduj wpisy przy starcie
        loadEntries();

        // Rejestracja Service Workera dla PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then((registration) => {
                        console.log('Service Worker zarejestrowany:', registration);
                    })
                    .catch((error) => {
                        console.log('Błąd rejestracji Service Workera:', error);
                    });
            });
        }
    </script>
</body>
</html>
