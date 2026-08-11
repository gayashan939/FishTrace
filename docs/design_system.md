# FishTrace design system

This document records the visual rules extracted from the five supplied
390 × 844 reference boards. The reference images are the source of truth.

## Visual character

FishTrace uses a restrained maritime enterprise aesthetic: white content
surfaces, deep teal navigation, cyan accents, subtle grey borders, compact
information density, and almost no elevation. The interface must not fall back
to generic Material composition when a FishTrace component exists.

## Semantic colors

| Token | Value | Use |
|---|---:|---|
| `primary` | `#006B78` | Primary actions, selected navigation, links |
| `primaryDark` | `#004B5A` | Teal app bars and deep controls |
| `cyan` | `#0A91A8` | Secondary emphasis and charts |
| `aqua` | `#4DD6D9` | Ocean highlights |
| `navy` | `#071D2C` | Splash and QR certificate backgrounds |
| `ocean` | `#00384B` | Dark ocean panels |
| `page` | `#F7F9FB` | Page background |
| `surface` | `#FFFFFF` | Cards, inputs, sheets |
| `surfaceMuted` | `#F2F5F7` | Segments and secondary panels |
| `textPrimary` | `#102330` | Main text |
| `textSecondary` | `#617482` | Supporting text |
| `textTertiary` | `#8A9AA5` | Metadata and inactive icons |
| `border` | `#E2E8EC` | Card/input outlines |
| `success` | `#1AA36F` | Verified, completed, connected |
| `warning` | `#F59B23` | Expiry, low stock, warning |
| `error` | `#E94848` | Critical alerts and destructive actions |
| `info` | `#2F80ED` | Informational state |

Raw colors are prohibited in feature screens. Semantic colors live in
`lib/app/theme/fishtrace_colors.dart`.

## Typography

The reference typeface is a compact modern sans serif. Roboto is used as the
production-safe bundled equivalent until a licensed Inter asset is supplied.

| Style | Size | Weight | Intended use |
|---|---:|---:|---|
| Display | 29 | 800 | Authentication hero copy |
| Page title | 23 | 800 | Rare large page headings |
| Section title | 18 | 700 | Major content sections |
| Card title | 16 | 700 | Cards and dialogs |
| Compact title | 14 | 700 | List/card titles |
| Body | 15 | 400 | Primary body text |
| Compact body | 13 | 400 | Dense screen content |
| Supporting | 12 | 400 | Metadata |
| Labels | 11–13 | 600–700 | Fields, chips, controls |

Text must remain readable with reasonable system text scaling. Long titles and
metadata must wrap or ellipsize deliberately rather than overflow.

## Layout measurements

- Target viewport: 390 × 844 logical pixels, portrait.
- Horizontal page gutter: 16.
- Spacing scale: 4, 8, 12, 16, 20, 24, 32.
- App bar: 56 high.
- Bottom navigation: 70 high plus safe-area inset.
- Primary/secondary buttons: 48 high.
- Practical minimum touch target: 44 × 44.
- Dashboard card gaps: 8–12.
- Standard card padding: 12; spacious panels may use 16.

Do not scale every measurement from the viewport. Use constraints, wrapping,
and scrollable regions.

## Shape and elevation

- Compact control radius: 8.
- Input radius: 10.
- Standard card radius: 12.
- Large visual panel radius: 18.
- Status pill radius: fully rounded.
- Cards use a one-pixel neutral border.
- Shadows are reserved for the central navigation action and modal surfaces.
  They use low opacity, small blur, and a short downward offset.

## Navigation pattern

Role screens use four labelled navigation destinations around a raised central
action. The reference mapping is:

| Role | Destination 1 | Destination 2 | Central action | Destination 3 | Destination 4 |
|---|---|---|---|---|---|
| Fisher | Home | Trips | Add catch | Batches | More |
| Processor | Dashboard | Intake | Scan batch | History | More |
| Transporter | Home | Trips | Add batch | Alerts | More |
| Retailer | Home | Inventory | Receive batch | Alerts | More |

The selected destination uses teal icon/text. Alert destinations may display a
red numeric badge.

## Component patterns

- Dashboard headers use a deep teal field, greeting, role/business subtitle,
  and notification control.
- Standard pages use a centered compact app bar and white or near-white body.
- Search combines a 44px field with an optional square filter control.
- Segmented filters use a pale surface and teal selected item.
- Cards are flat, bordered, and information-dense.
- Status always combines text with color; it is never color-only.
- Forms place compact labels above fields and show required markers.
- Primary actions sit at the end of content or in a keyboard-safe sticky
  footer when the reference requires it.
- QR scanner screens use a dark camera area, explicit framing corners, flash
  control, manual fallback, and clear permission/error states.
- Map previews use rounded panels with coordinates/status below.
- Charts use cyan/teal series, sparse axes, and pale grid lines.

## Required component catalogue

The master-prompt component set is implemented in `lib/core/widgets/` and
exported through `fishtrace_widgets.dart`.

| Group | Components | Primary consumers |
|---|---|---|
| Shell/navigation | `FishTraceScaffold`, `FishTraceAppBar`, `FishTraceBottomNavigation` | All primary screens and role navigation |
| Actions | `FishTracePrimaryButton`, `FishTraceSecondaryButton`, `FishTraceDestructiveButton` | Forms, sticky actions and destructive workflows |
| Inputs | `FishTraceTextField`, `FishTraceDropdown`, `FishTraceSearchField` | Authentication and role forms/search |
| General cards | `FishTraceCard`, `MetricCard`, `StatusChip`, `AlertCard`, `SectionHeader` | Dashboards and detail screens |
| Domain cards | `FishBatchCard`, `CatchCard`, `TripCard`, `VehicleCard`, `DeviceCard`, `InventoryProductCard` | Fisher, processor, transporter and retailer lists |
| Telemetry/history | `Timeline`, `TemperatureCard`, `SensorMetricCard`, `ChartCard`, `MapPreviewCard` | Traceability, cold-chain and monitoring screens |
| Device/media | `QRScannerFrame`, `QRCodeCard`, `ImageAttachmentPicker`, `SignaturePad` | QR, inspection, catch and delivery flows |
| States | `LoadingState`, `EmptyState`, `ErrorState`, `OfflineBanner`, `SyncStatusChip`, `RetryPanel` | Repository, offline and synchronization states |
| Overlays | `FilterBottomSheet`, `ConfirmationBottomSheet`, `SuccessDialog`, `PermissionDialog` | Filters, guarded mutations and permission recovery |

Feature screens compose these components with feature-owned presentation
widgets. Repeated role-domain list cards must use the domain cards above rather
than recreate borders, spacing, status colors and metadata layout locally.

## Artwork assumptions

The boards contain raster ocean scenes, boats, fish, maps, portraits, and
species illustrations, but no source assets or usage rights were supplied.
The application therefore uses:

- a custom-painted FishTrace mark and wordmark;
- custom ocean/wave/map compositions;
- consistent iconography for boats, fish, vehicles, and devices;
- generated demo thumbnails where a visual is essential.

These substitutions must preserve the reference layout, contrast, hierarchy,
and proportions. They are not permission to replace a reference-specific
screen with a generic Material screen.
