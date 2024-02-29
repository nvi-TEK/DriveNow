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
        data: [10, 16, 25, 20, 5, 40, 33, 18, 22, 50],
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
        cutout: '80%',
      },
    ],
  };

  const options = {
   plugins: {
    legend: {
      display: false
    }
   }
};

let sum = 0
  for (let i=0; i < 10; i++){
    sum += data.datasets[0].data[i]
  }


  const doughnutLabel = {
    id: "doughnutLabel",
    afterDatasetsDraw(chart, args, plugins) {
      const { ctx, data } = chart;

      const centerX = chart.getDatasetMeta(0).data[0].x;
      const centerY = chart.getDatasetMeta(0).data[0].y;

      ctx.save();
      ctx.font = "bold 40px";
      ctx.fillStyle = "black";
      ctx.textAlign = "center";
      ctx.fillText(`${sum} Total Expenses`, centerX, centerY);
    },
  };

  return (
    <div style={{ width: "200px", height: "200px" }}>
      <Doughnut
        data={data}
        options={options}
        plugins={[doughnutLabel]}
      ></Doughnut>
    </div>
  );
}
