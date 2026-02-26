import { Doughnut, mixins } from "vue-chartjs";

export default {
  name: "pie-chart",
  extends: Doughnut,
  mixins: [mixins.reactiveProp],
  props: {
    extraOptions: Object,
  },
  data() {
    return {
      resizeTimer: null,
    };
  },
  watch: {
    chartData: {
      handler() {
        this.render();
      },
      deep: true,
    },
    extraOptions: {
      handler() {
        this.render();
      },
      deep: true,
    },
  },
  methods: {
    render() {
      if (!this.chartData) {
        return;
      }

      const nextData = JSON.parse(JSON.stringify(this.chartData));

      if (this.$data._chart) {
        this.$data._chart.destroy();
      }

      this.renderChart(nextData, {
        responsive: true,
        maintainAspectRatio: false,
        ...(this.extraOptions || {}),
      });
    },
    handleResize() {
      if (this.resizeTimer) {
        clearTimeout(this.resizeTimer);
      }

      this.resizeTimer = setTimeout(() => {
        this.render();
      }, 80);
    },
  },
  mounted() {
    this.render();
    window.addEventListener("resize", this.handleResize);
  },
  beforeDestroy() {
    window.removeEventListener("resize", this.handleResize);
    if (this.resizeTimer) {
      clearTimeout(this.resizeTimer);
    }
    if (this.$data._chart) {
      this.$data._chart.destroy();
    }
  },
};
