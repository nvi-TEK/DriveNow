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
        barThickness: 32,
      },
      {
        label: "",
        data: expenseStackData.map((data) => data.value2),
        backgroundColor: "#BDE6FF",
        barThickness: 32,
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
          color: "#585858",
        },
        stacked: true,
      },
      y: {
        grid: {},
        ticks: {
          stepSize: 90,
          color: "#585858",
        },

        stacked: true,
      },
    },
  };

  return (
    <div className="h-[360px] max-2xl:h-[340px] w-full">
      <Bar data={data} options={options} />
    </div>
  );
};

export default ExpenseStack;
