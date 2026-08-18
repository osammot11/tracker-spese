<template>
  <div class="category-manager">
    <!-- Top Action & Tab Switcher -->
    <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
      <div class="type-toggle-wrapper" style="min-width: 280px;">
        <button
          type="button"
          class="type-toggle-btn"
          :class="{ 'active-expense': activeTab === 'expense' }"
          @click="activeTab = 'expense'"
        >
          <span>🛒 Categorie Spese ({{ expenseCategories.length }})</span>
        </button>
        <button
          type="button"
          class="type-toggle-btn"
          :class="{ 'active-income': activeTab === 'income' }"
          @click="activeTab = 'income'"
        >
          <span>💼 Categorie Entrate ({{ incomeCategories.length }})</span>
        </button>
      </div>

      <button type="button" @click="openCreateCategoryModal" class="btn btn-primary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Nuova Categoria</span>
      </button>
    </div>

    <!-- Category Grid -->
    <div class="grid-2">
      <div
        v-for="cat in currentTabCategories"
        :key="cat.id"
        class="card"
        style="display: flex; flex-direction: column; justify-content: space-between;"
      >
        <div class="card-header">
          <div style="display: flex; align-items: center; gap: 0.75rem;">
            <div
              style="width: 42px; height: 42px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 1.4rem;"
              :style="{ backgroundColor: cat.color + '20', color: cat.color, border: '1px solid ' + cat.color + '40' }"
            >
              {{ cat.icon || '📁' }}
            </div>
            <div>
              <h3 class="card-title" style="font-size: 1.1rem;">{{ cat.name }}</h3>
              <span class="card-subtitle">
                {{ cat.transactions_count || 0 }} transazioni registrate
              </span>
            </div>
          </div>

          <div style="display: flex; align-items: center; gap: 0.35rem;">
            <button
              type="button"
              class="btn btn-ghost btn-sm"
              @click="openEditCategoryModal(cat)"
              title="Modifica Categoria"
              style="padding: 0.35rem 0.55rem;"
            >
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </button>
            <button
              type="button"
              class="btn btn-ghost btn-sm"
              @click="deletingCategory = cat"
              title="Elimina Categoria"
              style="padding: 0.35rem 0.55rem; color: var(--expense);"
            >
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
              </svg>
            </button>
          </div>
        </div>

        <div class="card-body" style="padding: 1.25rem;">
          <div style="margin-bottom: 0.75rem; font-size: 0.82rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em;">
            Sottocategorie ({{ (cat.subcategories || []).length }}):
          </div>

          <!-- Subcategories Pills -->
          <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.25rem;">
            <span
              v-for="sub in (cat.subcategories || [])"
              :key="sub.id"
              class="badge badge-category"
              style="font-size: 0.84rem; padding: 0.35rem 0.65rem; background-color: var(--bg-muted); display: inline-flex; align-items: center; gap: 0.4rem;"
            >
              <span>{{ sub.name }}</span>
              <button
                type="button"
                @click="deleteSubcategory(sub)"
                style="background: transparent; border: none; cursor: pointer; color: var(--text-muted); font-size: 1rem; line-height: 1; padding: 0 2px;"
                title="Elimina Sottocategoria"
              >
                &times;
              </button>
            </span>
            <span v-if="!(cat.subcategories || []).length" style="font-size: 0.85rem; color: var(--text-light); font-style: italic;">
              Nessuna sottocategoria creata
            </span>
          </div>

          <!-- Add Subcategory Input -->
          <form @submit.prevent="addSubcategory(cat)" style="display: flex; gap: 0.5rem;">
            <input
              type="text"
              v-model="subFormNames[cat.id]"
              class="form-control"
              placeholder="+ Nuova sottocategoria..."
              style="padding: 0.45rem 0.75rem; font-size: 0.85rem;"
            />
            <button
              type="submit"
              class="btn btn-secondary btn-sm"
              :disabled="!subFormNames[cat.id] || subFormNames[cat.id].trim() === ''"
            >
              Aggiungi
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Category Modal (Create / Edit) -->
    <div v-if="showCategoryModal" class="modal-backdrop" @click.self="showCategoryModal = false">
      <div class="modal-dialog">
        <div class="modal-header">
          <h3 class="modal-title">
            {{ editingCategory ? 'Modifica Categoria' : 'Nuova Categoria' }}
          </h3>
          <button type="button" class="modal-close" @click="showCategoryModal = false">&times;</button>
        </div>

        <form @submit.prevent="saveCategory">
          <div class="modal-body">
            <!-- Name -->
            <div class="form-group">
              <label class="form-label">Nome Categoria *</label>
              <input
                type="text"
                v-model="categoryForm.name"
                class="form-control"
                placeholder="es. Alimentari, Palestra, Stipendio..."
                required
              />
            </div>

            <!-- Type -->
            <div class="form-group">
              <label class="form-label">Tipo Categoria *</label>
              <div class="type-toggle-wrapper">
                <button
                  type="button"
                  class="type-toggle-btn"
                  :class="{ 'active-expense': categoryForm.type === 'expense' }"
                  @click="categoryForm.type = 'expense'"
                >
                  Spesa
                </button>
                <button
                  type="button"
                  class="type-toggle-btn"
                  :class="{ 'active-income': categoryForm.type === 'income' }"
                  @click="categoryForm.type = 'income'"
                >
                  Entrata
                </button>
              </div>
            </div>

            <!-- Icon Picker -->
            <div class="form-group">
              <label class="form-label">Icona (Emoji)</label>
              <div style="display: flex; gap: 0.5rem; margin-bottom: 0.6rem;">
                <input
                  type="text"
                  v-model="categoryForm.icon"
                  class="form-control"
                  style="width: 70px; text-align: center; font-size: 1.3rem; padding: 0.35rem;"
                  maxlength="4"
                />
                <span style="font-size: 0.82rem; color: var(--text-muted); display: flex; align-items: center;">
                  Scegli tra i preset sotto o inserisci un'emoji a tua scelta
                </span>
              </div>
              <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; max-height: 100px; overflow-y: auto; padding: 4px; background: var(--bg-muted); border-radius: var(--radius-md);">
                <button
                  v-for="ico in presetIcons"
                  :key="ico"
                  type="button"
                  @click="categoryForm.icon = ico"
                  style="background: #fff; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 1.15rem; width: 34px; height: 34px; cursor: pointer; display: flex; align-items: center; justify-content: center;"
                >
                  {{ ico }}
                </button>
              </div>
            </div>

            <!-- Color Picker -->
            <div class="form-group" style="margin-bottom: 0;">
              <label class="form-label">Colore Badge & Grafici *</label>
              <div style="display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.5rem;">
                <div
                  :style="{ backgroundColor: categoryForm.color }"
                  style="width: 32px; height: 32px; border-radius: var(--radius-sm); border: 2px solid #fff; box-shadow: var(--shadow-sm);"
                ></div>
                <input
                  type="text"
                  v-model="categoryForm.color"
                  class="form-control"
                  style="width: 110px; font-family: var(--font-mono); font-size: 0.85rem;"
                  required
                />
              </div>
              <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                <button
                  v-for="col in paletteColors"
                  :key="col"
                  type="button"
                  @click="categoryForm.color = col"
                  :style="{ backgroundColor: col }"
                  style="width: 26px; height: 26px; border-radius: var(--radius-full); border: 2px solid #fff; box-shadow: var(--shadow-xs); cursor: pointer;"
                  :title="col"
                ></button>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showCategoryModal = false">
              Annulla
            </button>
            <button type="submit" class="btn btn-primary" :disabled="!categoryForm.name">
              {{ editingCategory ? 'Salva Modifiche' : 'Crea Categoria' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Category Modal -->
    <div v-if="deletingCategory" class="modal-backdrop" @click.self="deletingCategory = null">
      <div class="modal-dialog" style="max-width: 420px;">
        <div class="modal-header">
          <h3 class="modal-title">Elimina Categoria</h3>
          <button type="button" class="modal-close" @click="deletingCategory = null">&times;</button>
        </div>
        <div class="modal-body">
          <p style="color: var(--text-secondary); margin-bottom: 0.75rem;">
            Sei sicuro di voler eliminare la categoria <strong>{{ deletingCategory.name }}</strong>?
          </p>
          <p style="font-size: 0.82rem; color: var(--expense); font-weight: 500;">
            Attenzione: verranno eliminate anche tutte le sottocategorie e le transazioni associate!
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="deletingCategory = null">
            Annulla
          </button>
          <button type="button" class="btn btn-danger" @click="executeDeleteCategory">
            Elimina Definitivamente
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "CategoryManager",
  props: {
    initialCategories: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      categories: [...this.initialCategories],
      activeTab: "expense",
      showCategoryModal: false,
      editingCategory: null,
      deletingCategory: null,
      subFormNames: {},
      categoryForm: {
        name: "",
        type: "expense",
        icon: "📁",
        color: "#6366f1"
      },
      paletteColors: [
        "#3b82f6", "#10b981", "#f59e0b", "#ef4444", "#8b5cf6",
        "#ec4899", "#06b6d4", "#0d9488", "#6366f1", "#f43f5e",
        "#7c3aed", "#64748b", "#0284c7", "#84cc16"
      ],
      presetIcons: [
        "🏠", "🛒", "🍽️", "🚗", "💊", "🛍️", "✈️", "📱",
        "🎓", "⚡", "☕", "🍔", "🎁", "💼", "💻", "📈",
        "🏢", "🏷️", "🛠️", "⚽", "🎮", "📚", "🐶", "🏥"
      ]
    };
  },
  computed: {
    expenseCategories() {
      return this.categories.filter(c => c.type === "expense" || c.type === "both");
    },
    incomeCategories() {
      return this.categories.filter(c => c.type === "income" || c.type === "both");
    },
    currentTabCategories() {
      return this.activeTab === "expense" ? this.expenseCategories : this.incomeCategories;
    }
  },
  methods: {
    openCreateCategoryModal() {
      this.editingCategory = null;
      this.categoryForm = {
        name: "",
        type: this.activeTab,
        icon: this.activeTab === "expense" ? "🛒" : "💼",
        color: this.paletteColors[Math.floor(Math.random() * this.paletteColors.length)]
      };
      this.showCategoryModal = true;
    },
    openEditCategoryModal(cat) {
      this.editingCategory = cat;
      this.categoryForm = {
        name: cat.name,
        type: cat.type,
        icon: cat.icon || "📁",
        color: cat.color || "#6366f1"
      };
      this.showCategoryModal = true;
    },
    async saveCategory() {
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
      const url = this.editingCategory ? `/api/categories/${this.editingCategory.id}` : "/api/categories";
      const method = this.editingCategory ? "PUT" : "POST";

      try {
        const res = await fetch(url, {
          method: method,
          headers: {
            "Content-Type": "application/json",
            "Accept": "application/json",
            "X-CSRF-TOKEN": token
          },
          body: JSON.stringify(this.categoryForm)
        });

        const data = await res.json();
        if (res.ok && data.success) {
          if (this.editingCategory) {
            const idx = this.categories.findIndex(c => c.id === this.editingCategory.id);
            if (idx !== -1) {
              this.categories[idx] = { ...this.categories[idx], ...data.category };
            }
          } else {
            this.categories.push(data.category);
          }
          this.showCategoryModal = false;
        }
      } catch (err) {
        console.error("Errore salvataggio categoria:", err);
      }
    },
    async executeDeleteCategory() {
      if (!this.deletingCategory) return;
      const id = this.deletingCategory.id;
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");

      try {
        const res = await fetch(`/api/categories/${id}`, {
          method: "DELETE",
          headers: {
            "Accept": "application/json",
            "X-CSRF-TOKEN": token
          }
        });

        if (res.ok) {
          this.categories = this.categories.filter(c => c.id !== id);
          this.deletingCategory = null;
        }
      } catch (err) {
        console.error("Errore eliminazione categoria:", err);
      }
    },
    async addSubcategory(category) {
      const name = this.subFormNames[category.id];
      if (!name || !name.trim()) return;

      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");

      try {
        const res = await fetch(`/api/categories/${category.id}/subcategories`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "Accept": "application/json",
            "X-CSRF-TOKEN": token
          },
          body: JSON.stringify({ name: name.trim() })
        });

        const data = await res.json();
        if (res.ok && data.success) {
          if (!category.subcategories) {
            category.subcategories = [];
          }
          category.subcategories.push(data.subcategory);
          this.subFormNames[category.id] = "";
        }
      } catch (err) {
        console.error("Errore aggiunta sottocategoria:", err);
      }
    },
    async deleteSubcategory(subcategory) {
      if (!confirm(`Eliminare la sottocategoria "${subcategory.name}"?`)) return;

      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");

      try {
        const res = await fetch(`/api/subcategories/${subcategory.id}`, {
          method: "DELETE",
          headers: {
            "Accept": "application/json",
            "X-CSRF-TOKEN": token
          }
        });

        if (res.ok) {
          for (const cat of this.categories) {
            if (cat.subcategories) {
              cat.subcategories = cat.subcategories.filter(s => s.id !== subcategory.id);
            }
          }
        }
      } catch (err) {
        console.error("Errore eliminazione sottocategoria:", err);
      }
    }
  }
};
</script>