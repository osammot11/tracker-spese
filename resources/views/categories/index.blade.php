@extends("layouts.app")

@section("title", "Categorie")

@section("content")
<div class="page-header">
    <div>
        <h1 class="page-title">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
            </svg>
            <span>Categorie & Sottocategorie</span>
        </h1>
        <p class="page-subtitle">Organizza la struttura delle tue spese ed entrate personali</p>
    </div>
</div>

<!-- Reactive Vue Category Manager -->
<category-manager :initial-categories="{{ json_encode($categories) }}"></category-manager>
@endsection
