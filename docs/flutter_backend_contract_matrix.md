# Flutter / Laravel contract matrix

This matrix is derived from active Flutter forms and the Laravel API boundary.
`FIXED` means the field is passed through the current mobile Boat flow.

| Module | Screen | UI label | Flutter property | Flutter type | Request JSON | Laravel rule | DB column | Resource key | Response property | Status |
|---|---|---|---|---|---|---|---|---|---|---|
| Boats | Register / Edit Boat | Boat name | `name` | `String` | `name` | required string max:120 | `name` | `name` | `name` | MATCH |
| Boats | Register / Edit Boat | Registration number | `registration` | `String` | `registration_number` | required unique string max:50 | `registration_number` | `registration_number` | `registration` | MATCH |
| Boats | Register / Edit Boat | Length (m) | `lengthMetres` | `double` | `length_meters` | required numeric gt:0 | `length_meters` decimal(8,2), nullable legacy | `length_meters` | `lengthMetres` | FIXED |
| Boats | Register / Edit Boat | Boat type | `type` | `String` | `type` | required `LONG_LINER`, `GILLNETTER`, `DAY_BOAT` | `type` | `type` | `type` | FIXED |
| Boats | Register / Edit Boat | Engine details | `engineDetails` | `String` | `engine_details` | required string max:160 | `engine_details` varchar(160), nullable legacy | `engine_details` | `engineDetails` | FIXED |
| Boats | Register / Edit Boat | Home port | `homePort` | `String` | `home_port` | required string max:120 | `home_port` varchar(120), nullable legacy | `home_port` | `homePort` | FIXED |
| Boats | Register / Edit Boat | Boat is active | `active` | `bool` | `is_active` | sometimes boolean | `is_active` boolean | `is_active` | `active` | MATCH |
| Boats | Existing API/admin | Capacity | — | — | `capacity_kg` | nullable numeric gt:0 | `capacity_kg` decimal(12,3) | `capacity_kg` | — | BACKEND_ONLY / documented |

## Enum matrix

| Domain | Flutter serialization | Laravel validation | Database | Status |
|---|---|---|---|---|
| Boat type | `LONG_LINER` | `LONG_LINER` | string | FIXED |
| Boat type | `GILLNETTER` | `GILLNETTER` | string | FIXED |
| Boat type | `DAY_BOAT` | `DAY_BOAT` | string | FIXED |

## Fisher workflow fields

| Module | Screen | UI label | Flutter property/payload | Request JSON | Database target | Resource key | Status |
|---|---|---|---|---|---|---|---|
| Trips | Start Trip | Trip Code | `tripCode` | `trip_code` | `fishing_trips.trip_code` | `trip_code` | FIXED |
| Trips | Start Trip | Departure Date & Time | `departure` | `planned_departure_at` | `fishing_trips.planned_departure_at` | `planned_departure_at` | FIXED |
| Trips | Start Trip | Expected Duration | `expectedHours` | `expected_duration_hours` | `fishing_trips.expected_duration_hours` | `expected_duration_hours` | FIXED |
| Trips | Start Trip | Fishing Area | latitude/longitude | `fishing_area_latitude`, `fishing_area_longitude` | matching decimal columns | matching keys | FIXED |
| Trips | Start Trip | Crew Members | `crew` | `crew[]` | `fishing_trip_crew_members` | `crew[]` | FIXED |
| Trips | Start Trip | Notes | `notes` | `notes` | `fishing_trips.notes` | `notes` | FIXED |
| Catches | Add Catch | Condition | `condition` | `condition` (`EXCELLENT`, `GOOD`, `FAIR`) | `catch_records.condition` | `condition` | FIXED |
| Catches | Add Catch | GPS location | latitude/longitude | `latitude`, `longitude` | matching decimal columns | matching keys | FIXED |
| Catches | Add Catch | Notes | `notes` | `notes` | `catch_records.notes` | `notes` | FIXED |
| Catches | Add Catch | Photos | local paths | multipart file after catch create | `file_assets` | `images[]` | FIXED |
| Batches | Create Batch | Processing / Product Type | `processingType` | `product_type` | `fish_batches.product_type` | `product_type` | FIXED |
| Batches | Create Batch | Quality Grade | `qualityGrade` | `quality_grade` | `fish_batches.quality_grade` | `quality_grade` | FIXED |
| Batches | Create Batch | Storage Temp. (°C) | `storageTemperature` | `storage_temperature_celsius` | matching decimal column | matching key | FIXED |
| Batches | Create Batch | Ice Type | `iceType` | `ice_type` | `fish_batches.ice_type` | `ice_type` | FIXED |
| Batches | Create Batch | Ice Amount | `iceAmountKg` | `ice_amount_kg` | `fish_batches.ice_amount_kg` | `ice_amount_kg` | FIXED |
| Batches | Create Batch | Landing Site | `landingSite` | `landing_site_name` | `fish_batches.landing_site_name` | `landing_site_name` | FIXED |
| Batches | Create Batch | Notes | `notes` | `notes` | `fish_batches.notes` | `notes` | FIXED |

## Processor and Transporter workflow fields

| Module | Screen | UI label | Flutter property/payload | Request JSON | Database target | Resource key | Status |
|---|---|---|---|---|---|---|---|
| Processing | Processing Workflow | Operator | `operator` | `operator_name` | `processing_records.operator_name` | `operator_name` | FIXED |
| Processing | Processing Workflow | Processing Area | `area` | `processing_area` | `processing_records.processing_area` | `processing_area` | FIXED |
| Processing | Four ordered steps | Step measurements | `measurements` | `measurements` | `processing_steps.measurements` | `measurements` | FIXED |
| Vehicles | Add / Edit Vehicle | Registration Number | `registration` | `registration_number` | `vehicles.registration_number` | `registration_number` | MATCH |
| Vehicles | Add / Edit Vehicle | Capacity (tonnes) | `capacityTonnes` | `capacity_tonnes` | `vehicles.capacity_tonnes` | `capacity_tonnes` | FIXED |
| Vehicles | Vehicle Details | Vehicle Type | `type` | display-only | `vehicles.vehicle_type` | `vehicle_type` | FIXED |
| Vehicles | Vehicle Details | Refrigeration Category | `refrigerationCategory` | display-only | `vehicles.refrigeration_category` | `refrigeration_category` | FIXED |
| Vehicles | Vehicle Details | Reefer Unit | `reeferUnit` | display-only | `vehicles.reefer_unit` | `reefer_unit` | FIXED |
| Vehicles | Vehicle Details | Temperature Range | min/max temperature | display-only | matching Celsius columns | matching Celsius keys | FIXED |
| Vehicles | Vehicle Details | Driver | `driver` | display-only | `vehicles.default_driver_name` | `default_driver_name` | FIXED |
| Transport | Pre-Trip Checklist | Five mandatory checks | `completed` labels | `items[].key/completed` | `checklist_items` | `items[]` | FIXED |
| Transport | Delivery Confirmation | Receiver | `receiver` | `receiver_name` | `delivery_confirmations.receiver_name` | `receiver_name` | MATCH |
| Transport | Delivery Confirmation | Photos | local photo paths | multipart `DELIVERY_IMAGE` | `file_assets` | file descriptor | FIXED |
| Transport | Delivery Confirmation | Signature | exported PNG path | multipart `DELIVERY_SIGNATURE` | `file_assets` | file descriptor | FIXED |
| Transport | Trip List / Details | Distance | `distanceKm` | display-only | `transport_trips.estimated_distance_km` | `estimated_distance_km` | FIXED |
| Transport | Trip Details | Product Temperature | `productTemperature` | display-only | latest `sensor_readings.product_temperature` | `product_temperature_celsius` | FIXED |
| IoT | Live Monitoring | Product / air temperature | `SensorReading.productTemp` / `airTemp` | none | `sensor_readings.product_temperature` / `air_temperature` | same snake-case keys | FIXED (nullable) |
| IoT | Live Monitoring | Humidity / battery | `SensorReading.humidity` / `battery` | none | `sensor_readings.humidity` / `battery_percentage` | same snake-case keys | FIXED (nullable) |
| IoT | Live Monitoring | GPS and timestamp | `latitude`, `longitude`, `recordedAt` | none | `sensor_readings.latitude`, `longitude`, `recorded_at` | same snake-case keys; ISO UTC or Firebase epoch ms | FIXED (nullable GPS) |
| Transport | Monitoring Alerts | Alert type/status/threshold | `TransportAlertView` | `GET /transport-trips/{id}/alerts` | `cold_chain_alerts` | `type`, `severity`, `status`, measured/threshold values | FIXED |
| Transport | Monitoring Alerts | Acknowledge | alert ID + optional note | `POST /alerts/{id}/acknowledge` | `alert_acknowledgements` | `note` | FIXED |
| Transport | Monitoring Alerts | Driver incident | type, severity, description, timestamp | `POST /transport-trips/{id}/incidents` | `transport_incidents` | `type`, `severity`, `description`, `occurred_at` | FIXED |
| Retail | Inventory List / Sales | Default unit price | `unitPrice` | display-only | `inventory_lots.default_unit_price` | `default_unit_price` | FIXED (nullable) |
| Retail | Inventory List / Dashboard | Low-stock threshold | `lowStockThreshold` | display-only | `inventory_lots.low_stock_threshold_kg` | `low_stock_threshold_kg` | FIXED (nullable) |
| Retail | Sales / Stock Update | Package count | `quantityPackages` | `items[].quantity` / `quantity` | sale item / stock movement quantities | matching response keys | FIXED |
| Retail | Sales / Stock Update | Kilograms | calculated `packageCount * packageWeightKg` | display-only | package label weight | `package_weight_kg` | FIXED |
| Retail | Reports | Selected date range | `DateTimeRange` | `dateFrom` / `dateTo` | `ReportFilterRequest` | `date_from` / `date_to` | FIXED |
| Retail | Reports | Summary metrics | `RetailReportSummary` | no body | `ReportDataService::summary` | `sales_total`, `available_inventory_packages`, `batches` | FIXED |
| Retail | Reports | Sales rows | `RetailSalesReportRow` | no body | `ReportDataService::sales` | `receipt_number`, `location`, `status`, `total`, `sold_at` | FIXED |
| Retail | Reports | Inventory rows | `RetailInventoryReportRow` | no body | `ReportDataService::inventory` | `label_code`, package counts, `expires_at` | FIXED |
| Retail | Alerts | Status, measured value, threshold, detection time | `RetailAlertView` | `GET /retailer/alerts` | `cold_chain_alerts` | `status`, `measured_value`, `threshold_value`, `last_detected_at` | FIXED |
| Retail | Alerts | Resolve note | resolution note | `POST /retailer/alerts/{id}/resolve` | `cold_chain_alerts` audit/status | `note` (5–1000 chars) | FIXED |
| Retail | Recall alert | Quarantine reason | confirmation reason | `POST /retailer/recalls/{id}/quarantine` | inventory lots / alert status | `reason` (5–500 chars) | FIXED |
| Common | Notifications | Type, title, message, context | `AppNotification` | `GET /notifications?page=&per_page=` | database notifications | nested `data.type/title/message/context` | FIXED |
| Common | Notifications | Read state and timestamp | `read`, `readAt` | `POST /notifications/{id}/read` | `notifications.read_at` | `read_at` UTC ISO-8601 or null | FIXED |
| Common | Notifications | Date grouping | `createdAt` / derived `dayLabel` | none | `notifications.created_at` | `created_at` UTC ISO-8601 | FIXED |
| Common | Profile | Full name | `User.name` | `PUT /auth/profile` `name` | `users.name` | `name` | FIXED |
| Common | Profile | Email retained by name-only UI | `User.email` | `PUT /auth/profile` `email` | `users.email` | `email` | FIXED |
| Common | Business Information | Primary organization | `User.organization` | none | organizations + primary membership pivot | `organization.id/name/code/type/is_active` | FIXED |
| Common | File upload | Authorized file descriptor | `UploadedFile` | multipart `category/entity_type/entity_id/file` | `file_assets` | `id/category/entity_type/entity_id/original_name/mime_type/extension/size_bytes/sha256/download_url/created_at` | FIXED |
| Common | File download | Authorized download URL | `UploadedFile.downloadUrl` | `GET /files/{file}` | private configured filesystem | binary response using descriptor `download_url` | FIXED |
| Common | Profile settings | Compact density / device theme | `MobileSettings.compactDashboard/useDeviceTheme` | device-local only | versioned secure storage | typed booleans restored before app startup | FIXED |
| Common | Profile settings | Units / language preference | `measurementSystem/language` | device-local only; API remains metric | versioned secure storage | typed enum names with safe defaults | FIXED |
| Common | Profile settings | Notification categories | `temperatureAlerts/workflowUpdates/systemMessages` | device-local only | versioned secure storage | typed booleans; temperature choice gates local cold-chain alerts | FIXED |
| AI | No active mobile screen | Prediction request | none | `POST /batches/{batch}/ai-predictions/request` | queued `RequestSpoilagePrediction` | `status/batch_id/decision_support` | BACKEND_ONLY |
| AI | No active mobile screen | Prediction result | none | list/latest endpoints | `ai_predictions` | canonical risk enum, float confidence/probabilities, recommendation, model/provider, UTC time, disclaimer | FIXED_BACKEND_ONLY |
| AI | No active mobile screen | Prediction features | none | private provider payload | `ai_prediction_inputs.features` | nullable telemetry plus presence/count and elapsed threshold minutes | FIXED_BACKEND_ONLY |
