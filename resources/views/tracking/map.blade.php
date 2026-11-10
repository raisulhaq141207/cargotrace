@extends('layouts.app')

@section('title', 'Global World Tracking Map')
@section('header_title', 'Global Fleet Map')

@section('content')
<div class="space-y-4">

    <!-- Top Toolbar & Live Telemetry Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <!-- Left Title & Counters -->
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center text-lg shrink-0">
                <i class="fa-solid fa-earth-americas"></i>
            </div>
            <div>
                <h1 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <span>Global Shipment Visibility Map</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span> Live Fleet
                    </span>
                </h1>
                <p class="text-xs text-slate-400">Real-time worldwide marine corridors, port terminals &amp; cargo vessel tracking</p>
            </div>
        </div>

        <!-- Filter Buttons & Layer Switcher -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Filter Pills -->
            <div class="bg-slate-100 p-1 rounded-xl flex items-center text-xs font-semibold text-slate-600">
                <button type="button" onclick="filterMap('ALL')" id="btn-all" class="px-3 py-1 rounded-lg bg-white shadow-sm text-slate-900 transition">All ({{ $shipments->count() }})</button>
                <button type="button" onclick="filterMap('IN_TRANSIT')" id="btn-transit" class="px-3 py-1 rounded-lg hover:text-slate-900 transition text-sky-600">In Transit ({{ $inTransitCount }})</button>
                <button type="button" onclick="filterMap('DELAYED')" id="btn-delayed" class="px-3 py-1 rounded-lg hover:text-slate-900 transition text-rose-600">Delayed ({{ $delayedCount }})</button>
            </div>

            <!-- Layer Style Switcher -->
            <select id="mapTileSelector" onchange="changeMapLayer(this.value)" class="text-xs font-semibold px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 focus:outline-none">
                <option value="carto_light">Carto Light Map</option>
                <option value="osm">OpenStreetMap Standard</option>
                <option value="carto_dark">Dark Maritime Mode</option>
            </select>

            <!-- Reset View Button -->
            <button type="button" onclick="resetWorldView()" class="px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-700 transition" title="Reset World View">
                <i class="fa-solid fa-arrows-rotate mr-1"></i> Reset View
            </button>
        </div>

    </div>

    <!-- Main World Map Canvas (Full Viewport Height) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden relative" style="height: calc(100vh - 210px); min-height: 550px;">
        
        <!-- Leaflet Map Container -->
        <div id="worldMapCanvas" class="w-full h-full"></div>

        <!-- Floating Legend Overlay -->
        <div class="absolute bottom-5 left-5 z-[1000] bg-white/95 backdrop-blur-sm p-3.5 rounded-xl border border-slate-200/80 shadow-lg text-xs space-y-2 pointer-events-auto max-w-xs">
            <div class="font-bold text-[11px] text-slate-900 uppercase tracking-wider mb-1 flex items-center justify-between">
                <span>Map Legend</span>
                <span class="text-[9px] text-slate-400 font-normal">WGS84</span>
            </div>
            <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-[11px] text-slate-600">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-emerald-500 border border-white shrink-0"></span>
                    <span>Origin Hub</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-indigo-600 border border-white shrink-0"></span>
                    <span>Destination</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded-md bg-sky-500 text-white flex items-center justify-center text-[9px]"><i class="fa-solid fa-ship"></i></div>
                    <span>On Schedule</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded-md bg-rose-500 text-white flex items-center justify-center text-[9px]"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <span>Delayed Ship</span>
                </div>
            </div>
            <div class="pt-1.5 border-t border-slate-100 text-[10px] text-slate-400">
                Click any vessel or port pin for live telemetry &amp; voyage details.
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        
        // ─────────────────────────────────────────────────────────────────
        // 1. Initialize Leaflet World Map
        // ─────────────────────────────────────────────────────────────────
        const worldMap = L.map('worldMapCanvas', {
            zoomControl: true,
            minZoom: 2,
            maxZoom: 18,
            worldCopyJump: true
        }).setView([20.0, 10.0], 2.5); // Global view centering Atlantic / Europe / Asia

        // Tile layer definitions
        const tileLayers = {
            carto_light: L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                subdomains: 'abcd',
                maxZoom: 19
            }),
            osm: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18
            }),
            carto_dark: L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                subdomains: 'abcd',
                maxZoom: 19
            })
        };

        let currentLayer = tileLayers.carto_light.addTo(worldMap);

        window.changeMapLayer = function(layerName) {
            worldMap.removeLayer(currentLayer);
            currentLayer = tileLayers[layerName] || tileLayers.carto_light;
            currentLayer.addTo(worldMap);
        };

        window.resetWorldView = function() {
            worldMap.setView([20.0, 10.0], 2.5);
        };

        // ─────────────────────────────────────────────────────────────────
        // 2. Global Ports & Hubs
        // ─────────────────────────────────────────────────────────────────
        const locations = {!! json_encode($locations) !!};
        const portMarkers = L.layerGroup().addTo(worldMap);

        locations.forEach(loc => {
            if (!loc.latitude || !loc.longitude) return;

            const isPort = loc.type === 'PORT';
            const iconHtml = `<div style="background-color:#0f172a; width:18px; height:18px; border-radius:50%; border:2px solid #ffffff; box-shadow:0 1px 3px rgba(0,0,0,0.4); color:#ffffff; font-size:9px; display:flex; align-items:center; justify-content:center;">
                <i class="fa-solid ${isPort ? 'fa-anchor' : 'fa-warehouse'}"></i>
            </div>`;

            const icon = L.divIcon({
                className: 'custom-port-marker',
                html: iconHtml,
                iconSize: [18, 18],
                iconAnchor: [9, 9]
            });

            L.marker([loc.latitude, loc.longitude], { icon: icon })
                .bindPopup(`
                    <div style="font-family:'Plus Jakarta Sans',sans-serif; min-width:160px;">
                        <div style="font-weight:bold; font-size:12px; color:#0f172a;">${loc.name}</div>
                        <div style="font-size:10px; color:#64748b; margin-top:2px;">${loc.city}, ${loc.country} (${loc.code})</div>
                        <div style="margin-top:4px; font-size:9px; font-weight:bold; color:#0284c7; text-transform:uppercase;">${loc.type} TERMINAL</div>
                    </div>
                `)
                .addTo(portMarkers);
        });

        // ─────────────────────────────────────────────────────────────────
        // 3. Shipments, Ocean Routes & Active Vessels
        // ─────────────────────────────────────────────────────────────────
        const shipments = {!! json_encode($shipments) !!};
        const allShipmentLayers = [];

        shipments.forEach(s => {
            if (!s.origin || !s.destination) return;

            const isDelayed = s.is_delayed;
            const status = s.current_status;

            // Compute vessel position (latest event, or interpolated)
            const events = s.tracking_events || [];
            let vesselLat, vesselLng;

            if (events.length > 0 && events[events.length - 1].latitude && events[events.length - 1].longitude) {
                vesselLat = events[events.length - 1].latitude;
                vesselLng = events[events.length - 1].longitude;
            } else {
                vesselLat = (s.origin.latitude + s.destination.latitude) / 2;
                vesselLng = (s.origin.longitude + s.destination.longitude) / 2;
            }

            // Route points: Origin -> Waypoints -> Destination
            const routeCoords = [[s.origin.latitude, s.origin.longitude]];
            events.forEach(ev => {
                if (ev.latitude && ev.longitude) {
                    routeCoords.push([ev.latitude, ev.longitude]);
                }
            });
            routeCoords.push([s.destination.latitude, s.destination.longitude]);

            // Draw route polyline
            const lineColor = isDelayed ? '#ef4444' : (status === 'DELIVERED' ? '#10b981' : '#0284c7');
            const polyline = L.polyline(routeCoords, {
                color: lineColor,
                weight: isDelayed ? 3 : 2.5,
                opacity: 0.75,
                dashArray: status === 'DELIVERED' ? null : '6, 7',
                lineJoin: 'round'
            }).addTo(worldMap);

            // Vessel Marker (Ship Icon)
            const shipBg = isDelayed ? '#ef4444' : (status === 'DELIVERED' ? '#10b981' : '#0284c7');
            const shipIconHtml = `
                <div style="position:relative;">
                    <div style="background-color:${shipBg}; width:28px; height:28px; border-radius:8px; border:2px solid #ffffff; box-shadow:0 3px 6px rgba(0,0,0,0.3); color:#ffffff; font-size:12px; display:flex; align-items:center; justify-content:center;">
                        <i class="fa-solid fa-ship"></i>
                    </div>
                    ${isDelayed ? '<span style="position:absolute; top:-4px; right:-4px; width:10px; height:10px; background:#ef4444; border:2px solid #ffffff; border-radius:50%;"></span>' : ''}
                </div>`;

            const shipIcon = L.divIcon({
                className: 'custom-ship-marker',
                html: shipIconHtml,
                iconSize: [28, 28],
                iconAnchor: [14, 14]
            });

            const vesselMarker = L.marker([vesselLat, vesselLng], { icon: shipIcon })
                .bindPopup(`
                    <div style="font-family:'Plus Jakarta Sans',sans-serif; min-width:220px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-weight:bold; font-size:13px; color:#0f172a;">#${s.shipment_number}</span>
                            <span style="font-size:9px; font-weight:bold; padding:2px 6px; border-radius:4px; color:#ffffff; background:${shipBg};">${s.current_status}</span>
                        </div>
                        <div style="font-size:11px; color:#64748b; margin-top:2px;">Carrier: <b>${s.carrier ? s.carrier.name : '-'}</b></div>
                        
                        <div style="margin-top:6px; padding:6px; background:#f8fafc; border-radius:6px; font-size:10px; color:#334155;">
                            <div><b>Route:</b> ${s.origin.city} (${s.origin.code}) &rarr; ${s.destination.city} (${s.destination.code})</div>
                            <div style="margin-top:2px;"><b>Cargo:</b> ${s.cargo_type} • ${Number(s.weight).toLocaleString()} kg</div>
                            ${isDelayed ? `<div style="margin-top:2px; color:#e11d48; font-weight:bold;"><i class="fa-solid fa-clock-rotate-left"></i> Delayed +${Math.round(s.delay_duration_minutes/60)}h (${s.delay_severity})</div>` : '<div style="margin-top:2px; color:#10b981; font-weight:semibold;"><i class="fa-solid fa-circle-check"></i> On Schedule</div>'}
                        </div>

                        <div style="margin-top:8px; text-align:right;">
                            <a href="/shipments/${s.id}" style="font-size:11px; font-weight:bold; color:#0284c7; text-decoration:none;">
                                View Full Tracking &rarr;
                            </a>
                        </div>
                    </div>
                `)
                .addTo(worldMap);

            allShipmentLayers.push({
                shipment: s,
                polyline: polyline,
                marker: vesselMarker
            });
        });

        // ─────────────────────────────────────────────────────────────────
        // 4. Filtering Logic (All, In Transit, Delayed)
        // ─────────────────────────────────────────────────────────────────
        window.filterMap = function(filterType) {
            // Update button styles
            ['btn-all', 'btn-transit', 'btn-delayed'].forEach(id => {
                const btn = document.getElementById(id);
                if (btn) btn.className = 'px-3 py-1 rounded-lg hover:text-slate-900 transition';
            });

            if (filterType === 'ALL') {
                document.getElementById('btn-all').className = 'px-3 py-1 rounded-lg bg-white shadow-sm text-slate-900 transition font-bold';
            } else if (filterType === 'IN_TRANSIT') {
                document.getElementById('btn-transit').className = 'px-3 py-1 rounded-lg bg-sky-500 shadow-sm text-white transition font-bold';
            } else if (filterType === 'DELAYED') {
                document.getElementById('btn-delayed').className = 'px-3 py-1 rounded-lg bg-rose-500 shadow-sm text-white transition font-bold';
            }

            allShipmentLayers.forEach(item => {
                let show = true;
                if (filterType === 'IN_TRANSIT') {
                    show = item.shipment.current_status === 'IN_TRANSIT';
                } else if (filterType === 'DELAYED') {
                    show = item.shipment.is_delayed;
                }

                if (show) {
                    if (!worldMap.hasLayer(item.polyline)) worldMap.addLayer(item.polyline);
                    if (!worldMap.hasLayer(item.marker)) worldMap.addLayer(item.marker);
                } else {
                    if (worldMap.hasLayer(item.polyline)) worldMap.removeLayer(item.polyline);
                    if (worldMap.hasLayer(item.marker)) worldMap.removeLayer(item.marker);
                }
            });
        };

    });
</script>
@endpush

