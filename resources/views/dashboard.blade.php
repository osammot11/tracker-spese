@extends("layouts.app")

@section("title", "Dashboard")

@section("content")
<div class="page-header">
    <div>
        <h1 class="page-title">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);">
                <rect x="3" y="3" width="7" height="9"></rect>
                <rect x="14" y="3" width="7" height="5"></rect>
                <rect x="14" y="12" width="7" height="9"></rect>
                <rect x="3" y="16" width="7" height="5"></rect>
            </svg>
            <span>Dashboard Finanze</span>
        </h1>
        <p class="page-subtitle">Riepilogo e andamento delle tue spese ed entrate per {{ $monthName }}</p>
    </div>

    <div class="page-actions">
        @php
            $prevDate = \Carbon\Carbon::createFromDate($selectedYear, $selectedMonth, 1)->subMonth();
            $nextDate = \Carbon\Carbon::createFromDate($selectedYear, $selectedMonth, 1)->addMonth();
        @endphp
        <div class="month-picker-nav">
            <a href="{{ route('dashboard', ['year' => $prevDate->year, 'month' => $prevDate->month]) }}" class="month-picker-btn" title="Mese Precedente">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
            <span class="month-picker-title">{{ ucfirst($monthName) }}</span>
            <a href="{{ route('dashboard', ['year' => $nextDate->year, 'month' => $nextDate->month]) }}" class="month-picker-btn" title="Mese Successivo">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </a>
        </div>

        <button type="button" @click="openNewTransactionModal('expense')" class="btn btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Nuova Spesa</span>
        </button>
    </div>
</div>

<!-- 4 KPI Stat Cards -->
<div class="grid-4" style="margin-bottom: 1.75rem;">
    <!-- Entrate -->
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-label">Entrate del Mese</span>
            <div class="stat-icon-wrapper stat-icon-income">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="19" x2="12" y2="5"></line>
                    <polyline points="5 12 12 5 19 12"></polyline>
                </svg>
            </div>
        </div>
        <div class="stat-value text-income">+{{ number_format($totalIncome, 2, ',', '.') }} €</div>
        <div class="stat-subtext">
            <span class="stat-badge stat-badge-income">{{ count($incomesByCategory) }} categorie</span>
            <span>registrate nel mese</span>
        </div>
    </div>

    <!-- Spese -->
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-label">Spese del Mese</span>
            <div class="stat-icon-wrapper stat-icon-expense">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <polyline points="19 12 12 19 5 12"></polyline>
                </svg>
            </div>
        </div>
        <div class="stat-value text-expense">-{{ number_format($totalExpense, 2, ',', '.') }} €</div>
        <div class="stat-subtext">
            <span class="stat-badge stat-badge-expense">{{ count($expensesByCategory) }} categorie</span>
            <span>di uscite attive</span>
        </div>
    </div>

    <!-- Saldo Netto -->
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-label">Saldo Netto</span>
            <div class="stat-icon-wrapper {{ $netBalance >= 0 ? 'stat-icon-income' : 'stat-icon-expense' }}">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
        </div>
        <div class="stat-value {{ $netBalance >= 0 ? 'text-income' : 'text-expense' }}">
            {{ $netBalance >= 0 ? '+' : '' }}{{ number_format($netBalance, 2, ',', '.') }} €
        </div>
        <div class="stat-subtext">
            @if($netBalance >= 0)
                <span class="stat-badge stat-badge-income">In attivo</span>
                <span>Ottima gestione!</span>
            @else
                <span class="stat-badge stat-badge-expense">In deficit</span>
                <span>Spese maggiori delle entrate</span>
            @endif
        </div>
    </div>

    <!-- Tasso di Risparmio -->
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-label">Tasso Risparmio</span>
            <div class="stat-icon-wrapper stat-icon-amber">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                    <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                </svg>
            </div>
        </div>
        <div class="stat-value text-primary">{{ $savingsRate }}%</div>
        <div class="stat-subtext">
            <span>Del totale entrate risparmiato</span>
        </div>
    </div>
</div>

<!-- Main 2-Column Content -->
<div class="grid-2-1">
    <!-- Left Column: Trend Chart + Recent Transactions -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Trend Chart Card -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                        <span>Andamento Ultimi 6 Mesi</span>
                    </h2>
                    <span class="card-subtitle">Confronto entrate, spese e saldo mensile</span>
                </div>
            </div>
            <div class="card-body">
                <trend-chart :trend-data="{{ json_encode($sixMonthsTrend) }}"></trend-chart>
            </div>
        </div>

        <!-- Recent Transactions Card -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        <span>Transazioni Recenti</span>
                    </h2>
                    <span class="card-subtitle">Ultime transazioni di {{ $monthName }}</span>
                </div>
                <a href="{{ route('transactions.index', ['month' => $selectedMonth, 'year' => $selectedYear]) }}" class="btn btn-secondary btn-sm">
                    <span>Tutte le transazioni</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
            </div>

            @if($recentTransactions->count() > 0)
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Categoria</th>
                                <th>Descrizione</th>
                                <th>Metodo</th>
                                <th style="text-align: right;">Importo</th>
                                <th style="text-align: center;">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentTransactions as $t)
                                <tr>
                                    <td style="white-space: nowrap; font-weight: 500;">
                                        {{ $t->date->format('d/m/Y') }}
                                    </td>
                                    <td>
                                        <span class="badge" style="background-color: {{ $t->category ? $t->category->color . '18' : '#f1f5f9' }}; color: {{ $t->category ? $t->category->color : '#334155' }}; border: 1px solid {{ $t->category ? $t->category->color . '40' : '#e2e8f0' }};">
                                            <span>{{ $t->category ? $t->category->icon : '📁' }}</span>
                                            <span>{{ $t->category ? $t->category->name : 'Senza Categoria' }}</span>
                                            @if($t->subcategory)
                                                <span style="opacity: 0.7;">&rsaquo; {{ $t->subcategory->name }}</span>
                                            @endif
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--text-main);">
                                            {{ $t->description ?: ($t->subcategory ? $t->subcategory->name : $t->category->name) }}
                                        </div>
                                        @if($t->notes)
                                            <div style="font-size: 0.78rem; color: var(--text-muted);">{{ $t->notes }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span style="font-size: 0.84rem; color: var(--text-muted);">
                                            {{ $t->payment_method ?: 'N/D' }}
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <span class="amount {{ $t->type === 'income' ? 'amount-income' : 'amount-expense' }}" style="font-size: 0.98rem;">
                                            {{ $t->type === 'income' ? '+' : '-' }}{{ number_format($t->amount, 2, ',', '.') }} €
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <button type="button" @click="editTransaction({{ json_encode($t) }})" class="btn btn-ghost btn-sm" title="Modifica" style="padding: 0.25rem 0.5rem;">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">💸</div>
                    <h3 class="empty-state-title">Nessuna transazione in questo mese</h3>
                    <p class="empty-state-text">Non hai ancora inserito spese o entrate per {{ $monthName }}. Registra la prima transazione!</p>
                    <button type="button" @click="openNewTransactionModal('expense')" class="btn btn-primary">
                        + Inserisci Spesa
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Right Column: Category Breakdown -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Expenses By Category Card -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--expense);">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span>Ripartizione Spese</span>
                    </h2>
                    <span class="card-subtitle">Quote percentuali per categoria</span>
                </div>
            </div>
            <div class="card-body">
                @if(count($expensesByCategory) > 0)
                    <div class="category-progress-list">
                        @foreach($expensesByCategory as $cat)
                            <div class="category-progress-item">
                                <div class="category-progress-header">
                                    <div class="category-info">
                                        <span>{{ $cat['icon'] }}</span>
                                        <span>{{ $cat['name'] }}</span>
                                    </div>
                                    <div class="category-amounts">
                                        <span class="category-pct">{{ $cat['percentage'] }}%</span>
                                        <span style="font-weight: 700; color: var(--text-main); font-size: 0.92rem;">
                                            {{ number_format($cat['amount'], 2, ',', '.') }} €
                                        </span>
                                    </div>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-fill" style="width: {{ $cat['percentage'] }}%; background-color: {{ $cat['color'] }};"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state" style="padding: 1.5rem 1rem;">
                        <span style="font-size: 2rem; margin-bottom: 0.5rem;">📊</span>
                        <p style="font-size: 0.88rem; color: var(--text-muted);">Nessuna spesa registrata</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Incomes By Category Card -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--income);">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                            <polyline points="17 6 23 6 23 12"></polyline>
                        </svg>
                        <span>Fonti di Entrata</span>
                    </h2>
                    <span class="card-subtitle">Provenienza entrate del mese</span>
                </div>
            </div>
            <div class="card-body">
                @if(count($incomesByCategory) > 0)
                    <div class="category-progress-list">
                        @foreach($incomesByCategory as $incCat)
                            <div class="category-progress-item">
                                <div class="category-progress-header">
                                    <div class="category-info">
                                        <span>{{ $incCat['icon'] }}</span>
                                        <span>{{ $incCat['name'] }}</span>
                                    </div>
                                    <div class="category-amounts">
                                        <span class="category-pct">{{ $incCat['percentage'] }}%</span>
                                        <span style="font-weight: 700; color: var(--income); font-size: 0.92rem;">
                                            +{{ number_format($incCat['amount'], 2, ',', '.') }} €
                                        </span>
                                    </div>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-fill" style="width: {{ $incCat['percentage'] }}%; background-color: {{ $incCat['color'] }};"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state" style="padding: 1.5rem 1rem;">
                        <span style="font-size: 2rem; margin-bottom: 0.5rem;">💼</span>
                        <p style="font-size: 0.88rem; color: var(--text-muted);">Nessuna entrata registrata</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
