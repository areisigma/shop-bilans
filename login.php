<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Bilans">
    <title>Logowanie - Rozliczenie Zakupów</title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icon-192.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(circle at 20% 20%, #1f2937 0%, #0b1220 45%, #050914 100%);
            color: #e5e7eb;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
        }

        .login-container {
            background: #0f172a;
            border: 1px solid #1f2937;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.45);
            padding: 40px;
            max-width: 400px;
            width: 100%;
        }

        @media (max-width: 768px) {
            .login-container {
                padding: 30px 20px;
                border-radius: 15px;
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #38bdf8;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #374151;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            font-size: 1rem;
            border: 1px solid #334155;
            border-radius: 10px;
            background: #111827;
            color: #e5e7eb;
            transition: border-color 0.3s ease;
        }

        @media (max-width: 768px) {
            .form-group input {
                font-size: 16px; /* Zapobiega zoom na iOS */
            }
        }

        .form-group input:focus {
            outline: none;
            border-color: #22d3ee;
            box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.2);
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-login {
            background: linear-gradient(135deg, #22d3ee 0%, #0ea5e9 100%);
            color: #0b1220;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #38e0fa 0%, #22c3f3 100%);
            transform: translateY(-2px);
        }

        .btn-register {
            background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
            color: #0b1220;
        }

        .btn-register:hover {
            background: linear-gradient(135deg, #6ee7b7 0%, #34d399 100%);
            transform: translateY(-2px);
        }

        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: none;
        }

        .message.success {
            background: rgba(52, 211, 153, 0.15);
            color: #a7f3d0;
            border: 1px solid rgba(52, 211, 153, 0.6);
        }

        .message.error {
            background: rgba(248, 113, 113, 0.15);
            color: #fecdd3;
            border: 1px solid rgba(248, 113, 113, 0.6);
        }

        .message.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🛒 Rozliczenie Zakupów</h1>
            <p>Zaloguj się lub utwórz nowe konto</p>
        </div>

        <div id="message" class="message"></div>

        <form id="authForm">
            <div class="form-group">
                <label for="username">Login</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Hasło</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>

            <div class="button-group">
                <button type="button" class="btn btn-login" id="loginBtn">Zaloguj</button>
                <button type="button" class="btn btn-register" id="registerBtn">Zarejestruj</button>
            </div>
        </form>
    </div>

    <script>
        const authForm = document.getElementById('authForm');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const loginBtn = document.getElementById('loginBtn');
        const registerBtn = document.getElementById('registerBtn');
        const messageDiv = document.getElementById('message');

        function showMessage(text, type) {
            messageDiv.textContent = text;
            messageDiv.className = `message ${type} show`;
            setTimeout(() => {
                messageDiv.classList.remove('show');
            }, 5000);
        }

        loginBtn.addEventListener('click', async () => {
            const username = usernameInput.value.trim();
            const password = passwordInput.value;

            if (!username || !password) {
                showMessage('Wypełnij wszystkie pola', 'error');
                return;
            }

            try {
                const response = await fetch('auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'login',
                        username: username,
                        password: password
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('Logowanie pomyślne! Przekierowywanie...', 'success');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Błąd:', error);
                showMessage('Wystąpił błąd podczas logowania', 'error');
            }
        });

        registerBtn.addEventListener('click', async () => {
            const username = usernameInput.value.trim();
            const password = passwordInput.value;

            if (!username || !password) {
                showMessage('Wypełnij wszystkie pola', 'error');
                return;
            }

            if (password.length < 4) {
                showMessage('Hasło musi mieć co najmniej 4 znaki', 'error');
                return;
            }

            try {
                const response = await fetch('auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'register',
                        username: username,
                        password: password
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('Rejestracja pomyślna! Możesz się teraz zalogować.', 'success');
                    passwordInput.value = '';
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Błąd:', error);
                showMessage('Wystąpił błąd podczas rejestracji', 'error');
            }
        });

        // Enter na logowanie
        authForm.addEventListener('submit', (e) => {
            e.preventDefault();
            loginBtn.click();
        });
    </script>
</body>
</html>
