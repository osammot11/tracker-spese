<template>
  <div class="transaction-manager">
    <!-- Filter Bar Card -->
    <div class="card" style="margin-bottom: 1.5rem;">
      <div class="card-body" style="padding: 1.25rem;">
        <!-- Filters Row 1: Type Segment + Search -->
        <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
          <!-- Type Segment Filter -->
          <div class="type-toggle-wrapper" style="width: auto; min-width: 280px;">
            <button
              type="button"
              class="type-toggle-btn"
              :class="{ 'active-expense': filters.type === '' }"
              @click="setType('')"
            >
              Tutte
            </button>
            <button
              type="button"
              class="type-toggle-btn"
              :class="{ 'active-expense': filters.type === 'expense' }"
              @click="setType('expense')"
            >
              Solo Spese
            </button>
            <button
              type="button"
              class="type-toggle-btn"
              :class="{ 'active-income': filters.type === 'income' }"
              @click="setType('income')"
            >
              Solo Entrate
            </button>
          </div>

          <!-- Search Input -->
          <div class="input-icon-wrapper" style="flex: 1; min-width: 220px; max-width: 380px;">
            <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input
              type="text"
              v-model="filters.search"
              @input="debounceSearch"
              class="form-control"
              placeholder="Cerca per descrizione, note o metodo..."
            />
          </div>
        </div>

        <!-- Filters Row 2: Category, Subcategory, Month, Year, Reset -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 0.85rem; align-items: flex-end;">
          <!-- Category -->
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" style="font-size: 0.78rem;">Categoria</label>
            <select v-model="filters.category_id" @change="onCategoryFilterChange" class="form-control">
              <option value="">Tutte le Categorie</option>
              <option v-for="cat in availableFilterCategories" :key="cat.id" :value="cat.id">
                {{ cat.icon }} {{ cat.name }}
              </option>
            </select>
          </div>

          <!-- Subcategory -->
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" style="font-size: 0.78rem;">Sottocategoria</label>
            <select v-model="filters.subcategory_id" @change="fetchTransactions(1)" class="form-control" :disabled="!availableFilterSubcategories.length">
              <option value="">Tutte le Sottocategorie</option>
              <option v-for="sub in availableFilterSubcategories" :key="sub.id" :value="sub.id">
                {{ sub.name }}
              </option>
            </select>
          </div>

          <!-- Month -->
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" style="font-size: 0.78rem;">Mese</label>
            <select v-model="filters.month" @change="fetchTransactions(1)" class="form-control">
              <option value="all">Tutto l'anno</option>
              <option value="1">Gennaio</option>
              <option value="2">Febbraio</option>
              <option value="3">Marzo</option>
              <option value="4">Aprile</option>
              <option value="5">Maggio</option>
              <option value="6">Giugno</option>
              <option value="7">Luglio</option>
              <option value="8">Agosto</option>
              <option value="9">Settembre</option>
              <option value="10">Ottobre</option>
              <option value="11">Novembre</option>
              <option value="12">Dicembre</option>
            </select>
          </div>

          <!-- Year -->
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" style="font-size: 0.78rem;">Anno</label>
            <select v-model="filters.year" @change="fetchTransactions(1)" class="form-control">
              <option value="all">Tutti gli anni</option>
              <option value="2024">2024</option>
              <option value="2025">2025</option>
              <option value="2026">2026</option>
              <option value="2027">2027</option>
            </select>
          </div>

          <!-- Clear Filters Button -->
          <div style="margin-bottom: 0;">
            <button type="button" @click="resetFilters" class="btn btn-secondary" style="width: 100%;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="1 4 1 10 7 10"></polyline>
                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
              </svg>
              <span>Azzera Filtri</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Summary KPI Bar -->
    <div class="grid-4" style="margin-bottom: 1.5rem;">
      <div class="stat-card" style="padding: 1rem 1.25rem;">
        <span class="stat-label" style="font-size: 0.75rem;">Entrate Filtrate</span>
        <div class="stat-value text-income" style="font-size: 1.45rem;">
          +{{ formatCurrency(summary.total_income) }} €
        </div>
      </div>
      <div class="stat-card" style="padding: 1rem 1.25rem;">
        <span class="stat-label" style="font-size: 0.75rem;">Spese Filtrate</span>
        <div class="stat-value text-expense" style="font-size: 1.45rem;">
          -{{ formatCurrency(summary.total_expense) }} €
        </div>
      </div>
      <div class="stat-card" style="padding: 1rem 1.25rem;">
        <span class="stat-label" style="font-size: 0.75rem;">Saldo Risultati</span>
        <div class="stat-value" :class="summary.net_balance >= 0 ? 'text-income' : 'text-expense'" style="font-size: 1.45rem;">
          {{ summary.net_balance >= 0 ? '+' : '' }}{{ formatCurrency(summary.net_balance) }} €
        </div>
      </div>
      <div class="stat-card" style="padding: 1rem 1.25rem;">
        <span class="stat-label" style="font-size: 0.75rem;">Totale Transazioni</span>
        <div class="stat-value text-primary" style="font-size: 1.45rem;">
          {{ summary.total_count }}
        </div>
      </div>
    </div>

    <!-- Transactions Table Card -->
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
            <span>Elenco Transazioni</span>
          </h2>
          <span class="card-subtitle">
            Mostrati {{ transactions.length }} su {{ pagination.total }} record
          </span>
        </div>

        <!-- Sorting -->
        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <label style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">Ordina:</label>
          <select v-model="filters.sort_by" @change="fetchTransactions(1)" class="form-control" style="width: auto; padding: 0.35rem 1.75rem 0.35rem 0.65rem; font-size: 0.84rem;">
            <option value="date">Data</option>
            <option value="amount">Importo</option>
          </select>
          <select v-model="filters.sort_order" @change="fetchTransactions(1)" class="form-control" style="width: auto; padding: 0.35rem 1.75rem 0.35rem 0.65rem; font-size: 0.84rem;">
            <option value="desc">Decrescente</option>
            <option value="asc">Crescente</option>
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" style="padding: 3rem; text-align: center; color: var(--text-muted);">
        <p>Caricamento transazioni in corso...</p>
      </div>

      <!-- Table -->
      <div v-else-if="transactions.length > 0" class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Data</th>
              <th>Categoria & Sottocategoria</th>
              <th>Descrizione</th>
              <th>Metodo Pagamento</th>
              <th style="text-align: right;">Importo</th>
              <th style="text-align: center; width: 100px;">Azioni</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="t in transactions" :key="t.id">
              <td style="white-space: nowrap; font-weight: 500;">
                {{ formatDate(t.date) }}
              </td>
              <td>
                <span
                  class="badge"
                  :style="{
                    backgroundColor: (t.category?.color || '#6366f1') + '18',
                    color: t.category?.color || '#334155',
                    borderColor: (t.category?.color || '#6366f1') + '40'
                  }"
                >
                  <span>{{ t.category?.icon || '📁' }}</span>
                  <span>{{ t.category?.name || 'Senza Categoria' }}</span>
                  <span v-if="t.subcategory" style="opacity: 0.75;">&rsaquo; {{ t.subcategory.name }}</span>
                </span>
              </td>
              <td>
                <div style="font-weight: 600; color: var(--text-main);">
                  {{ t.description || (t.subcategory?.name || t.category?.name || 'Transazione') }}
                </div>
                <div v-if="t.notes" style="font-size: 0.78rem; color: var(--text-muted);">
                  {{ t.notes }}
                </div>
              </td>
              <td>
                <span style="font-size: 0.84rem; color: var(--text-muted);">
                  {{ t.payment_method || 'N/D' }}
                </span>
              </td>
              <td style="text-align: right;">
                <span
                  class="amount"
                  :class="t.type === 'income' ? 'amount-income' : 'amount-expense'"
                  style="font-size: 0.98rem;"
                >
                  {{ t.type === 'income' ? '+' : '-' }}{{ formatCurrency(t.amount) }} €
                </span>
              </td>
              <td style="text-align: center;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 0.25rem;">
                  <button
                    type="button"
                    class="btn btn-ghost btn-sm"
                    @click="onEditClick(t)"
                    title="Modifica Transazione"
                    style="padding: 0.3rem 0.5rem;"
                  >
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                  </button>
                  <button
                    type="button"
                    class="btn btn-ghost btn-sm"
                    @click="onDeleteClick(t)"
                    title="Elimina Transazione"
                    style="padding: 0.3rem 0.5rem; color: var(--expense);"
                  >
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="3 6 5 6 21 6"></polyline>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-state">
        <div class="empty-state-icon">🔍</div>
        <h3 class="empty-state-title">Nessuna transazione trovata</h3>
        <p class="empty-state-text">Non ci sono transazioni corrispondenti ai filtri impostati. Prova a modificare o azzerare i filtri.</p>
        <button type="button" @click="resetFilters" class="btn btn-secondary">
          Azzera Filtri
        </button>
      </div>

      <!-- Pagination Footer -->
      <div v-if="pagination.last_page > 1" class="card-footer">
        <div style="font-size: 0.85rem; color: var(--text-muted);">
          Pagina {{ pagination.current_page }} di {{ pagination.last_page }}
        </div>
        <div style="display: flex; gap: 0.5rem;">
          <button
            type="button"
            class="btn btn-secondary btn-sm"
            :disabled="pagination.current_page === 1"
            @click="fetchTransactions(pagination.current_page - 1)"
          >
            &larr; Precedente
          </button>
          <button
            type="button"
            class="btn btn-secondary btn-sm"
            :disabled="pagination.current_page === pagination.last_page"
            @click="fetchTransactions(pagination.current_page + 1)"
          >
            Successiva &rarr;
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="deletingTransaction" class="modal-backdrop" @click.self="deletingTransaction = null">
      <div class="modal-dialog" style="max-width: 420px;">
        <div class="modal-header">
          <h3 class="modal-title">Conferma Eliminazione</h3>
          <button type="button" class="modal-close" @click="deletingTransaction = null">&times;</button>
        </div>
        <div class="modal-body">
          <p style="color: var(--text-secondary); margin-bottom: 0.75rem;">
            Sei sicuro di voler eliminare questa transazione da <strong>{{ formatCurrency(deletingTransaction.amount) }} €</strong> ({{ deletingTransaction.category?.name }})?
          </p>
          <p style="font-size: 0.82rem; color: var(--text-muted);">
            L'operazione non può essere annullata.
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="deletingTransaction = null">
            Annulla
          </button>
          <button type="button" class="btn btn-danger" @click="executeDelete">
            Elimina
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "TransactionManager",
  props: {
    categories: {
      type: Array,
      default: () => []
    },
    initialFilters: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      transactions: [],
      summary: {
        total_income: 0,
        total_expense: 0,
        net_balance: 0,
        total_count: 0
      },
      filters: {
        type: this.initialFilters.type || "",
        category_id: this.initialFilters.category_id || "",
        subcategory_id: this.initialFilters.subcategory_id || "",
        search: this.initialFilters.search || "",
        month: this.initialFilters.month || new Date().getMonth() + 1,
        year: this.initialFilters.year || new Date().getFullYear(),
        sort_by: "date",
        sort_order: "desc"
      },
      pagination: {
        current_page: 1,
        last_page: 1,
        total: 0,
        per_page: 15
      },
      isLoading: false,
      searchTimeout: null,
      deletingTransaction: null
    };
  },
  computed: {
    availableFilterCategories() {
      if (!this.filters.type) return this.categories;
      return this.categories.filter(c => c.type === this.filters.type || c.type === "both");
    },
    selectedFilterCategory() {
      if (!this.filters.category_id) return null;
      return this.categories.find(c => c.id == this.filters.category_id) || null;
    },
    availableFilterSubcategories() {
      return this.selectedFilterCategory ? this.selectedFilterCategory.subcategories || [] : [];
    }
  },
  mounted() {
    this.fetchTransactions();
    // Listen for saved transactions on window/app to auto-refresh table
    window.addEventListener("transaction-saved", () => {
      this.fetchTransactions(this.pagination.current_page);
    });
  },
  methods: {
    async fetchTransactions(page = 1) {
      this.isLoading = true;

      const params = new URLSearchParams({
        page: page,
        type: this.filters.type,
        category_id: this.filters.category_id,
        subcategory_id: this.filters.subcategory_id,
        search: this.filters.search,
        month: this.filters.month,
        year: this.filters.year,
        sort_by: this.filters.sort_by,
        sort_order: this.filters.sort_order
      });

      try {
        const res = await fetch(`/api/transactions?${params.toString()}`);
        if (res.ok) {
          const data = await res.json();
          this.transactions = data.transactions.data || [];
          this.pagination = {
            current_page: data.transactions.current_page,
            last_page: data.transactions.last_page,
            total: data.transactions.total,
            per_page: data.transactions.per_page
          };
          this.summary = data.summary || this.summary;
        }
      } catch (err) {
        console.error("Errore caricamento transazioni:", err);
      } finally {
        this.isLoading = false;
      }
    },
    debounceSearch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.fetchTransactions(1);
      }, 300);
    },
    setType(type) {
      this.filters.type = type;
      if (this.selectedFilterCategory && this.selectedFilterCategory.type !== type && this.selectedFilterCategory.type !== "both") {
        this.filters.category_id = "";
        this.filters.subcategory_id = "";
      }
      this.fetchTransactions(1);
    },
    onCategoryFilterChange() {
      this.filters.subcategory_id = "";
      this.fetchTransactions(1);
    },
    resetFilters() {
      this.filters = {
        type: "",
        category_id: "",
        subcategory_id: "",
        search: "",
        month: "all",
        year: new Date().getFullYear(),
        sort_by: "date",
        sort_order: "desc"
      };
      this.fetchTransactions(1);
    },
    formatCurrency(val) {
      return Number(val || 0).toLocaleString("it-IT", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    },
    formatDate(dateStr) {
      if (!dateStr) return "";
      const d = new Date(dateStr);
      return d.toLocaleDateString("it-IT", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric"
      });
    },
    onEditClick(transaction) {
      if (window.appInstance && window.appInstance.editTransaction) {
        window.appInstance.editTransaction(transaction);
      } else if (this.$root && this.$root.$refs && this.$root.$refs.transactionModal) {
        this.$root.$refs.transactionModal.edit(transaction);
      }
    },
    onDeleteClick(transaction) {
      this.deletingTransaction = transaction;
    },
    async executeDelete() {
      if (!this.deletingTransaction) return;

      const id = this.deletingTransaction.id;
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");

      try {
        const res = await fetch(`/api/transactions/${id}`, {
          method: "DELETE",
          headers: {
            "Accept": "application/json",
            "X-CSRF-TOKEN": token
          }
        });

        if (res.ok) {
          this.deletingTransaction = null;
          this.fetchTransactions(this.pagination.current_page);
        }
      } catch (err) {
        console.error("Errore cancellazione transazione:", err);
      }
    }
  }
};
</script>