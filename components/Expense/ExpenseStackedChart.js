import React from "react";
import { Bar } from "react-chartjs-2";
import expenseStackData from "../../components/Expense/expenseStackData.json";
import {
  Chart as ChartJS,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
} from "chart.js";

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip);

const ExpenseStack = () => {
  const data = {
    labels: expenseStackData.map((data) => data.label),
    datasets: [
      {
        label: "",
        data: expenseStackData.map((data) => data.value1),
        backgroundColor: "#0076EC",
      },
      {
        label: "",
        data: expenseStackData.map((data) => data.value2),
        backgroundColor: "#BDE6FF",
      },
    ],
  };

  const options = {
    plugins: {
        legend: {
          display: false
        }
       },
    interaction: {
        mode: 'index'
    },
    scales: {
      x: {
        grid: {
          drawOnChartArea: false,
        },
        stacked: true,
      },
      y: {
        grid: {},
        ticks: {
          stepSize: 90,
        },

        stacked: true,
      },
    },
  };

  return (
    <div className="h-[340px] w-[630px]">
      <Bar data={data} options={options} />
    </div>
  );
};

export default ExpenseStack;
