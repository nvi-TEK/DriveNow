import React from "react";
import { Bar } from "react-chartjs-2";
import dashboardStackData from "../components/dashboard/dashboardStackData.json";
import {
  Chart as ChartJS,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
} from "chart.js";

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip);

const DashboardStack = () => {
  const data = {
    labels: dashboardStackData.map((data) => data.label),
    datasets: [
      {
        label: "",
        data: dashboardStackData.map((data) => data.value1),
        backgroundColor: "#A6D2FF",
      },
      {
        label: "",
        data: dashboardStackData.map((data) => data.value2),
        backgroundColor: "#1F8FFF",
      },
      {
        label: "",
        data: dashboardStackData.map((data) => data.value3),
        backgroundColor: "#D52D4D",
      },
      {
        label: "",
        data: dashboardStackData.map((data) => data.value4),
        backgroundColor: "#FFDBE2",
      },
    ],
  };

  const options = {
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false,
      },
    },
    interaction: {
      mode: "index",
    },
    scales: {
      x: {
        grid: {
          drawOnChartArea: false,
        },
        ticks: {
          color: "#BFBFBF",
        },

        stacked: true,
      },
      y: {
        grid: {},
        ticks: {
          stepSize: 90,
          color: "#BFBFBF",
        },

        stacked: true,
      },
    },
  };

  return (
    <div className="max-2xl:h-[360px] h-[450px] w-full px-[30px] ">
      <Bar data={data} options={options} />
    </div>
  );
};

export default DashboardStack;
