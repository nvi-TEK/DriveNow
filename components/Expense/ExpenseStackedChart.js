import React from "react";
import { Bar } from "react-chartjs-2";
import { useTheme } from "next-themes";
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
  const { resolvedTheme } = useTheme();
  const data = {
    labels: expenseStackData.map((data) => data.label),
    datasets: [
      {
        label: "",
        data: expenseStackData.map((data) => data.value1),
        backgroundColor: "#0076EC",
        barThickness: 32,
        borderWidth: 0.72,
        borderColor: "#0076EC",
      },
      {
        label: "",
        data: expenseStackData.map((data) => data.value2),
        backgroundColor: "#BDE6FF",
        barThickness: 32,
        borderWidth: 0.72,
        borderColor: "#0076EC",
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
          drawTicks: false,
        },
        ticks: {
          color: "#BFBFBF",
          padding: 4,
        },
        stacked: true,
      },
      y: {
        border: {
          display: false,
        },
        grid: {
          drawTicks: false,
          color: resolvedTheme === "dark" ? "#5C5C5C" : "rgba(0, 0, 0, 0.1)",
        },
        ticks: {
          stepSize: 90,
          color: "#BFBFBF",
          padding: 7,
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
