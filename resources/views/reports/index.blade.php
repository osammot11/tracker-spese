@extends("layouts.app")

@section("title", "Report & Statistiche")

@section("content")
<div class="page-header">
    <div>
        <h1 class="page-title">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
            <span>Report & Analisi Annuale</span>
        </h1>
        <p class="page-subtitle">Panoramica completa del flusso di cassa e distribuzione delle spese per il {{ $selectedYear }}</p>
    </div>

    <div class="page-actions">
        <form method="GET" action="{{ route('reports.index') }}" style="display: flex; align-items: center; gap: 0.5rem;">
            <label for="year-select" style="font-size: 0.88rem; font-weight: 600; color: var(--text-muted);">Anno:</label>
            <select id="year-select" name="year" class="form-control" style="width: 120px; padding: 0.45rem 1.8rem 0.45rem 0.75rem;" onchange="this.form.submit()">
                @foreach($availableYears as $yr)
                    <option value="{{ $yr }}" {{ $selectedYear == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                @endforeach
            </select>
        </form>

        <a href="{{ route('reports.export', ['year' => $selectedYear]) }}" class="btn btn-secondary" title="Esporta dati in formato CSV">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            <span>Esporta CSV</span>
        </a>
    </div>
</div>

<!-- Annual KPI Cards -->
<div class="grid-4" style="margin-bottom: 1.75rem;">
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-label">Entrate {{ $selectedYear }}</span>
            <div class="stat-icon-wrapper stat-icon-income">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="19" x2="12" y2="5"></line>
                    <polyline points="5 12 12 5 19 12"></polyline>
                </svg>
            </div>
        </div>
        <div class="stat-value text-income">+{{ number_format($totalYearIncome, 2, ',', '.') }} €</div>
        <div class="stat-subtext">
            <span>Totale entrate incassate</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-label">Spese {{ $selectedYear }}</span>
            <div class="stat-icon-wrapper stat-icon-expense">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <polyline points="19 12 12 19 5 12"></polyline>
                </svg>
            </div>
        </div>
        <div class="stat-value text-expense">-{{ number_format($totalYearExpense, 2, ',', '.') }} €</div>
        <div class="stat-subtext">
            <span>Totale uscite effettuate</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-label">Risparmio Netto</span>
            <div class="stat-icon-wrapper {{ $totalYearBalance >= 0 ? 'stat-icon-income' : 'stat-icon-expense' }}">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"></path>
                    <line x1="12" y1="6" x2="12" y2="8"></line>
                    <line x1="12" y1="16" x2="12" y2="18"></line>
                </svg>
            </div>
        </div>
        <div class="stat-value {{ $totalYearBalance >= 0 ? 'text-income' : 'text-expense' }}">
            {{ $totalYearBalance >= 0 ? '+' : '' }}{{ number_format($totalYearBalance, 2, ',', '.') }} €
        </div>
        <div class="stat-subtext">
            <span>Flusso netto complessivo</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-label">Tasso Risparmio</span>
            <div class="stat-icon-wrapper stat-icon-primary">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                    <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                </svg>
            </div>
        </div>
        <div class="stat-value text-primary">{{ $yearSavingsRate }}%</div>
        <div class="stat-subtext">
            <span>Media annuale di risparmio</span>
        </div>
    </div>
</div>

<!-- Annual Chart -->
<div class="card" style="margin-bottom: 1.75rem;">
    <div class="card-header">
        <div>
            <h2 class="card-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg>
                <span>Confronto Mensile {{ $selectedYear }}</span>
            </h2>
            <span class="card-subtitle">Distribuzione mese per mese delle entrate e delle spese</span>
        </div>
    </div>
    <div class="card-body">
        <annual-bar-chart :months-data="{{ json_encode($monthlyData) }}"></annual-bar-chart>
    </div>
</div>

<!-- Monthly Details Table & Category Breakdown -->
<div class="grid-2-1">
    <!-- Monthly Breakdown Table -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="8" y1="6" x2="21" y2="6"></line>
                        <line x1="8" y1="12" x2="21" y2="12"></line>
                        <line x1="8" y1="18" x2="21" y2="18"></line>
                        <line x1="3" y1="6" x2="3.01" y2="6"></line>
                        <line x1="3" y1="12" x2="3.01" y2="12"></line>
                        <line x1="3" y1="18" x2="3.01" y2="18"></line>
                    </svg>
                    <span>Dettaglio Mese per Mese</span>
                </h2>
                <span class="card-subtitle">Riepilogo e link rapidi per ciascun mese</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Mese</th>
                        <th style="text-align: right;">Entrate</th>
                        <th style="text-align: right;">Spese</th>
                        <th style="text-align: right;">Saldo</th>
                        <th style="text-align: center;">Tasso</th>
                        <th style="text-align: center;">Vedi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($monthlyData as $m)
                        <tr>
                            <td style="font-weight: 600;">{{ $m['month_name'] }}</td>
                            <td style="text-align: right;">
                                <span class="amount amount-income" style="font-size: 0.88rem;">
                                    {{ $m['income'] > 0 ? '+' . number_format($m['income'], 2, ',', '.') . ' €' : '-' }}
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <span class="amount amount-expense" style="font-size: 0.88rem;">
                                    {{ $m['expense'] > 0 ? '-' . number_format($m['expense'], 2, ',', '.') . ' €' : '-' }}
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <span class="amount {{ $m['balance'] >= 0 ? 'amount-income' : 'amount-expense' }}" style="font-size: 0.88rem;">
                                    {{ $m['balance'] != 0 ? ($m['balance'] > 0 ? '+' : '') . number_format($m['balance'], 2, ',', '.') . ' €' : '0,00 €' }}
                                </span>
                            </td>
                            <td style="text-align: center;">
                                @if($m['income'] > 0)
                                    <span class="badge {{ $m['savings_rate'] >= 20 ? 'badge-income' : ('badge-category') }}">
                                        {{ $m['savings_rate'] }}%
                                    </span>
                                @else
                                    <span style="color: var(--text-light); font-size: 0.82rem;">-</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <a href="{{ route('dashboard', ['year' => $selectedYear, 'month' => $m['month_num']]) }}" class="btn btn-ghost btn-sm" title="Vai a {{ $m['month_name'] }}" style="padding: 0.25rem 0.5rem;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Annual Category Breakdown -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 2a10 10 0 0 1 10 10"></path>
                    </svg>
                    <span>Spese per Categoria ({{ $selectedYear }})</span>
                </h2>
                <span class="card-subtitle">Incidenza annuale di ogni categoria</span>
            </div>
        </div>
        <div class="card-body">
            @if(count($categoryYearBreakdown) > 0)
                <div class="category-progress-list">
                    @foreach($categoryYearBreakdown as $cb)
                        <div class="category-progress-item">
                            <div class="category-progress-header">
                                <div class="category-info">
                                    <span>{{ $cb['icon'] }}</span>
                                    <span>{{ $cb['name'] }}</span>
                                </div>
                                <div class="category-amounts">
                                    <span class="category-pct">{{ $cb['percentage'] }}%</span>
                                    <span style="font-weight: 700; color: var(--text-main); font-size: 0.92rem;">
                                        {{ number_format($cb['amount'], 2, ',', '.') }} €
                                    </span>
                                </div>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width: {{ $cb['percentage'] }}%; background-color: {{ $cb['color'] }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state" style="padding: 2rem 1rem;">
                    <span style="font-size: 2rem; margin-bottom: 0.5rem;">📂</span>
                    <p style="font-size: 0.88rem; color: var(--text-muted);">Nessuna spesa registrata per quest'anno</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
