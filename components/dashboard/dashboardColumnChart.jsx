import React from "react";
import { Chart as ChartJS, defaults, Tooltip } from "chart.js/auto";
import { Bar } from "react-chartjs-2";
import { useTheme } from "next-themes";
import dashboardData from "./dashboardData.json";

ChartJS.register(Tooltip);

export default function DashChart() {
  const { resolvedTheme } = useTheme();

  const data = {
    labels: dashboardData.map((data) => data.label),
    datasets: [
      {
        label: "",
        data: dashboardData.map((data) => data.value),
        backgroundColor: "#1F8FFF",
        borderRadius: 40,
        borderSkipped: false,
        barThickness: 13,
      },
    ],
  };

  const options = {
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false,
      },
      backgroundBar: {
        resolvedTheme,
      },
    },
    interaction: {
      mode: "index",
    },

    scales: {
      x: {
        border: {
          display: false,
        },
        grid: {
          offset: true,

          display: false,
          drawOnChartArea: true,
          drawBorder: false,
          drawTicks: false,
        },
        ticks: {
          display: true,
          color: "#BFBFBF",
        },
      },
      y: {
        border: {
          display: false,
        },
        ticks: {
          stepSize: 20,
          color: "#BFBFBF",
          display: true,
        },
        grid: {
          display: false,

          offset: true,
          drawOnChartArea: false,
          drawborder: false,
          drawTicks: false,
        },
      },
    },
  };

  const backgroundBar = {
    id: "backgroundBar",
    beforeDatasetsDraw(chart, args, pluginOptions) {
      const {
        ctx,
        chartArea: { top, bottom, height },
        scales: { x, y },
      } = chart;

      ctx.beginPath();
      const width = chart.getDatasetMeta(0).data[0].width;
      ctx.fillStyle = pluginOptions.resolvedTheme === "dark" ? "#2A2A2A" : "#F1F8FF";
      chart.getDatasetMeta(0).data.forEach((dataPoint, index) => {
        ctx.roundRect(
          x.getPixelForValue(index) - width / 2,
          top,
          width,
          height,
          40
        );
        ctx.fill();
      });

      // ctx.save();
      // const segment = width / data.labels.length;
      // const barWidth =
      //   segment *
      //   data.datasets[0].barPercentage *
      //   data.datasets[0].categoryPercentage;
      // ctx.fillStyle = "gray";
      // for (let i = 0; i < data.labels.length; i++) {
      //   ctx.fillRect(
      //     x.getPixelForValue(i) - barWidth / 2,
      //     top,
      //     barWidth,
      //     height
      //   );
      // }
    },
  };

  return (
    <div className="h-[260px] max-2xl:h-[235px] w-full px-4 pb-3">
      <Bar data={data} options={options} plugins={[backgroundBar]}></Bar>
    </div>
  );
}
