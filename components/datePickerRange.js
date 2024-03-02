/* eslint-disable react/no-unescaped-entities */
/* eslint-disable require-jsdoc */
import * as React from 'react';
import Calendar from '@mui/icons-material/Event';
import { LocalizationProvider } from '@mui/x-date-pickers/LocalizationProvider';
import { AdapterDayjs } from '@mui/x-date-pickers/AdapterDayjs';
import { DateRangePicker } from '@mui/x-date-pickers-pro/DateRangePicker';
import { SingleInputDateRangeField } from '@mui/x-date-pickers-pro/SingleInputDateRangeField';
import { DemoContainer } from '@mui/x-date-pickers/internals/demo';

export default function SingleInputDateRangePickerWithAdornment() {
  return (
    <LocalizationProvider dateAdapter={AdapterDayjs}>
      <DemoContainer components={['SingleInputDateRangeField']}>
        <DateRangePicker
          slots={{ field: SingleInputDateRangeField }}
          slotProps={{ textField: { InputProps: { endAdornment: <Calendar /> } } }}
        />
      </DemoContainer>
    </LocalizationProvider>
  );
}



// const WrappedSingleInputDateRangeField = React.forwardRef((props, ref) => {
//   return <SingleInputDateRangeField size="small" {...props} ref={ref} />;
// });

// WrappedSingleInputDateRangeField.fieldType = "single-input";

// export default function WrappedSingleInputDateRangePicker() {
//   return (
//     <LocalizationProvider dateAdapter={AdapterDayjs}>
//       <DemoContainer components={["SingleInputDateRangeField"]}>
//         <DateRangePicker
//           slotProps={{
//             textField: {
//               InputProps: { endAdornment: <Calendar />, size: "small" },
//               placeholder: "Start - End",
//             },
//           }}
//           calendars={1}
//           slots={{ field: WrappedSingleInputDateRangeField }}
//         />
//       </DemoContainer>
//     </LocalizationProvider>
//   );
// }
