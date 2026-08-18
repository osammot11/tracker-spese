<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Accesso Protetto - SpeseTracker</title>
    
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    @vite(["resources/css/app.css"])
    <style>
        .pin-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background-color: var(--bg-app);
        }
        .pin-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 400px;
            padding: 2.25rem 2rem;
            text-align: center;
        }
        .pin-icon-box {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-lg);
            background: var(--primary-subtle);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem auto;
            border: 1px solid var(--primary-border);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
        }
        .pin-input {
            width: 220px;
            height: 58px;
            font-size: 1.8rem;
            font-weight: 800;
            text-align: center;
            letter-spacing: 0.9rem;
            padding-left: 0.9rem;
            border-radius: var(--radius-lg);
            border: 2px solid var(--border-color);
            background-color: var(--bg-subtle);
            color: var(--text-main);
            margin: 1.25rem auto;
            display: block;
            transition: all var(--transition-fast);
        }
        .pin-input:focus {
            outline: none;
            border-color: var(--primary);
            background-color: #ffffff;
            box-shadow: 0 0 0 4px var(--primary-subtle);
        }
        .keypad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.65rem;
            max-width: 260px;
            margin: 1.25rem auto 0 auto;
        }
        .key-btn {
            height: 52px;
            border: 1px solid var(--border-color);
            background-color: var(--bg-card);
            border-radius: var(--radius-md);
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-main);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all var(--transition-fast);
            box-shadow: var(--shadow-xs);
        }
        .key-btn:hover {
            background-color: var(--bg-muted);
            border-color: var(--border-strong);
            transform: translateY(-1px);
        }
        .key-btn:active {
            transform: translateY(1px);
            background-color: var(--primary-subtle);
        }
        .key-btn.action {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="pin-wrapper">
        <div class="pin-card">
            <div class="pin-icon-box">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>

            <h1 style="font-size: 1.35rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.35rem; letter-spacing: -0.02em;">
                Accesso Protetto
            </h1>
            <p style="font-size: 0.88rem; color: var(--text-muted);">
                Inserisci il codice PIN a 4 cifre per accedere al tuo tracker spese
            </p>

            @if(session("success"))
                <div class="alert alert-success" style="margin-top: 1rem; margin-bottom: 0.5rem; text-align: left; padding: 0.65rem 0.9rem;">
                    <span>{{ session("success") }}</span>
                </div>
            @endif

            @if($errors->has("pin"))
                <div class="alert alert-danger" style="margin-top: 1rem; margin-bottom: 0.5rem; text-align: left; padding: 0.65rem 0.9rem;">
                    <span>{{ $errors->first("pin") }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route("pin.verify") }}" id="pinForm">
                @csrf
                <input
                    type="password"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="4"
                    name="pin"
                    id="pinInput"
                    class="pin-input"
                    placeholder="••••"
                    autocomplete="off"
                    autofocus
                    required
                />

                <!-- Touch Numeric Keypad -->
                <div class="keypad">
                    <button type="button" class="key-btn" onclick="appendDigit('1')">1</button>
                    <button type="button" class="key-btn" onclick="appendDigit('2')">2</button>
                    <button type="button" class="key-btn" onclick="appendDigit('3')">3</button>
                    <button type="button" class="key-btn" onclick="appendDigit('4')">4</button>
                    <button type="button" class="key-btn" onclick="appendDigit('5')">5</button>
                    <button type="button" class="key-btn" onclick="appendDigit('6')">6</button>
                    <button type="button" class="key-btn" onclick="appendDigit('7')">7</button>
                    <button type="button" class="key-btn" onclick="appendDigit('8')">8</button>
                    <button type="button" class="key-btn" onclick="appendDigit('9')">9</button>
                    <button type="button" class="key-btn action" onclick="clearPin()" title="Cancella tutto">C</button>
                    <button type="button" class="key-btn" onclick="appendDigit('0')">0</button>
                    <button type="button" class="key-btn action" onclick="deleteDigit()" title="Cancella ultima cifra">⌫</button>
                </div>

                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; font-size: 1rem;">
                        <span>Sblocca</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const input = document.getElementById('pinInput');
        const form = document.getElementById('pinForm');

        function appendDigit(digit) {
            if (input.value.length < 4) {
                input.value += digit;
                if (input.value.length === 4) {
                    form.submit();
                }
            }
        }

        function deleteDigit() {
            input.value = input.value.slice(0, -1);
        }

        function clearPin() {
            input.value = '';
            input.focus();
        }

        input.addEventListener('input', (e) => {
            if (input.value.length === 4) {
                form.submit();
            }
        });
    </script>
</body>
</html>
