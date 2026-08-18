@extends("layouts.app")

@section("title", "Transazioni")

@section("content")
<div class="page-header">
    <div>
        <h1 class="page-title">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
            <span>Gestione Transazioni</span>
        </h1>
        <p class="page-subtitle">Visualizza, filtra, modifica e gestisci tutte le tue entrate e uscite</p>
    </div>

    <div class="page-actions">
        <button type="button" @click="openNewTransactionModal('income')" class="btn btn-income">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Nuova Entrata</span>
        </button>
        <button type="button" @click="openNewTransactionModal('expense')" class="btn btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Nuova Spesa</span>
        </button>
    </div>
</div>

<!-- Reactive Vue Transactions Manager -->
<transaction-manager 
    :categories="{{ json_encode($categories) }}"
    :initial-filters="{{ json_encode($initialFilters) }}">
</transaction-manager>
@endsection
