"use client";

import { useEffect } from "react";
import L from "leaflet";
import { MapContainer, Marker, TileLayer } from "react-leaflet";
import "leaflet/dist/leaflet.css";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";

// Les URL d'icônes par défaut de Leaflet ne survivent pas au bundling
// Next.js — remplacement standard par les assets importés directement.
function useDefaultMarkerIcon() {
  useEffect(() => {
    // @ts-expect-error accès à une méthode privée retirée intentionnellement
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
      iconRetinaUrl: markerIcon2x.src,
      iconUrl: markerIcon.src,
      shadowUrl: markerShadow.src,
    });
  }, []);
}

export function CompanyLeafletMap({
  latitude,
  longitude,
}: {
  latitude: number;
  longitude: number;
}) {
  useDefaultMarkerIcon();

  return (
    <MapContainer
      center={[latitude, longitude]}
      zoom={15}
      scrollWheelZoom={false}
      style={{ height: "280px", width: "100%", borderRadius: "var(--radius-chip)" }}
    >
      <TileLayer
        attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        url={process.env.NEXT_PUBLIC_MAP_TILE_URL!}
      />
      <Marker position={[latitude, longitude]} />
    </MapContainer>
  );
}
