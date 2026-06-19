# iOS Push — APNs Sandbox Handoff (React Native)

Spec for the React Native / iOS team to get push working on **development (sandbox)** builds.

**The backend is complete and correct — no backend changes are involved.** FCM auth, the queue,
the worker, and device registration all work. The only blocker is an **APNs environment mismatch**
on the iOS client. This document explains it and the exact fixes.

---

## 1. The symptom

A real push to the iOS device is rejected by FCM:

```json
{
  "error": {
    "code": 401,
    "status": "UNAUTHENTICATED",
    "details": [
      { "@type": "...FcmError",  "errorCode": "THIRD_PARTY_AUTH_ERROR" },
      { "@type": "...ApnsError", "statusCode": 403, "reason": "BadEnvironmentKeyInToken" }
    ]
  }
}
```

The generic `UNAUTHENTICATED` is misleading — the real cause is the nested
**`ApnsError: BadEnvironmentKeyInToken`**.

---

## 2. Root cause (read this once, carefully)

- Every iOS push token belongs to **one** APNs environment: **sandbox** (development) or
  **production**.
- A **debug build** signed with a **Development** profile gets a **sandbox** APNs token.
  A build signed with **AdHoc / App Store / Distribution** gets a **production** token.
- The environment is **baked into the FCM token at registration time** by the app, via the
  `aps-environment` entitlement.
- **FCM HTTP v1 gives the sender NO way to override the environment.** Our backend cannot choose
  sandbox vs production — it just sends to the token, and FCM routes to whatever environment that
  token was registered under.

So `BadEnvironmentKeyInToken` means: **the token was registered as production, but the device only
has a sandbox subscription (or vice versa).** The two don't match, Apple rejects.

Uploading the APNs **`.p8` Auth Key** to Firebase (already done) lets FCM *authenticate* to both
environments — but it does **not** change which environment a given token points at. That part is
decided entirely by the app build + entitlement.

---

## 3. What to fix — Xcode

Do all of these on the **debug** target you test with.

### 3.1 Signing & Capabilities
- **Push Notifications** capability is present (adds `aps-environment` to the entitlements).
- **Background Modes** → check **Remote notifications**.
- **Signing**: use an **Automatic** signing or a **Development** provisioning profile for the debug
  build. If the debug scheme is signed with an **AdHoc/Distribution** profile, its `aps-environment`
  becomes `production` even in a debug build — that is the most common cause of this error.

### 3.2 Verify the entitlement value
Open the build's `.entitlements` file (e.g. `ios/<App>/<App>.entitlements`):

```xml
<key>aps-environment</key>
<string>development</string>   <!-- must be "development" for sandbox testing -->
```

You can also confirm what actually got signed into the installed app:

```bash
# from the built .app (Debug-iphoneos) or a dev IPA
codesign -d --entitlements :- /path/to/YourApp.app | grep -A1 aps-environment
```

It must print `development`. If it prints `production`, fix the signing profile (3.1) and rebuild.

> Note: Xcode normally sets `aps-environment` automatically from the provisioning profile. Do **not**
> hardcode it to `production` in the entitlements file for the debug target.

---

## 4. What to fix — React Native (`@react-native-firebase/messaging`)

Assuming `@react-native-firebase/app` + `@react-native-firebase/messaging`.

### 4.1 Let the SDK detect the environment (preferred)
With `FirebaseAppDelegateProxyEnabled` **enabled** (the default), the SDK reads the APNs token and the
`aps-environment` entitlement and registers the FCM token with the correct environment. **Do not**
manually force the environment in native code.

If method swizzling is **disabled** (`FirebaseAppDelegateProxyEnabled = NO` in `Info.plist`), you must
hand the APNs token to Firebase yourself, and for a dev build pass the sandbox type — in
`ios/<App>/AppDelegate.(m|mm|swift)`:

```objc
- (void)application:(UIApplication *)application
    didRegisterForRemoteNotificationsWithDeviceToken:(NSData *)deviceToken {
  // .sandbox for dev builds, .prod for store builds, or .unknown to auto-detect.
  [FIRMessaging messaging].APNSToken = deviceToken;            // auto-detect, OR:
  // [[FIRMessaging messaging] setAPNSToken:deviceToken type:FIRMessagingAPNSTokenTypeSandbox];
}
```

Prefer auto-detect (`.unknown`) so the same code works for dev and store builds.

### 4.2 Registration flow (already specified — unchanged)
After login, on a real device, with notification permission granted:

```ts
import messaging from '@react-native-firebase/messaging';

await messaging().requestPermission();           // iOS prompt
const token = await messaging().getToken();      // FCM registration token
await api.post('/api/hrms/devices', {
  token,
  platform: 'ios',
  app_version: '1.x.x',
});

// re-register on rotation
messaging().onTokenRefresh(token =>
  api.post('/api/hrms/devices', { token, platform: 'ios', app_version: '1.x.x' })
);
```

See `MOBILE-HANDOFF.md` for the full devices API (`POST`/`DELETE /api/hrms/devices`, auth, payload).

### 4.3 Re-register after the fix
The token currently stored on the backend is tagged the **wrong** environment and will **not** start
working on its own. After rebuilding with `aps-environment=development`:
1. **Delete + reinstall** the app on the device (fresh APNs registration).
2. Log in → it POSTs a fresh **sandbox** token.
3. Tell the backend to fire a test (below).

---

## 5. Verification (backend will run this)

1. Backend confirms a new `device_tokens` row (`platform='ios'`, `revoked_at` NULL).
2. Backend fires one real send to that token.
   - **Success** → phone shows the notification. Done.
   - **Still `BadEnvironmentKeyInToken`** → the installed app's `aps-environment` is still
     `production`; recheck §3.2 (`codesign -d --entitlements`).
   - **`Unregistered` / 404** → token rotated; re-register and retry.

---

## 6. FAQ

**Do we need App Store / TestFlight review to test?**
No. Sandbox testing needs only a **Development-signed debug build** on a real device + the `.p8` key
(already uploaded). Review is only needed later for a production/App Store build.

**Production build — anything different?**
No code change. A store/TestFlight build is signed with `aps-environment=production`, gets a
production token, and FCM routes to production APNs using the same `.p8`. The auto-detect code in §4.1
handles both with no branching.

**Android?**
Unaffected — no APNs layer. It works once a device registers.

**Can the backend force sandbox?**
No. FCM HTTP v1 does not expose an APNs-environment override; it is determined by the token. This is
why the fix must be on the client.

---

## 7. One-line summary
The backend is done. Sign the **debug** build with a **Development** profile so `aps-environment` is
`development`, let Firebase auto-detect the APNs environment, reinstall, and re-register — then the
existing backend delivers the push to sandbox APNs.
