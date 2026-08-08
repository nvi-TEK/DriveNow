import { Chart as ChartJS, ArcElement, Tooltip, Legend } from "chart.js";
import { Doughnut } from "react-chartjs-2";
import { useTheme } from "next-themes";

ChartJS.register(ArcElement, Tooltip, Legend);

export default function DoughnutChart() {
  const { resolvedTheme } = useTheme();

  const getOrCreateLegendList = (chart, id) => {
    const legendContainer = document.getElementById(id);
    let listContainer = legendContainer.querySelector("ul");

    if (!listContainer) {
      listContainer = document.createElement("ul");
      listContainer.className - "listContainer";
      legendContainer.appendChild(listContainer);
    }
    return listContainer;
  };

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
          180000, 160000.29, 250000, 200000, 200000, 400000, 330000, 180000,
          240000, 500000,
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
        spacing: 3,
      },
    ],
  };


  const doughnutLabel = {
    id: "doughnutLabel",
    beforeDatasetsDraw(chart, args, pluginOptions) {
      const { ctx } = chart;

      const centerX = chart.getDatasetMeta(0).data[0].x;
      const centerY = chart.getDatasetMeta(0).data[0].y;

      const isDark = pluginOptions.resolvedTheme === "dark";

      ctx.save();

      ctx.font = "bold 16px Avenir";
      ctx.fillStyle = isDark ? "#FFFFFF" : "#262626";
      ctx.textAlign = "center";
      ctx.fillText(
        `₵${sum.toLocaleString(undefined, {
          maximumFractionDigits: 2,
          minimumFractionDigits: 2,
        })}`,
        centerX,
        centerY - 5
      );
      ctx.restore();

      ctx.font = "normal 14px Avenir";
      ctx.fillStyle = isDark ? "#B0B0B0" : "#8C8C8C";
      ctx.textAlign = "center";
      ctx.fillText("Total Expenses", centerX, centerY + 25);
      ctx.restore();
    },
  };

  const htmlLegendPlugin = {
    id: "htmlLegend",
    afterUpdate(chart, args, options) {
      const ul = getOrCreateLegendList(chart, options.containerID);

      while (ul.firstChild) {
        ul.firstChild.remove();
      }

      const items = chart.options.plugins.legend.labels.generateLabels(chart);

      items.forEach((item) => {
        const li = document.createElement("li");
        li.className = "li";

        const boxSpan = document.createElement("span");
        boxSpan.className = "boxSpan";

        const textContainer = document.createElement("p");
        textContainer.className = "textContainer";

        li.appendChild(boxSpan);
        li.appendChild(textContainer);

        ul.appendChild(li);
      });
    },
  };

  const options = {
    plugins: {
      legend: {
        display: false,
      },
      htmlLegend: {
        containerID: "legend-container",
      },
      doughnutLabel: {
        resolvedTheme,
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
        plugins={[doughnutLabel]}
      ></Doughnut>

      {/* <div id="legend-container"></div> */}
    </div>
  );
}
