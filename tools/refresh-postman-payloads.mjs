import fs from 'node:fs';

const collectionPath = new URL('../postman/FishTrace.postman_collection.json', import.meta.url);

const json = (value) => JSON.stringify(value, null, 2);
const raw = (value) => ({mode: 'raw', raw: json(value), options: {raw: {language: 'json'}}});
const formData = (items) => ({mode: 'formdata', formdata: items.map(([key, value, type = 'text']) => ({key, type, src: type === 'file' ? [] : undefined, value: type === 'file' ? undefined : value}))});

const payloads = {
  'App\\Http\\Requests\\Admin\\ResetManagedUserPasswordRequest': {password: 'FishTrace@2026', password_confirmation: 'FishTrace@2026'},
  'App\\Http\\Requests\\Admin\\StoreManagedUserRequest': {name: 'Demo User', email: 'demo.user@example.test', password: 'FishTrace@2026', password_confirmation: 'FishTrace@2026', status: 'ACTIVE', role_ids: ['{{roleId}}'], organization_ids: ['{{organization}}'], primary_organization_id: '{{organization}}'},
  'App\\Http\\Requests\\Admin\\StoreOrganizationRequest': {name: 'Demo Fisheries Cooperative', code: 'DEMO-001', type: 'FISHER'},
  'App\\Http\\Requests\\Admin\\UpdateManagedUserRequest': {name: 'Updated Demo User', email: 'demo.user@example.test', role_ids: ['{{roleId}}'], organization_ids: ['{{organization}}'], primary_organization_id: '{{organization}}'},
  'App\\Http\\Requests\\Admin\\UpdateOrganizationRequest': {name: 'Updated Fisheries Cooperative', code: 'DEMO-001', type: 'FISHER'},
  'App\\Http\\Requests\\Auth\\ChangePasswordRequest': {current_password: '{{demoPassword}}', password: 'FishTrace@2026', password_confirmation: 'FishTrace@2026'},
  'App\\Http\\Requests\\Auth\\ForgotPasswordRequest': {email: '{{demoEmail}}'},
  'App\\Http\\Requests\\Auth\\ResetPasswordRequest': {email: '{{demoEmail}}', reset_token: '{{resetToken}}', password: 'FishTrace@2026', password_confirmation: 'FishTrace@2026'},
  'App\\Http\\Requests\\Auth\\VerifyPasswordResetOtpRequest': {email: '{{demoEmail}}', otp: '{{resetOtp}}'},
  'App\\Http\\Requests\\Fisher\\StoreBatchRequest': {fish_species_id: '{{fishSpecies}}', product_type: 'Chilled whole tuna', catches: [{catch_id: '{{catchRecord}}', weight_kg: 10}]},
  'App\\Http\\Requests\\Fisher\\StoreBoatRequest': {registration_number: 'DEMO-BOAT-001', name: 'Demo Fishing Boat', type: 'Longliner', capacity_kg: 1000, is_active: true},
  'App\\Http\\Requests\\Fisher\\StoreCatchRequest': {fishing_trip_id: '{{fishingTrip}}', fish_species_id: '{{fishSpecies}}', fishing_gear_type_id: '{{fishingGearType}}', weight_kg: 10, quantity: 1, caught_at: '2026-08-03T08:00:00Z', client_record_id: '{{clientRecordId}}', client_created_at: '2026-08-03T08:00:00Z'},
  'App\\Http\\Requests\\Fisher\\StoreFishingTripRequest': {boat_id: '{{boat}}', landing_site_id: '{{landingSite}}', general_catch_area: 'FAO Area 51, Southern Sri Lanka'},
  'App\\Http\\Requests\\Fisher\\UpdateBoatRequest': {registration_number: 'DEMO-BOAT-001', name: 'Updated Demo Fishing Boat', type: 'Longliner', capacity_kg: 1100, is_active: true},
  'App\\Http\\Requests\\Fisher\\UpdateCatchRequest': {fish_species_id: '{{fishSpecies}}', fishing_gear_type_id: '{{fishingGearType}}', weight_kg: 12, quantity: 1, caught_at: '2026-08-03T08:00:00Z', client_created_at: '2026-08-03T08:00:00Z'},
  'App\\Http\\Requests\\Fisher\\UpdateFishingTripRequest': {boat_id: '{{boat}}', landing_site_id: '{{landingSite}}', general_catch_area: 'FAO Area 51, Southern Sri Lanka'},
  'App\\Http\\Requests\\IoT\\AcknowledgeColdChainAlertRequest': {note: 'Alert reviewed by operations.'},
  'App\\Http\\Requests\\IoT\\StoreIotDeviceRequest': {organization_id: '{{organization}}', device_code: 'DEMO-IOT-001', serial_number: 'DEMO-SERIAL-001', display_name: 'Demo Reefer Sensor', firmware_version: '1.0.0', supports_product_temperature: true, supports_air_temperature: true, supports_humidity: true, supports_gps: true, supports_door_sensor: false},
  'App\\Http\\Requests\\IoT\\UpdateIotDeviceRequest': {display_name: 'Updated Demo Reefer Sensor', firmware_version: '1.0.1', supports_product_temperature: true, supports_air_temperature: true, supports_humidity: true, supports_gps: true, supports_door_sensor: false},
  'App\\Http\\Requests\\Processor\\CompleteProcessingStepRequest': {measurements: {product_temperature: -1.5, output_weight_kg: 9.2}, notes: 'Step completed within target temperature.'},
  'App\\Http\\Requests\\Processor\\ReceiveBatchRequest': {received_weight_kg: 100, notes: 'Batch accepted in good condition.'},
  'App\\Http\\Requests\\Processor\\RejectBatchRequest': {rejection_reason: 'Temperature control could not be verified.', received_weight_kg: 100, notes: 'Escalated for quality review.'},
  'App\\Http\\Requests\\Processor\\SplitBatchRequest': {children: [{weight_kg: 40, product_type: 'Frozen tuna loin', package_count: 4}, {weight_kg: 40, product_type: 'Frozen tuna loin', package_count: 4}]},
  'App\\Http\\Requests\\Processor\\StoreProcessingRecordRequest': {fish_batch_id: '{{batch}}', processing_type_id: '{{processingType}}', input_weight_kg: 100, notes: 'Standard chilled processing.'},
  'App\\Http\\Requests\\Processor\\StoreQualityInspectionRequest': {processing_record_id: '{{processingRecord}}', result: 'PASSED', quality_grade_id: '{{qualityGrade}}', product_temperature: -1.5, ph_level: 5.8, appearance: 'Bright and firm', odor: 'Fresh', notes: 'Approved for sale.'},
  'App\\Http\\Requests\\Processor\\UpdateProcessingRecordRequest': {notes: 'Processing documentation updated.'},
  'App\\Http\\Requests\\Reports\\StoreReportExportRequest': {report_type: 'batch-status', date_from: '2026-08-01', date_to: '2026-08-03', species_id: '{{fishSpecies}}', status: 'ACTIVE'},
  'App\\Http\\Requests\\Support\\StoreSupportIssueRequest': {subject: 'Mobile sync issue', description: 'A pending mobile record could not be synchronized after reconnecting.'},
  'App\\Http\\Requests\\Retail\\AdjustInventoryRequest': {quantity: 1},
  'App\\Http\\Requests\\Retail\\MarkInventoryUnavailableRequest': {reason: 'Product quality review required.'},
  'App\\Http\\Requests\\Retail\\QuarantineRetailAlertRequest': {reason: 'Product held pending food-safety review.'},
  'App\\Http\\Requests\\Retail\\ReceivePackageRequest': {retail_location_id: '{{retailLocation}}', received_package_count: 1, condition_temperature: -1.2, expires_at: '2026-08-24T12:00:00Z', notes: 'Received in good condition.'},
  'App\\Http\\Requests\\Retail\\ResolveRetailAlertRequest': {note: 'Corrective action completed and documented.'},
  'App\\Http\\Requests\\Retail\\StoreRetailReceiptRequest': {package_label_id: '{{label}}', retail_location_id: '{{retailLocation}}', received_package_count: 1, condition_temperature: -1.2, expires_at: '2026-08-24T12:00:00Z', notes: 'Received in good condition.'},
  'App\\Http\\Requests\\Retail\\StoreRetailSaleRequest': {retail_location_id: '{{retailLocation}}', client_reference: '{{clientSaleReference}}', items: [{inventory_lot_id: '{{lot}}', quantity: 1, unit_price: 2500}]},
  'App\\Http\\Requests\\Retail\\StoreStockAdjustmentRequest': {inventory_lot_id: '{{lot}}', direction: 'REMOVE', quantity: 1, reason: 'Damaged packaging found during stock count.'},
  'App\\Http\\Requests\\Transport\\AddTransportBatchRequest': {batch_id: '{{batch}}'},
  'App\\Http\\Requests\\Transport\\AssignDeviceRequest': {iot_device_id: '{{device}}', expires_at: '2026-08-04T12:00:00Z'},
  'App\\Http\\Requests\\Transport\\CancelTransportTripRequest': {reason: 'Vehicle refrigeration fault requires maintenance.'},
  'App\\Http\\Requests\\Transport\\StoreDeliveryConfirmationRequest': {receiver_name: 'Amali Perera', receiver_contact: '+94770000000', notes: 'Delivered in good condition.', delivered_at: '2026-08-03T10:00:00Z'},
  'App\\Http\\Requests\\Transport\\StoreTransportIncidentRequest': {type: 'TEMPERATURE_DEVIATION', severity: 'WARNING', description: 'Temperature briefly exceeded the configured threshold.', occurred_at: '2026-08-03T09:30:00Z'},
  'App\\Http\\Requests\\Transport\\StoreTransportTripRequest': {vehicle_id: '{{vehicle}}', driver_name: 'Demo Driver', origin: 'Mirissa', destination: 'Colombo', scheduled_at: '2026-08-03T08:00:00Z'},
  'App\\Http\\Requests\\Transport\\StoreVehicleRequest': {registration_number: 'DEMO-VEHICLE-001', name: 'Demo Reefer Truck'},
  'App\\Http\\Requests\\Transport\\UpdateTransportChecklistRequest': {items: [{key: 'vehicle_clean', completed: true}, {key: 'refrigeration_operational', completed: true}]},
  'App\\Http\\Requests\\Transport\\UpdateTransportTripRequest': {driver_name: 'Updated Demo Driver', origin: 'Mirissa', destination: 'Colombo', scheduled_at: '2026-08-03T08:00:00Z'},
  'App\\Http\\Requests\\Transport\\UpdateVehicleRequest': {registration_number: 'DEMO-VEHICLE-001', name: 'Updated Demo Reefer Truck', is_active: true},
};

const multipart = {
  'App\\Http\\Requests\\Files\\StoreFileRequest': formData([['category', 'BATCH_DOCUMENT'], ['entity_type', 'fish_batch'], ['entity_id', '{{batch}}'], ['file', '', 'file']]),
  'App\\Http\\Requests\\Fisher\\StoreBatchDocumentRequest': formData([['category', 'BATCH_DOCUMENT'], ['file', '', 'file']]),
};

const noBody = new Set([
  'App\\Http\\Requests\\Admin\\AdminOrganizationMutationRequest',
  'App\\Http\\Requests\\Admin\\AdminUserMutationRequest',
  'App\\Http\\Requests\\AI\\RequestAIPredictionRequest',
  'App\\Http\\Requests\\Fisher\\MutateFishingTripRequest',
  'App\\Http\\Requests\\IoT\\MutateIotDeviceRequest',
  'App\\Http\\Requests\\Notifications\\NotificationMutationRequest',
  'App\\Http\\Requests\\Processor\\StartProcessingStepRequest',
  'App\\Http\\Requests\\Transport\\CompleteTransportTripRequest',
  'App\\Http\\Requests\\Transport\\MutateTransportTripRequest',
]);

const additionalVariables = [
  'clientRecordId', 'clientSaleReference', 'fishSpecies', 'fishingGearType', 'landingSite', 'processingType', 'qualityGrade', 'retailLocation', 'roleId', 'resetOtp', 'resetToken',
];

const collection = JSON.parse(fs.readFileSync(collectionPath, 'utf8'));
let supportFolder = collection.item.find((folder) => folder.name === 'Support');
if (!supportFolder) {
  supportFolder = {name: 'Support', item: []};
  collection.item.push(supportFolder);
}
if (!supportFolder.item.some((item) => item.request?.url?.raw?.endsWith('/api/v1/support/issues'))) {
  supportFolder.item.push({
    name: 'POST /api/v1/support/issues',
    request: {
      method: 'POST',
      header: [
        {key: 'Accept', value: 'application/json'},
        {key: 'Content-Type', value: 'application/json'},
      ],
      url: {
        raw: '{{baseUrl}}/api/v1/support/issues',
        host: ['{{baseUrl}}'],
        path: ['api', 'v1', 'support', 'issues'],
      },
      description: 'Controller action: `App\\Http\\Controllers\\Api\\V1\\SupportIssueController@store`. Validation: `App\\Http\\Requests\\Support\\StoreSupportIssueRequest`.',
      body: raw(payloads['App\\Http\\Requests\\Support\\StoreSupportIssueRequest']),
    },
    response: [],
  });
}
for (const key of additionalVariables) {
  if (!collection.variable.some((variable) => variable.key === key)) {
    collection.variable.push({key, value: `REPLACE_${key}`});
  }
}

for (const folder of collection.item) {
  for (const item of folder.item ?? []) {
    const request = item.request;
    if (!request?.body || request.body.mode !== 'raw' || request.body.raw.trim() !== '{}') continue;
    const formRequest = request.description?.match(/Validation: `([^`]+)`/)?.[1];
    if (multipart[formRequest]) {
      request.body = multipart[formRequest];
    } else if (payloads[formRequest]) {
      request.body = raw(payloads[formRequest]);
    } else if (!formRequest || noBody.has(formRequest)) {
      delete request.body;
    } else {
      throw new Error(`No payload template for ${item.name}: ${formRequest}`);
    }
  }
}

const fileFolder = collection.item.find((folder) => folder.name === 'Files');
const uploadTemplate = fileFolder?.item?.find((item) => item.request?.method === 'POST' && item.request.url?.raw?.endsWith('/api/v1/files'));
if (fileFolder && uploadTemplate) {
  const variants = [
    ['Upload catch image', 'CATCH_IMAGE', 'catch_record', '{{catchRecord}}'],
    ['Upload processing image', 'PROCESSING_IMAGE', 'processing_record', '{{processingRecord}}'],
    ['Upload inspection image', 'INSPECTION_IMAGE', 'quality_inspection', '{{inspection}}'],
    ['Upload delivery image', 'DELIVERY_IMAGE', 'transport_trip', '{{transportTrip}}'],
    ['Upload delivery signature', 'DELIVERY_SIGNATURE', 'transport_trip', '{{transportTrip}}'],
    ['Upload batch document', 'BATCH_DOCUMENT', 'fish_batch', '{{batch}}'],
  ];
  const generatedNames = new Set(variants.map(([name]) => name));
  fileFolder.item = fileFolder.item.filter((item) => item !== uploadTemplate && !generatedNames.has(item.name));
  for (const [name, category, entityType, entityId] of variants) {
    const item = structuredClone(uploadTemplate);
    item.name = name;
    item.request.body = formData([
      ['category', category],
      ['entity_type', entityType],
      ['entity_id', entityId],
      ['file', '', 'file'],
    ]);
    item.request.header = item.request.header.filter((header) => header.key.toLowerCase() !== 'content-type');
    fileFolder.item.push(item);
  }
}

for (const folder of collection.item) {
  folder.item = (folder.item ?? []).filter((item) => !(item.request?.method === 'PATCH' && item.request.url?.raw?.includes('/processing-records/')));
  const patchNames = new Set((folder.item ?? []).filter((item) => item.request?.method === 'PATCH').map((item) => item.name));
  const patchCopies = (folder.item ?? [])
    .filter((item) => item.request?.method === 'PUT' && !item.request.url?.raw?.includes('/processing-records/'))
    .map((item) => {
      const copy = structuredClone(item);
      copy.name = copy.name.replace(/^PUT /, 'PATCH ');
      copy.request.method = 'PATCH';
      return copy;
    })
    .filter((item) => !patchNames.has(item.name));
  folder.item.push(...patchCopies);
}

collection.info.description = 'Generated from the registered FishTrace API routes. Run Login first to store `accessToken`. Write requests include payloads derived from their Form Request validation rules; replace every `REPLACE_*` ID with an existing record ID for the authenticated organization. File uploads include workflow-specific multipart examples.';
fs.writeFileSync(collectionPath, `${JSON.stringify(collection, null, 2)}\n`);
