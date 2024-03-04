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
              display: false,
            },
          },
          interaction: {
            mode: "index",
          },
          scales: {
           x:{ 
              grid: {
                color: (context) => {
                  if(context.index === 0){
                    return '';
                  } else {
                    return 'rgba(102, 102, 102, 0.2)';
                  }
                },
                offset: true,
                display: true,
                drawOnChartArea: false,
                drawBorder: false,
                drawTicks: false
              }, 
              ticks:{
                display: true
              },
            },
            y: {
              ticks: {
                stepSize: 20,
                display: true
              },
              grid: {
                display: false,
                offset: true,
                drawOnChartArea: false,
                drawBorder: false,
                drawTicks: false
              },
            },
          },
        }}
      />
    </div>
  );
};
