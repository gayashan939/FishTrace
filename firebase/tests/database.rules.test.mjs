import { readFile } from 'node:fs/promises';
import { after, before, beforeEach, test } from 'node:test';
import assert from 'node:assert/strict';
import {
    assertFails,
    assertSucceeds,
    initializeTestEnvironment,
} from '@firebase/rules-unit-testing';
import { get, ref, set } from 'firebase/database';

const projectId = 'demo-fishtrace';
let environment;

const validLiveReading = {
    deviceId: 'sensor-1',
    productTemperature: 3.4,
    recordedAt: 1785585600000,
    messageId: 'message-1',
};

const validTelemetry = {
    deviceId: 'sensor-1',
    tripId: 'trip-1',
    productTemperature: 3.4,
    humidity: 82,
    batteryPercentage: 91,
    latitude: 6.03,
    longitude: 80.22,
    recordedAt: 1785585600000,
    schemaVersion: 1,
};

before(async () => {
    environment = await initializeTestEnvironment({
        projectId,
        database: {
            rules: await readFile(new URL('../database.rules.json', import.meta.url), 'utf8'),
        },
    });
});

beforeEach(async () => {
    await environment.clearDatabase();
    await environment.withSecurityRulesDisabled(async (context) => {
        await set(ref(context.database()), {
            deviceAssignments: {
                'device-1': { active: true, tripId: 'trip-1', deviceId: 'sensor-1' },
                'device-2': { active: false, tripId: 'trip-1', deviceId: 'sensor-2' },
            },
            tripMembers: {
                'trip-1': { 'user-1': true },
            },
        });
    });
});

after(async () => {
    await environment?.cleanup();
});

test('unauthenticated clients cannot read or write protected data', async () => {
    const database = environment.unauthenticatedContext().database();

    await assertFails(get(ref(database, 'liveTrips/trip-1')));
    await assertFails(set(ref(database, 'telemetry/device-1/message-1'), validTelemetry));
});

test('devices can only read their own assignment and members can read their trip', async () => {
    const device = environment.authenticatedContext('device-1').database();
    const member = environment.authenticatedContext('user-1').database();
    const outsider = environment.authenticatedContext('user-2').database();

    await assertSucceeds(get(ref(device, 'deviceAssignments/device-1')));
    await assertFails(get(ref(device, 'deviceAssignments/device-2')));
    await assertSucceeds(get(ref(member, 'liveTrips/trip-1')));
    await assertFails(get(ref(outsider, 'liveTrips/trip-1')));
});

test('an active assigned device can publish only a valid live-trip payload', async () => {
    const assigned = environment.authenticatedContext('device-1').database();
    const inactive = environment.authenticatedContext('device-2').database();

    await assertSucceeds(set(ref(assigned, 'liveTrips/trip-1'), validLiveReading));
    await assertFails(set(ref(assigned, 'liveTrips/trip-2'), validLiveReading));
    await assertFails(set(ref(inactive, 'liveTrips/trip-1'), { ...validLiveReading, deviceId: 'sensor-2' }));
    await assertFails(set(ref(assigned, 'liveTrips/trip-1'), { ...validLiveReading, deviceId: 'sensor-other' }));
    await assertFails(set(ref(assigned, 'liveTrips/trip-1'), { ...validLiveReading, productTemperature: 80 }));
    await assertFails(set(ref(assigned, 'liveTrips/trip-1'), { ...validLiveReading, unexpected: true }));
});

test('telemetry is bounded, append-only, and isolated to its device identity', async () => {
    const assigned = environment.authenticatedContext('device-1').database();
    const other = environment.authenticatedContext('device-2').database();
    const reading = ref(assigned, 'telemetry/device-1/message-1');

    await assertSucceeds(set(reading, validTelemetry));
    await assertSucceeds(get(reading));
    await assertFails(set(reading, { ...validTelemetry, productTemperature: 3.5 }));
    await assertFails(get(ref(other, 'telemetry/device-1/message-1')));
    await assertFails(set(ref(assigned, 'telemetry/device-1/message-2'), { ...validTelemetry, humidity: 101 }));
    await assertFails(set(ref(assigned, 'telemetry/device-1/message-3'), { ...validTelemetry, schemaVersion: 2 }));
    await assertFails(set(ref(assigned, 'telemetry/device-1/message-4'), { ...validTelemetry, unexpected: true }));
});

test('ordinary signed-in users cannot impersonate a device writer', async () => {
    const administrator = environment.authenticatedContext('admin-user', { role: 'ADMIN' }).database();

    await assertFails(set(ref(administrator, 'liveTrips/trip-1'), validLiveReading));
    await assertFails(set(ref(administrator, 'telemetry/device-1/message-1'), validTelemetry));
    assert.ok(true);
});
