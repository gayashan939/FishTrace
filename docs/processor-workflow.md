# Processor workflow

Incoming/history, processing records, ordered steps, inspections, package labels, child batches, and AI prediction history use explicit Resources and typed processor query services. Directory pagination is validated and capped at 100. Controller-side record lookups are avoided for creation workflows; the Actions enforce processor-organization ownership and locked workflow state. Public QR/package tokens are never serialized by authenticated processor responses, while printable labels resolve their batch read model without mutating print state.

## Intake

Processors list batches in `AVAILABLE_FOR_PROCESSING` through `GET /api/v1/processor/incoming-batches`. Acceptance and rejection are dedicated transitions:

- `POST /processor/batches/{batch}/accept` requires positive received weight, creates a processor-organization intake, moves the batch to `ACCEPTED_BY_PROCESSOR`, and records `PROCESSOR_ACCEPTED`.
- `POST /processor/batches/{batch}/reject` requires a reason, records the rejected intake and traceability event, and leaves the batch available to another processor.

The accepted intake is unique per batch and organization. Once claimed, another processor organization cannot read or process the batch.

## Processing

`POST /processing-records` requires an accepted intake belonging to the current organization. Input weight cannot exceed either batch weight or received weight. The optional `operator_name` and `processing_area` fields preserve the mobile assignment shown to the operator. Creation atomically moves the batch to `PROCESSING` and creates the required ordered steps:

1. CLEANING — requires `cleaned_weight_kg`.
2. GRADING — requires `grade`.
3. FREEZING — requires `product_temperature` between -40°C and 4°C.
4. PACKAGING — requires output weight, waste weight, and package count.

Each step must be started before completion and all preceding steps must be complete. Packaged output plus waste cannot exceed input by more than 0.05 kg.

Step transitions lock both the processing record and its step and verify the nested route relationship. A step UUID from another record returns 404 and cannot be mutated. Once inspection completes a record, its steps cannot be reopened or recompleted. `PUT /processing-records/{processingRecord}` changes validated notes only while the record is not complete; workflow status and calculated weights are never accepted through this generic update.

## Inspection and splitting

Quality inspection is allowed only after every step is complete. PASSED completes the processing record, updates final batch weight, moves the batch to `PROCESSED`, and records inspection and processing milestones. FAILED or CONDITIONAL places processing on `QUALITY_HOLD` with mandatory notes.

`POST /batches/{batch}/split` accepts 2–50 child definitions. The action locks the parent, prevents cumulative child weights exceeding final output, creates traceability relationships, secure child QR tokens, and printable package labels in one transaction. Labels are available through `/package-labels/{label}` and the read-only print preview at `/package-labels/{label}/print`.
