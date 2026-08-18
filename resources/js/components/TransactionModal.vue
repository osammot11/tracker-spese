<template>
  <div v-if="isOpen" class="modal-backdrop" @click.self="close">
    <div class="modal-dialog">
      <div class="modal-header">
        <h3 class="modal-title">
          {{ isEditing ? "Modifica Transazione" : (form.type === "expense" ? "Nuova Spesa" : "Nuova Entrata") }}
        </h3>
        <button type="button" class="modal-close" @click="close">&times;</button>
      </div>

      <form @submit.prevent="submitForm">
        <div class="modal-body">
          <!-- Type Toggle (Spesa / Entrata) -->
          <div class="form-group" style="margin-bottom: 1.25rem;">
            <div class="type-toggle-wrapper">
              <button
                type="button"
                class="type-toggle-btn"
                :class="{ 'active-expense': form.type === 'expense' }"
                @click="setType('expense')"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <polyline points="19 12 12 19 5 12"></polyline>
                </svg>
                <span>Spesa</span>
              </button>
              <button
                type="button"
                class="type-toggle-btn"
                :class="{ 'active-income': form.type === 'income' }"
                @click="setType('income')"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <line x1="12" y1="19" x2="12" y2="5"></line>
                  <polyline points="5 12 12 5 19 12"></polyline>
                </svg>
                <span>Entrata</span>
              </button>
            </div>
          </div>

          <!-- Amount -->
          <div class="form-group">
            <label class="form-label">Importo (€) *</label>
            <div class="input-icon-wrapper input-euro-wrapper">
              <span class="input-euro-prefix">€</span>
              <input
                type="number"
                step="0.01"
                min="0.01"
                v-model.number="form.amount"
                class="form-control"
                placeholder="0,00"
                required
                ref="amountInput"
              />
            </div>
          </div>

          <!-- Date & Payment Method Grid -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
              <label class="form-label">Data *</label>
              <input
                type="date"
                v-model="form.date"
                class="form-control"
                required
              />
            </div>

            <div class="form-group">
              <label class="form-label">Metodo di Pagamento</label>
              <select v-model="form.payment_method" class="form-control">
                <option value="">Seleziona...</option>
                <option value="Carta di Debito">Carta di Debito / Bancomat</option>
                <option value="Carta di Credito">Carta di Credito</option>
                <option value="Bonifico Bancario">Bonifico Bancario</option>
                <option value="Contanti">Contanti</option>
                <option value="PayPal">PayPal</option>
                <option value="Addebito Diretto">Addebito Diretto (RID)</option>
                <option value="Altro">Altro</option>
              </select>
            </div>
          </div>

          <!-- Category & Subcategory Grid -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
              <label class="form-label">Categoria *</label>
              <select v-model="form.category_id" @change="onCategoryChange" class="form-control" required>
                <option value="">Seleziona Categoria</option>
                <option v-for="cat in filteredCategories" :key="cat.id" :value="cat.id">
                  {{ cat.icon }} {{ cat.name }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Sottocategoria</label>
              <select v-model="form.subcategory_id" class="form-control" :disabled="!availableSubcategories.length">
                <option value="">{{ availableSubcategories.length ? "Nessuna (Generale)" : "Nessuna disponibile" }}</option>
                <option v-for="sub in availableSubcategories" :key="sub.id" :value="sub.id">
                  {{ sub.name }}
                </option>
              </select>
            </div>
          </div>

          <!-- Description -->
          <div class="form-group">
            <label class="form-label">Descrizione / Causale</label>
            <input
              type="text"
              v-model="form.description"
              class="form-control"
              placeholder="es. Spesa supermercato, Bolletta luce, ecc."
            />
          </div>

          <!-- Notes -->
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Note aggiuntive (opzionale)</label>
            <textarea
              v-model="form.notes"
              class="form-control"
              rows="2"
              placeholder="Dettagli ulteriori..."
            ></textarea>
          </div>

          <!-- Error Alert -->
          <div v-if="errorMessage" class="alert alert-danger" style="margin-top: 1rem; margin-bottom: 0;">
            {{ errorMessage }}
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="close" :disabled="isSubmitting">
            Annulla
          </button>
          <button
            type="submit"
            class="btn"
            :class="form.type === 'expense' ? 'btn-danger' : 'btn-income'"
            :disabled="isSubmitting || !form.amount || !form.category_id"
          >
            <span v-if="isSubmitting">Salvataggio...</span>
            <span v-else>{{ isEditing ? "Aggiorna Transazione" : (form.type === "expense" ? "Aggiungi Spesa" : "Aggiungi Entrata") }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
export default {
  name: "TransactionModal",
  data() {
    return {
      isOpen: false,
      isEditing: false,
      editingId: null,
      isSubmitting: false,
      errorMessage: "",
      categories: [],
      form: {
        type: "expense",
        amount: "",
        date: new Date().toISOString().split("T")[0],
        category_id: "",
        subcategory_id: "",
        payment_method: "",
        description: "",
        notes: ""
      }
    };
  },
  computed: {
    filteredCategories() {
      return this.categories.filter(c => c.type === this.form.type || c.type === "both");
    },
    selectedCategory() {
      if (!this.form.category_id) return null;
      return this.categories.find(c => c.id == this.form.category_id) || null;
    },
    availableSubcategories() {
      return this.selectedCategory ? this.selectedCategory.subcategories || [] : [];
    }
  },
  mounted() {
    this.fetchCategories();
  },
  methods: {
    async fetchCategories() {
      try {
        const res = await fetch("/api/categories");
        if (res.ok) {
          this.categories = await res.json();
        }
      } catch (err) {
        console.error("Errore caricamento categorie:", err);
      }
    },
    openNew(type = "expense") {
      this.isEditing = false;
      this.editingId = null;
      this.errorMessage = "";
      this.form = {
        type: type,
        amount: "",
        date: new Date().toISOString().split("T")[0],
        category_id: "",
        subcategory_id: "",
        payment_method: "",
        description: "",
        notes: ""
      };
      this.isOpen = true;
      this.$nextTick(() => {
        if (this.$refs.amountInput) {
          this.$refs.amountInput.focus();
        }
      });
    },
    edit(transaction) {
      this.isEditing = true;
      this.editingId = transaction.id;
      this.errorMessage = "";
      
      const dateFormatted = typeof transaction.date === "string" 
        ? transaction.date.substring(0, 10) 
        : new Date().toISOString().split("T")[0];

      this.form = {
        type: transaction.type,
        amount: parseFloat(transaction.amount),
        date: dateFormatted,
        category_id: transaction.category_id,
        subcategory_id: transaction.subcategory_id || "",
        payment_method: transaction.payment_method || "",
        description: transaction.description || "",
        notes: transaction.notes || ""
      };
      this.isOpen = true;
    },
    setType(type) {
      this.form.type = type;
      if (this.selectedCategory && this.selectedCategory.type !== type && this.selectedCategory.type !== "both") {
        this.form.category_id = "";
        this.form.subcategory_id = "";
      }
    },
    onCategoryChange() {
      this.form.subcategory_id = "";
    },
    close() {
      this.isOpen = false;
    },
    async submitForm() {
      this.isSubmitting = true;
      this.errorMessage = "";

      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
      const url = this.isEditing ? "/api/transactions/" + this.editingId : "/api/transactions";
      const method = this.isEditing ? "PUT" : "POST";

      try {
        const res = await fetch(url, {
          method: method,
          headers: {
            "Content-Type": "application/json",
            "Accept": "application/json",
            "X-CSRF-TOKEN": token
          },
          body: JSON.stringify(this.form)
        });

        const data = await res.json();

        if (res.ok && data.success) {
          this.isOpen = false;
          this.$emit("saved", data.transaction);
          if (window.location.pathname === "/" || window.location.pathname.startsWith("/reports")) {
            window.location.reload();
          }
        } else {
          this.errorMessage = data.message || "Si è verificato un errore durante il salvataggio.";
        }
      } catch (err) {
        this.errorMessage = "Errore di connessione al server.";
      } finally {
        this.isSubmitting = false;
      }
    }
  }
};
</script>