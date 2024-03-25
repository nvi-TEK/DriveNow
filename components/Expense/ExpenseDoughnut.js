import { Chart as ChartJS, ArcElement, Tooltip, defaults } from "chart.js";
import { Doughnut } from "react-chartjs-2";

ChartJS.register(ArcElement, Tooltip);

export default function DoughnutChart() {
  const data = {
    labels: [
      "Car Insurance",
      "Deposit Refund",
      "Petty Cash",
      "Utilities",
      "Stationeries",
      "Employee Transportation",
      "System",
      "Staff Costs",
      "Driver Support",
      "General Vehicle Maintenance",
    ],
    datasets: [
      {
        label: "",
        data: [
          180000000, 160000000, 250000000, 200000000, 250000000, 400000000,
          330000000, 180000000, 240000000, 500000000,
        ],
        backgroundColor: [
          "#0076EC",
          "#6943FF",
          "#CD39E5",
          "#19C098",
          "#FFA723",
          "#0E7CFF",
          "#FF2C91",
          "#83E521",
          "#3FBBD7",
          "#FB3232",
        ],
        borderRadius: 80,
        borderWidth: 0,
        cutout: "80%",
      },
    ],
  };

  const doughnutLabel = {
    id: "doughnutLabel",
    beforeDatasetsDraw(chart, args, plugins, options, config) {
      const {
        ctx,
        data,
        chartArea: { top, bottom },
      } = chart;

      const centerX = chart.getDatasetMeta(0).data[0].x;
      const centerY = chart.getDatasetMeta(0).data[0].y;

      ctx.save();

      ctx.font = "bold 16px Avenir";
      ctx.fillStyle = "#262626";
      ctx.textAlign = "center";
      ctx.fillText(`₵${sum.toLocaleString()}`, centerX, centerY - 5);
      ctx.restore();

      ctx.font = "normal 14px Avenir";
      ctx.fillStyle = "#8C8C8C";
      ctx.textAlign = "center";
      ctx.fillText("Total Expenses", centerX, centerY + 25);
      ctx.restore();
    },
  };

  const config = {
    data,
    options: {},
    plugins: [doughnutLabel],
  };

  const options = {
    plugins: {
      legend: {
        display: false,
      },
    },
  };

  let sum = 0;
  for (let i = 0; i < data.datasets[0].data.length; i++) {
    sum += data.datasets[0].data[i];
  }

  return (
    <div id="doughnut">
      <Doughnut
        data={data}
        options={options}
        config={config}
        plugins={[doughnutLabel]}
      ></Doughnut>
    </div>
  );
}
