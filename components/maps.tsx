import { useState } from "react";
import { APIProvider, Map } from "@vis.gl/react-google-maps";

export default function Maps() {
  return (
    <div className="m-0">
      <APIProvider apiKey={""}>
        <Map
          style={{ width: "100%", height: "768px" }}
          defaultCenter={{ lat: 5.5593, lng: 0.1974 }}
          defaultZoom={12}
          gestureHandling={"greedy"}
          disableDefaultUI={true}
        />
      </APIProvider>
         
    </div>
  );
}
