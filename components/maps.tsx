import { useState } from "react";
import { APIProvider, Map } from "@vis.gl/react-google-maps";

export default function Maps() {
  return (
    <div className="">
      <APIProvider apiKey={""}>
        <Map
          id="heatmap"
          style={{ width: "100%", height: "910px" }}
          defaultCenter={{ lat: 5.7348, lng: 0.0302 }}
          defaultZoom={15}
          gestureHandling={"greedy"}
          disableDefaultUI={true}
        />
      </APIProvider>
         
    </div>
  );
}
