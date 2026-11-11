<?php

namespace Database\Seeders;

use App\Models\News;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first();
        $adminId = $admin?->id ?? 1;

        $articles = [
            [
                'title' => 'Suez Canal Authority Issues New Vessel Speed & Navigational Advisory',
                'slug' => 'suez-canal-authority-new-speed-navigational-advisory',
                'category' => 'PORT_ADVISORY',
                'is_breaking' => true,
                'summary' => 'SCA implements updated convoy transit regulations and adjusted toll surcharges for ultra-large container vessels (ULCV) navigating northbound passages.',
                'content' => "The Suez Canal Authority (SCA) announced this morning that revised navigational guidelines will take effect immediately across all convoy corridors. Under the directive, container vessels with draft exceeding 14.5 meters must maintain a mandatory escort tugboat throughout the Great Bitter Lake sector.\n\nKey takeaways for freight forwarders and ocean carriers:\n- Northbound convoy transit departure slots adjusted by +45 minutes to enhance separation distances.\n- Bunker fuel sulfur validation inspections will be conducted at Port Said outer anchorage.\n- Average transit times are projected to increase by approximately 90 minutes per convoy, but maritime safety margins are substantially improved.\n\nAll CargoTrace operators monitoring Europe-Asia trade routes (including shipments transiting between Southeast Asia and Mediterranean gateways) are advised to review updated vessel ETA recalculations.",
                'image_url' => 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=800&auto=format&fit=crop&q=60',
                'author_id' => $adminId,
                'is_published' => true,
                'published_at' => Carbon::now()->subHours(2),
            ],
            [
                'title' => 'Severe Monsoon Advisory: Gale Force Winds Across Malacca & Singapore Straits',
                'slug' => 'severe-monsoon-advisory-malacca-singapore-straits',
                'category' => 'WEATHER_ALERT',
                'is_breaking' => true,
                'summary' => 'Maritime weather services have issued Gale Warning level 7 across the Malacca Strait corridor with wave heights reaching 3.5 meters.',
                'content' => "Heavy tropical monsoon troughs have intensified over the southern reaches of the South China Sea and the Strait of Malacca. Regional maritime safety agencies report continuous rainfall, reduced visibility below 2 nautical miles, and wave swells ranging between 2.5 and 3.5 meters.\n\nOperational guidelines:\n- Feeder vessels and coastal barges operating between Tanjung Priok, Port Klang, and Port of Singapore should exercise heightened navigational caution.\n- Reefer containers and high-value fragile cargo stowage plans must be double-lashed before departing berths.\n- CargoTrace Weather Telemetry Engine is continuously polling meteorological coordinates to adjust ETA estimates automatically for vessels en route.",
                'image_url' => 'https://images.unsplash.com/photo-1509316975850-ff9c5deb0cd9?w=800&auto=format&fit=crop&q=60',
                'author_id' => $adminId,
                'is_published' => true,
                'published_at' => Carbon::now()->subHours(5),
            ],
            [
                'title' => 'Port of Rotterdam Unveils Next-Gen Automated AI Container Inspection Terminal',
                'slug' => 'port-of-rotterdam-next-gen-automated-container-inspection',
                'category' => 'PORT_ADVISORY',
                'is_breaking' => false,
                'summary' => 'Maasvlakte 2 terminal expands automated gate turnaround, reducing customs clearance wait times for international containerized freight by up to 35%.',
                'content' => "Europe's largest seaport, the Port of Rotterdam, has completed the commissioning of its state-of-the-art automated optical container inspection portal at Maasvlakte 2. The high-throughput x-ray scanner and automated optical character recognition (OCR) systems enable full customs and biosecurity screening while trucks move at speeds of up to 15 km/h.\n\nBenefits to international shippers:\n- Dwell times for import containers bound for European hinterland destinations drop from 48 hours to under 26 hours on average.\n- Automatic electronic release notifications are now dispatched directly to integrated logistics platforms, reducing documentation holds.\n- Green corridor priority lanes have been established for certified sustainable and carbon-audited freight shipments.",
                'image_url' => 'https://images.unsplash.com/photo-1578575437130-527eed3abbec?w=800&auto=format&fit=crop&q=60',
                'author_id' => $adminId,
                'is_published' => true,
                'published_at' => Carbon::now()->subDays(1),
            ],
            [
                'title' => 'IMO 2026 Maritime Carbon Intensity Index (CII) Enforcement Takes Full Effect',
                'slug' => 'imo-2026-maritime-carbon-intensity-index-enforcement',
                'category' => 'REGULATORY',
                'is_breaking' => false,
                'summary' => 'International Maritime Organization enacts stricter operational energy efficiency thresholds; older container vessels face speed reductions and route optimizations.',
                'content' => "The International Maritime Organization (IMO) has commenced phase 2 enforcement of the Carbon Intensity Indicator (CII) framework. Under this regime, commercial cargo vessels over 5,000 gross tonnage receive annual operational ratings from A to E based on grams of CO2 emitted per cargo-carrying capacity and nautical mile.\n\nIndustry impact:\n- Vessels receiving D or E ratings for consecutive years must submit formal corrective action plans, primarily involving 'slow steaming' (reducing speed by 1 to 2 knots).\n- Average ocean transit schedules across Trans-Pacific and Far East–Europe corridors will see 1 to 2 days added to baseline transit times.\n- CargoTrace EtaService incorporates these revised speed profiles when computing expected arrival milestones.",
                'image_url' => 'https://images.unsplash.com/photo-1518241353330-0f7941c2d9b5?w=800&auto=format&fit=crop&q=60',
                'author_id' => $adminId,
                'is_published' => true,
                'published_at' => Carbon::now()->subDays(2),
            ],
            [
                'title' => 'Ocean Alliance & Major Carriers Announce Q4 Bunker Surcharge Restructuring',
                'slug' => 'ocean-alliance-major-carriers-q4-bunker-surcharge',
                'category' => 'CARRIER_UPDATE',
                'is_breaking' => false,
                'summary' => 'Major global carriers including Maersk, CMA CGM, and MSC align Low Sulfur Fuel (LSF) adjustments across East-West trade routes.',
                'content' => "Following fluctuations in marine gasoil (MGO) and very low sulfur fuel oil (VLSFO) pricing across major bunkering hubs including Singapore and Rotterdam, major ocean alliances have announced revised bunker adjustment factors (BAF) for the forthcoming quarter.\n\nCarriers emphasize that automated logistics platforms offering real-time tracking and exception visibility allow logistics managers to offset surcharges by optimizing port dwell times and avoiding costly demurrage fees.",
                'image_url' => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=800&auto=format&fit=crop&q=60',
                'author_id' => $adminId,
                'is_published' => true,
                'published_at' => Carbon::now()->subDays(3),
            ],
            [
                'title' => 'Trans-Pacific Trade Lane: Port of Los Angeles & Long Beach Report Record Berth Efficiency',
                'slug' => 'trans-pacific-trade-lane-la-long-beach-record-berth-efficiency',
                'category' => 'TRADE_LANE',
                'is_breaking' => false,
                'summary' => 'San Pedro Bay ports clear historical container backlogs; vessel waiting times drop to zero days at anchor for scheduled container liner services.',
                'content' => "The twin ports of Los Angeles and Long Beach have announced record-setting cargo processing metrics for the latest quarter. Through coordinated rail-dock synchronization and extended weekend gate hours, vessel congestion has been fully mitigated.\n\nHighlights for shippers:\n- Zero container ships waiting at outer anchorage—berthing on arrival is now standard.\n- Intermodal rail dwell times reduced to an average of 3.8 days.\n- Shippers importing from Southeast Asia into US West Coast corridors experience predictable transit timetables.",
                'image_url' => 'https://images.unsplash.com/photo-1574044566945-815ca0be7e04?w=800&auto=format&fit=crop&q=60',
                'author_id' => $adminId,
                'is_published' => true,
                'published_at' => Carbon::now()->subDays(4),
            ],
        ];

        foreach ($articles as $art) {
            News::updateOrCreate(
                ['slug' => $art['slug']],
                $art
            );
        }
    }
}

