import "./bootstrap";
import { createApp } from "vue";

import TransactionModal from "./components/TransactionModal.vue";
import TransactionManager from "./components/TransactionManager.vue";
import CategoryManager from "./components/CategoryManager.vue";
import TrendChart from "./components/TrendChart.vue";
import AnnualBarChart from "./components/AnnualBarChart.vue";

const app = createApp({
  methods: {
    openNewTransactionModal(type = "expense") {
      if (this.$refs.transactionModal) {
        this.$refs.transactionModal.openNew(type);
      }
    },
    editTransaction(transaction) {
      if (this.$refs.transactionModal) {
        this.$refs.transactionModal.edit(transaction);
      }
    },
    onTransactionSaved(transaction) {
      window.dispatchEvent(new CustomEvent("transaction-saved", { detail: transaction }));
    }
  }
});

app.component("transaction-modal", TransactionModal);
app.component("transaction-manager", TransactionManager);
app.component("category-manager", CategoryManager);
app.component("trend-chart", TrendChart);
app.component("annual-bar-chart", AnnualBarChart);

const mountedApp = app.mount("#app");
window.appInstance = mountedApp;
