<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield("title", "Tracker Spese") - Gestione Finanze Personali</title>
    
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    @vite(["resources/css/app.css", "resources/js/app.js"])
    @stack("styles")
</head>
<body>
    <div id="app">
        <!-- Top Navigation -->
        <header class="header">
            <div class="header-container">
                <a href="{{ route("dashboard") }}" class="logo-brand">
                    <div class="logo-icon">
                        <span>€</span>
                    </div>
                    <div class="logo-title">
                        <span>SpeseTracker</span>
                        <span>Finanze Personali</span>
                    </div>
                </a>

                <nav>
                    <ul class="nav-menu" id="navMenu">
                        <li>
                            <a href="{{ route("dashboard") }}" class="nav-link {{ request()->routeIs("dashboard") ? "active" : "" }}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="9"></rect>
                                    <rect x="14" y="3" width="7" height="5"></rect>
                                    <rect x="14" y="12" width="7" height="9"></rect>
                                    <rect x="3" y="16" width="7" height="5"></rect>
                                </svg>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route("transactions.index") }}" class="nav-link {{ request()->routeIs("transactions.*") ? "active" : "" }}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                                <span>Transazioni</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route("categories.index") }}" class="nav-link {{ request()->routeIs("categories.*") ? "active" : "" }}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                                </svg>
                                <span>Categorie</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route("reports.index") }}" class="nav-link {{ request()->routeIs("reports.*") ? "active" : "" }}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="20" x2="18" y2="10"></line>
                                    <line x1="12" y1="20" x2="12" y2="4"></line>
                                    <line x1="6" y1="20" x2="6" y2="14"></line>
                                </svg>
                                <span>Report</span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="header-actions">
                    <button type="button" @click="openNewTransactionModal('expense')" class="btn btn-danger btn-sm" title="Aggiungi Spesa">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Nuova Spesa</span>
                    </button>
                    <button type="button" @click="openNewTransactionModal('income')" class="btn btn-income btn-sm" title="Aggiungi Entrata">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Nuova Entrata</span>
                    </button>
                    <button type="button" class="mobile-menu-btn" onclick="document.getElementById('navMenu').classList.toggle('mobile-open')">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-wrapper">
            @if(session("success"))
                <div class="alert alert-success">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <span>{{ session("success") }}</span>
                    </div>
                </div>
            @endif

            @if(session("error"))
                <div class="alert alert-danger">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span>{{ session("error") }}</span>
                    </div>
                </div>
            @endif

            @yield("content")
        </main>

        <!-- Global Transaction Modal Component -->
        <transaction-modal ref="transactionModal" @saved="onTransactionSaved"></transaction-modal>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; {{ date("Y") }} SpeseTracker &mdash; Gestione Spese ed Entrate Personali</p>
        </footer>
    </div>

    @stack("scripts")
</body>
</html>
