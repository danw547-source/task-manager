import { Bar, mixins } from "vue-chartjs";

export default {
  name: "bar-chart",
  extends: Bar,
  mixins: [mixins.reactiveProp],
  props: {
    extraOptions: Object,
    gradientColors: {
      type: Array,
      default: () => [
        "rgba(72,72,176,0.2)",
        "rgba(72,72,176,0.0)",
        "rgba(119,52,169,0)",
      ],
      validator: (val) => {
        return val.length > 2;
      },
    },
    gradientStops: {
      type: Array,
      default: () => [1, 0.4, 0],
      validator: (val) => {
        return val.length > 2;
      },
    },
  },
  data() {
    return {
      ctx: null,
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
    updateGradients(chartData) {
      if (!chartData) return;
      const ctx =
        this.ctx || document.getElementById(this.chartId).getContext("2d");
      const gradientStroke = ctx.createLinearGradient(0, 230, 0, 50);

      gradientStroke.addColorStop(
        this.gradientStops[0],
        this.gradientColors[0]
      );
      gradientStroke.addColorStop(
        this.gradientStops[1],
        this.gradientColors[1]
      );
      gradientStroke.addColorStop(
        this.gradientStops[2],
        this.gradientColors[2]
      );
      chartData.datasets.forEach((set) => {
        set.backgroundColor = gradientStroke;
      });
    },
    render() {
      if (!this.chartData) {
        return;
      }

      const nextData = JSON.parse(JSON.stringify(this.chartData));
      this.updateGradients(nextData);

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
