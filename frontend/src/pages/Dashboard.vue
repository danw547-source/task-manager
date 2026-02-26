<template>
  <div>
    <div class="row">
      <div class="col-12">
        <card type="chart">
          <template slot="header">
            <div class="row">
              <div class="col-sm-6" :class="isRTL ? 'text-right' : 'text-left'">
                <h5 class="card-category">
                  {{ $t("dashboard.totalShipments") }}
                </h5>
                <h2 class="card-title">{{ $t("dashboard.performance") }}</h2>
              </div>
              <div class="col-sm-6 text-right">
                <user-filter-select
                  v-model="selectedUserId"
                  :is-admin="isAdmin"
                  all-label="All users"
                  storage-key="taskflow_dashboard_selected_user"
                  class="mb-2"
                  @change="onUserSelectionChange"
                  @users-loaded="onDashboardUsersLoaded"
                />
                <small class="text-muted">{{ $t("dashboard.performanceHint") }}</small>
              </div>
            </div>
          </template>
          <div class="dashboard-performance-chart">
            <line-chart
              ref="bigChart"
              chart-id="big-line-chart"
              :chart-data="bigLineChart.chartData"
              :gradient-colors="bigLineChart.gradientColors"
              :gradient-stops="bigLineChart.gradientStops"
              :extra-options="bigLineChart.extraOptions"
            >
            </line-chart>
          </div>
        </card>
      </div>
    </div>
    <div class="row" v-if="showNoDataBanner">
      <div class="col-12">
        <div class="dashboard-empty-state">
          No task data available for {{ selectedUserName }} in the selected period.
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-4 col-md-12" :class="{ 'text-right': isRTL }">
        <card type="chart">
          <template slot="header">
            <h5 class="card-category">{{ $t("dashboard.completionRate") }}</h5>
            <h3 class="card-title">
              <i class="tim-icons icon-chart-pie-36 text-info"></i>
              {{ totals.completion_rate }}%
            </h3>
          </template>
          <div class="dashboard-pie-chart">
            <pie-chart
              chart-id="completion-pie-chart"
              :chart-data="completionPieChart.chartData"
              :extra-options="completionPieChart.extraOptions"
            />
          </div>
        </card>
      </div>
      <div class="col-lg-8 col-md-12" :class="{ 'text-right': isRTL }">
        <card type="chart">
          <template slot="header">
            <h5 class="card-category">{{ $t("dashboard.totalShipments") }}</h5>
            <h3 class="card-title">
              <i class="tim-icons icon-bullet-list-67 text-primary"></i>
              {{ totals.total_tasks }}
            </h3>
          </template>
          <div class="dashboard-shipments-chart">
            <bar-chart
              chart-id="shipments-bar-chart"
              :chart-data="shipmentsChart.chartData"
              :gradient-colors="shipmentsChart.gradientColors"
              :gradient-stops="shipmentsChart.gradientStops"
              :extra-options="shipmentsChart.extraOptions"
            />
          </div>
        </card>
      </div>
    </div>
    <div class="row">
      <div class="col-12">
        <card class="card" :header-classes="{ 'text-right': isRTL }">
          <h4 slot="header" class="card-title">
            {{ $t("dashboard.tasksLeaderboard.title") }}
          </h4>
          <div class="table-responsive">
            <base-table
              :columns="leaderboardColumns"
              :data="leaderboardData"
              thead-classes="text-primary"
            />
          </div>
        </card>
      </div>
    </div>
  </div>
</template>
<script>
import LineChart from "@/components/Charts/LineChart";
import BarChart from "@/components/Charts/BarChart";
import PieChart from "@/components/Charts/PieChart";
import UserFilterSelect from "@/components/UserFilterSelect.vue";
import { BaseTable } from "@/components";
import * as chartConfigs from "@/components/Charts/config";
import { getDashboardSummary } from "@/services/dashboardService";
import { getStoredUser } from "@/services/authService";
import config from "@/config";

export default {
  components: {
    LineChart,
    BarChart,
    PieChart,
    UserFilterSelect,
    BaseTable,
  },
  data() {
    return {
      loading: false,
      currentUser: null,
      dashboardUsers: [],
      selectedUserId: 0,
      leaderboardData: [],
      totals: {
        total_tasks: 0,
        completed_tasks: 0,
        completion_rate: 0,
      },
      completionPieChart: {
        chartData: {
          labels: [],
          datasets: [],
        },
        extraOptions: {
          responsive: true,
          maintainAspectRatio: false,
          rotation: -0.5 * Math.PI,
          circumference: 2 * Math.PI,
          cutoutPercentage: 68,
          layout: {
            padding: {
              top: 8,
              bottom: 8,
            },
          },
          legend: {
            position: "bottom",
            labels: {
              fontColor: "#9a9a9a",
            },
          },
        },
      },
      shipmentsChart: {
        chartData: {
          labels: [],
          datasets: [],
        },
        extraOptions: {
          ...chartConfigs.barChartOptions,
          scales: {
            ...chartConfigs.barChartOptions.scales,
            yAxes: [
              {
                ...chartConfigs.barChartOptions.scales.yAxes[0],
                ticks: {
                  ...chartConfigs.barChartOptions.scales.yAxes[0].ticks,
                  beginAtZero: true,
                  suggestedMin: 0,
                  suggestedMax: 10,
                },
              },
            ],
          },
        },
        gradientColors: [
          "rgba(66, 184, 131, 0.85)",
          "rgba(66, 184, 131, 0.5)",
          "rgba(66, 184, 131, 0.2)",
        ],
        gradientStops: [1, 0.4, 0],
      },
      bigLineChart: {
        chartData: {
          datasets: [],
          labels: [],
        },
        extraOptions: {
          ...chartConfigs.purpleChartOptions,
          scales: {
            ...chartConfigs.purpleChartOptions.scales,
            yAxes: [
              {
                ...chartConfigs.purpleChartOptions.scales.yAxes[0],
                ticks: {
                  ...chartConfigs.purpleChartOptions.scales.yAxes[0].ticks,
                  suggestedMin: 0,
                  suggestedMax: 30,
                },
              },
            ],
          },
        },
        gradientColors: config.colors.primaryGradient,
        gradientStops: [1, 0.4, 0],
      },
    };
  },
  computed: {
    isRTL() {
      return this.$rtl.isRTL;
    },
    isAdmin() {
      return this.currentUser?.role === "admin";
    },
    selectedUserName() {
      if (!this.selectedUserId) {
        return "all users";
      }

      const selected = this.dashboardUsers.find((user) => user.id === this.selectedUserId);
      return selected?.name || "selected user";
    },
    showNoDataBanner() {
      return this.isAdmin && !this.loading && this.selectedUserId > 0 && this.totals.total_tasks === 0;
    },
    leaderboardColumns() {
      return this.$t("dashboard.tasksLeaderboard.columns");
    },
  },
  methods: {
    onDashboardUsersLoaded(users) {
      this.dashboardUsers = users;
    },
    async onUserSelectionChange() {
      await this.loadDashboardSummary();
    },
    async loadDashboardSummary() {
      this.loading = true;

      try {
        const params = {
          months: 12,
          ...(this.isAdmin && this.selectedUserId
            ? { user_id: this.selectedUserId }
            : {}),
        };

        let summary;

        try {
          summary = await getDashboardSummary(params);
        } catch (error) {
          if (!(this.isAdmin && this.selectedUserId)) {
            throw error;
          }

          this.selectedUserId = 0;
          summary = await getDashboardSummary({ months: 12 });
        }

        this.totals = summary?.totals || this.totals;

        const totalTasks = Number(this.totals.total_tasks || 0);
        const completedTasks = Number(this.totals.completed_tasks || 0);
        const outstandingTasks = Math.max(0, totalTasks - completedTasks);

        this.leaderboardData = Array.isArray(summary?.leaderboard)
          ? summary.leaderboard.map((row) => ({
              user: row.name,
              email: row.email,
              tasks: row.tasks,
              completed: row.completed,
              outstanding: row.outstanding,
            }))
          : [];

        const labels = summary?.labels || [];
        const estimated = summary?.performance?.estimated_completion_days || [];
        const actual = summary?.performance?.actual_completion_days || [];
        const monthlyTotals = summary?.monthly?.total_tasks || [];
        const maxPoint = Math.max(...estimated, ...actual, 1);
        const maxMonthlyTotal = Math.max(...monthlyTotals, 1);

        this.bigLineChart.extraOptions.scales.yAxes[0].ticks.suggestedMax =
          Math.max(2, Math.ceil(maxPoint * 1.25));

        this.shipmentsChart.extraOptions.scales.yAxes[0].ticks.suggestedMax =
          Math.max(2, Math.ceil(maxMonthlyTotal * 1.25));

        this.completionPieChart.chartData = {
          labels: [
            this.$t("dashboard.completedTasks"),
            this.$t("dashboard.outstandingTasks"),
          ],
          datasets: [
            {
              data: [completedTasks, outstandingTasks],
              backgroundColor: [config.colors.primary, config.colors.info],
              borderColor: [config.colors.primary, config.colors.info],
              borderWidth: 1,
            },
          ],
        };

        this.shipmentsChart.chartData = {
          labels,
          datasets: [
            {
              label: this.$t("dashboard.chartLabels.totalTasks"),
              fill: true,
              borderColor: config.colors.primary,
              borderWidth: 2,
              borderDash: [],
              borderDashOffset: 0.0,
              data: monthlyTotals,
            },
          ],
        };

        const chartData = {
          labels,
          datasets: [
            {
              label: this.$t("dashboard.chartLabels.estimated"),
              fill: true,
              borderColor: config.colors.primary,
              borderWidth: 2,
              borderDash: [],
              borderDashOffset: 0.0,
              pointBackgroundColor: config.colors.primary,
              pointBorderColor: "rgba(255,255,255,0)",
              pointHoverBackgroundColor: config.colors.primary,
              pointBorderWidth: 20,
              pointHoverRadius: 4,
              pointHoverBorderWidth: 15,
              pointRadius: 3,
              data: estimated,
            },
            {
              label: this.$t("dashboard.chartLabels.actual"),
              fill: false,
              borderColor: config.colors.info,
              borderWidth: 2,
              borderDash: [6, 4],
              borderDashOffset: 0.0,
              pointBackgroundColor: config.colors.info,
              pointBorderColor: "rgba(255,255,255,0)",
              pointHoverBackgroundColor: config.colors.info,
              pointBorderWidth: 20,
              pointHoverRadius: 4,
              pointHoverBorderWidth: 15,
              pointRadius: 3,
              data: actual,
            },
          ],
        };

        if (this.$refs.bigChart) {
          this.$refs.bigChart.updateGradients(chartData);
        }

        this.bigLineChart.chartData = chartData;
      } catch (error) {
        this.totals = {
          total_tasks: 0,
          completed_tasks: 0,
          completion_rate: 0,
        };
        this.leaderboardData = [];
      } finally {
        this.loading = false;
      }
    },
  },
  async mounted() {
    this.currentUser = getStoredUser();
    await this.loadDashboardSummary();
  },
};
</script>
<style scoped>
.dashboard-empty-state {
  border-radius: 0.4rem;
  padding: 0.75rem 1rem;
  margin: 0.25rem 0 0.5rem;
  background: rgba(29, 140, 248, 0.12);
  border: 1px solid rgba(29, 140, 248, 0.35);
  color: rgba(255, 255, 255, 0.86);
  font-size: 0.85rem;
}

.dashboard-performance-chart {
  position: relative;
  width: min(100%, 980px);
  min-width: 0;
  margin: 0 auto;
  height: 260px;
  max-height: 260px;
}

.dashboard-pie-chart {
  position: relative;
  width: min(100%, 220px);
  min-width: 0;
  margin: 0 auto;
  height: 220px;
  max-height: 220px;
}

.dashboard-shipments-chart {
  position: relative;
  width: min(100%, 980px);
  min-width: 0;
  margin: 0 auto;
  height: 260px;
  max-height: 260px;
}

.dashboard-performance-chart ::v-deep canvas,
.dashboard-pie-chart ::v-deep canvas,
.dashboard-shipments-chart ::v-deep canvas {
  display: block;
  width: 100% !important;
  height: 100% !important;
}
</style>
