# CargoTrace: Known Limitations & Future Work

As specified in the **Web Programming 2 — Final Project Brief (Slide 04 & 18)**, this document outlines deliberate scope boundaries, known engineering limitations of the current MVP, and planned future technical enhancements.

---

## 1. Known Architectural Limitations

### 1. External Telemetry Polling vs. Real-Time Physical GPS
- **Current Behavior**: Checkpoint coordinates are updated when logistics operators log historical milestones (`POST /api/shipments/{id}/tracking`) or through seed events.
- **Context**: Slide 04 explicitly defines *"Pelacakan GPS real-time dari perangkat fisik"* as outside mandatory scope.
- **Future Enhancement**: Integration with AIS (Automatic Identification System) maritime satellite feeds and IoT cellular transponders (e.g. MQTT broker + Redis pub/sub) for sub-minute vessel geofencing.

### 2. Weather Correlation vs. Machine Learning ETA
- **Current Behavior**: Deterministic mathematical delay calculation:
  $$\text{Delay} = \text{Reference Arrival} - \text{Expected Arrival}$$
  Weather telemetry is captured at each waypoint for operational situational awareness.
- **Context**: Slide 04 & 09 specify *"Machine learning/prediksi AI wajib: di luar scope"* and *"Jangan mengklaim weather sebagai bukti prediksi delay."*
- **Future Enhancement**: Training a Gradient Boosted Decision Tree (LightGBM) on historical weather severity, port dwell times, and seasonal maritime bottlenecks to dynamically predict probabilistic ETA curves.

### 3. Commercial Settlement & Port EDI Direct Feeds
- **Current Behavior**: Carriers, manifests, and HS tariff codes are managed within the relational database.
- **Context**: Slide 04 lists *"Integrasi langsung dengan pelabuhan, customs, atau carrier sungguhan"* and commercial payments as outside scope.
- **Future Enhancement**: Implementation of EDIFACT (EDI 310, 214) message exchange parser and integration with national single-window customs systems (INSW).

---

## 2. Planned Future Engineering Work

1. **Webhook Event Streaming**:
   - Implement webhook dispatcher so enterprise customer systems receive instant HTTP POST payloads upon status transitions and exception resolution.
2. **Multi-Leg Route Graph Optimization**:
   - Support Dijkstra/A* pathfinding algorithms across interconnected feeder hubs, transshipment ports, and railheads.
3. **Automated Bill of Lading (B/L) PDF Generation**:
   - Integrate Laravel DomPDF or Browsershot to generate downloadable multimodal shipping manifests.
4. **WebSocket Push Notifications**:
   - Integrate Laravel Reverb or Pusher for real-time live map marker movement without page refreshes.

