<template>
  <div style="position: relative; width: 100%; height: 320px;">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script>
import { Chart, registerables } from "chart.js";
Chart.register(...registerables);

export default {
  name: "AnnualBarChart",
  props: {
    monthsData: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      chartInstance: null
    };
  },
  mounted() {
    this.renderChart();
  },
  beforeUnmount() {
    if (this.chartInstance) {
      this.chartInstance.destroy();
    }
  },
  methods: {
    renderChart() {
      if (!this.$refs.chartCanvas) return;

      const labels = this.monthsData.map(d => d.month_name);
      const incomes = this.monthsData.map(d => d.income);
      const expenses = this.monthsData.map(d => d.expense);

      const ctx = this.$refs.chartCanvas.getContext("2d");

      this.chartInstance = new Chart(ctx, {
        type: "bar",
        data: {
          labels: labels,
          datasets: [
            {
              label: "Entrate (€)",
              data: incomes,
              backgroundColor: "#10b981",
              borderRadius: 6,
              barPercentage: 0.6,
              categoryPercentage: 0.7
            },
            {
              label: "Spese (€)",
              data: expenses,
              backgroundColor: "#ef4444",
              borderRadius: 6,
              barPercentage: 0.6,
              categoryPercentage: 0.7
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: {
            mode: "index",
            intersect: false
          },
          plugins: {
            legend: {
              position: "top",
              align: "end",
              labels: {
                boxWidth: 12,
                boxHeight: 12,
                useBorderRadius: true,
                borderRadius: 3,
                font: {
                  family: "-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif",
                  size: 12,
                  weight: "500"
                },
                color: "#475569"
              }
            },
            tooltip: {
              backgroundColor: "#ffffff",
              titleColor: "#0f172a",
              bodyColor: "#334155",
              borderColor: "#e2e8f0",
              borderWidth: 1,
              padding: 10,
              boxPadding: 4,
              usePointStyle: true,
              callbacks: {
                label: function(context) {
                  let label = context.dataset.label || "";
                  if (label) {
                    label += ": ";
                  }
                  if (context.parsed.y !== null) {
                    label += Number(context.parsed.y).toLocaleString("it-IT", {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2
                    }) + " €";
                  }
                  return label;
                }
              }
            }
          },
          scales: {
            x: {
              grid: {
                display: false
              },
              ticks: {
                color: "#64748b",
                font: {
                  size: 12
                }
              }
            },
            y: {
              grid: {
                color: "#f1f5f9"
              },
              ticks: {
                color: "#64748b",
                font: {
                  size: 11
                },
                callback: function(value) {
                  return value.toLocaleString("it-IT") + " €";
                }
              }
            }
          }
        }
      });
    }
  }
};
</script>