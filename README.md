# 💰 Tracker Spese ed Entrate Personali

Un'applicazione web moderna, pulita e reattiva per il monitoraggio e la gestione completa delle tue finanze personali (spese ed entrate in Euro), costruita con **Laravel**, **SQLite**, **Blade**, **Vue 3** e **100% puro CSS** (senza Tailwind, solo Light Theme).

---

## ✨ Caratteristiche Principali

- **Gestione Completa Entrate & Spese**: Tracciamento con data, importo in €, categoria, sottocategoria, metodo di pagamento e note.
- **Categorie & Sottocategorie Gerarchiche**: Organizzazione dinamica a cascata con icone emoji e colori personalizzabili.
- **Dashboard Finanziaria**: Metriche chiave del mese (Entrate, Spese, Saldo Netto, Tasso di Risparmio), grafico di andamento a 6 mesi e ripartizione percentuale delle uscite/entrate con progress bar.
- **Sezione Transazioni Interattiva**: Filtri istantanei (Tipo, Categoria/Sottocategoria, Mese/Anno, Ricerca testuale), ordinamento e paginazione.
- **Gestione Categorie**: Creazione, modifica ed eliminazione categorie e inserimento rapido di sottocategorie.
- **Report & Esportazione CSV**: Analisi annuale, confronto mese per mese ed esportazione istantanea in CSV compatibile con Excel.
- **Design 100% Puro CSS & Responsive**: Layout moderno, pulito, rigorosamente Light Theme, ottimizzato per desktop, tablet e smartphone.

---

## 🛠️ Stack Tecnologico

- **Backend**: PHP 8.4+ & Laravel 12
- **Database**: SQLite
- **Frontend**: Blade Templates + Componenti Reattivi Vue 3 (SFC)
- **Stili**: Puro CSS (Custom Properties / CSS Variables, Flexbox, Grid)
- **Grafici**: Chart.js
- **Build Tool**: Vite

---

## 🚀 Installazione & Avvio Rapido

1. **Clona il repository**:
   ```bash
   git clone git@github.com:osammot11/tracker-spese.git
   cd tracker-spese
   ```

2. **Installa le dipendenze PHP e Node**:
   ```bash
   composer install
   npm install
   ```

3. **Configura l'ambiente e il Database**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   php artisan migrate --seed
   ```

4. **Compila gli asset frontend**:
   ```bash
   npm run build
   # oppure per lo sviluppo continuo:
   npm run dev
   ```

5. **Avvia l'applicazione**:
   ```bash
   php artisan serve
   ```
   Apri il browser all'indirizzo `http://localhost:8000`.

---

## 🧪 Test Automatizzati

Per eseguire la suite di test completa:
```bash
php artisan test
```

