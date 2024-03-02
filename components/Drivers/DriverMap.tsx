
import  { useState} from "react"
import { APIProvider, Map } from "@vis.gl/react-google-maps";

export default function DriverMap() {
  return (
    <div className="">
      <APIProvider apiKey={""}>
        <Map style={{ width: "100%", height: "365px" }}
          defaultCenter={{ lat: 5.5593, lng: 0.1974 }}
          defaultZoom={10}
          gestureHandling={"greedy"}
          disableDefaultUI={true}
        />
      </APIProvider>
    </div>
  );
}