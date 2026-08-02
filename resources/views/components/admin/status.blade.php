@props(['value'])
@php
    $status = strtoupper((string) ($value instanceof BackedEnum ? $value->value : $value));
    $tone = match (true) {
        in_array($status, ['ACTIVE','COMPLETED','PASSED','CONFIRMED','SYNCED','IN_STOCK','VALID','READ'], true) => 'bg-emerald-100 text-emerald-800',
        in_array($status, ['FAILED','CRITICAL','RECALLED','EXPIRED','INVALID','CANCELLED','REJECTED'], true) => 'bg-red-100 text-red-800',
        in_array($status, ['WARNING','CONDITIONAL','QUALITY_HOLD','RESERVED','ACKNOWLEDGED','PROCESSING'], true) => 'bg-amber-100 text-amber-800',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp
<span {{ $attributes->class("inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {$tone}") }}>{{ str_replace('_', ' ', $status) }}</span>
