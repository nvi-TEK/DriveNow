import * as React from 'react';
import { BarChart } from '@mui/x-charts/BarChart';


const ExpensePaid = {
  data: [120, 290, 30, 105, 145, 115, 220, 60, 120, 175, 20, 160],
  label: 'Expense Paid',
};
const ExpenseNotPaid = {
  data: [0, 0, 165, 0, 0, 350, 0, 0, 0, 0, 240, 0],
  label: 'Expense Not Paid',
};

export default function ExpenseChart() {
  return (
    <BarChart
      width={710}
      height={600}
      series={[
        { ...ExpensePaid, stack: 'total' },
        { ...ExpenseNotPaid, stack: 'total' },
      ]}
    />
  );
}
