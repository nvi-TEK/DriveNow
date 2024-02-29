import React from "react";
import { Chart as ChartJS, defaults, Tooltip } from "chart.js/auto";
import { Bar } from "react-chartjs-2";
import dashboardData from "./dashboardData.json";
import { options } from "../mainChart";

ChartJS.register(Tooltip);

export const DashChart = () => {
  return (
    <div className="h-[270px] w-[700px] ">
      <Bar
        data={{
          labels: dashboardData.map((data) => data.label),
          datasets: [
            {
              label: "",
              data: dashboardData.map((data) => data.value),
              backgroundColor: "#1F8FFF",
              borderRadius: 40,
              borderSkipped: false,
              barThickness: 14,
            },
          ],
        }}
        options={{
          plugins: {
    legend: {
      display: false
    }
   },
          interaction: {
            mode: "index",
          },
          scales: {
            x: {
              grid: {
                offset: true,
                display: false,
                drawOnChartArea: false,
                drawBorder: false,
              },
            },
            y: {
              ticks: {
                stepSize: 20,
              },
              grid: {
                display: false,
                offset: true,
                drawOnChartArea: false,
              },
            },
          },
        }}
      />
    </div>
  );
  const labels = Utils.months({ count: 7 });
  const data = {
    labels: labels,
    datasets: [
      {
        label: "My First Dataset",
        data: [65, 59, 80, 81, 56, 55, 40],
        backgroundColor: [
          "rgba(255, 99, 132, 0.2)",
          "rgba(255, 159, 64, 0.2)",
          "rgba(255, 205, 86, 0.2)",
          "rgba(75, 192, 192, 0.2)",
          "rgba(54, 162, 235, 0.2)",
          "rgba(153, 102, 255, 0.2)",
          "rgba(201, 203, 207, 0.2)",
        ],
        borderColor: [
          "rgb(255, 99, 132)",
          "rgb(255, 159, 64)",
          "rgb(255, 205, 86)",
          "rgb(75, 192, 192)",
          "rgb(54, 162, 235)",
          "rgb(153, 102, 255)",
          "rgb(201, 203, 207)",
        ],
        borderWidth: 1,
      },
    ],
  };

  const config = {
    type: "bar",
    data: data,
    options: {
      scales: {
        y: {
          beginAtZero: true,
        },
      },
    },
  };

  return (
    <div style={{ width: "780px", height: "780px" }}>
      <Bar data={data} options={options}></Bar>
    </div>
  );
};
