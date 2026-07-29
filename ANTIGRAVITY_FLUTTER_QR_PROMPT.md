# Antigravity Prompt — Port the Wearable-ERP Production QR Scanner to Flutter

> Paste everything below the line into Antigravity, after attaching/opening your existing Flutter app.
> Every endpoint, field name, response flag, timing and string in this spec was read directly from the
> PHP source — do not let the model invent alternatives.

---

## ROLE

You are implementing a **production floor QR scanning module** inside my existing Flutter app.

The feature already exists as a mobile web page in a PHP 8 MVC ERP (`wearable-erp`). I want a **native Flutter port with exact behavioural parity** — same screens, same validation gates, same error states, same wording. The PHP backend is **not** to be modified. The Flutter app will authenticate as a normal web user (session cookie + CSRF token) and call the same web endpoints the browser page calls.

Reference implementation in the PHP repo (read-only, for your understanding):

| File | What it contains |
|---|---|
| `app/Views/company/qr_tracking.php` | The entire feature — markup, CSS and all JS inline (792 lines). This is your source of truth for UI and flow. |
| `app/Controllers/ProductionController.php` | `qrTracking()` L882, `getBatchStages()` L871, `verifyQrCode()` L1133, `logQrActivity()` L928 |
| `app/Helpers/StageHelper.php` | Stage key normalisation |
| `app/Core/Session.php` | CSRF token generation/validation |
| `routes/web.php` L721-735, L902 | Route + middleware definitions |

---

## HARD CONSTRAINTS — read before writing any code

1. **Do NOT use the `/api/production/*` endpoints** (`ApiQrController`). They look convenient but they are unsafe and incomplete: they have **no auth middleware at all**, they ignore tenant scoping, and `verifyQr()` **skips the serial-range check, the size-vs-`sizes_json` check and the entire preceding-stage sequence/quality gate**. Using them would let operators log units out of sequence.
2. **Do NOT use `POST /api/login`.** It returns a `token` / `access_token` field that is generated with `bin2hex(random_bytes(32))` and **never stored or validated anywhere**. It is a dead end. It also never calls `Auth::login()`, so it establishes no session.
3. **Do not modify the PHP backend.** No new endpoints, no schema changes.
4. **Do not invent field names.** Request keys are exactly `qr_code`, `stage`, `status`, `duration_seconds`, `csrf_token`.
5. All POST bodies go as **`multipart/form-data` or `application/x-www-form-urlencoded`** — the PHP `Request::get()` reads `$_POST`, **not** a JSON body. A JSON body will be silently read as empty and every request will fail.

---

## 1. AUTHENTICATION — session cookie + CSRF

The three endpoints you need are protected by `AuthMiddleware` → `CsrfMiddleware` → `PermissionMiddleware` (permission name `company.production.rfid_tracking`).

### 1.1 Login sequence

```
GET  {baseUrl}/login
     → HTML. Extract the CSRF token with: name="csrf_token" value="([a-f0-9]{64})"
     → Response sets cookie PHPSESSID. Persist it.

POST {baseUrl}/login          (application/x-www-form-urlencoded)
     email=<identifier>&password=<password>&csrf_token=<token from above>
     → 302 redirect. Do NOT auto-follow blindly; inspect it:
         redirect to /login              => credentials rejected
         redirect to a dashboard route   => success
     → IMPORTANT: Auth::login() calls Session::regenerate() (session_regenerate_id),
       so this response contains a NEW PHPSESSID in Set-Cookie. Your cookie jar
       MUST replace the old one. Session *data* (including csrf_token) is preserved
       across regeneration, but the cookie value changes.
```

The `email` field accepts an **email, username, or employee code** — label it "Email / Username / Employee Code" in the UI.

### 1.2 Getting the CSRF token for the scanner (important)

After login you cannot re-`GET /login` to read the token — `showLogin()` redirects away when already authenticated. Instead:

```
GET {baseUrl}/company/production/qr-tracking
```

This single request gives you **three** things at once:

1. **The CSRF token**, embedded twice in the inline JS. Extract with:
   `csrf_token['"],\s*["']([a-f0-9]{64})["']`
2. **The batch list** (see §2.1).
3. **A permission check for free** — if the user lacks `company.production.rfid_tracking`, this request redirects/403s. Surface that as "You do not have permission to use the production scanner."

Send the token on every POST as the form field `csrf_token`. The `X-CSRF-Token` **header is also accepted** by `CsrfMiddleware` — either is fine; prefer the form field for parity.

### 1.3 CSRF failure recovery

If a POST returns HTTP 403 with `{"error":"Security Error: CSRF token mismatch."}`, the middleware has already **rotated the token**. Your client must: re-fetch `/company/production/qr-tracking`, re-extract the token, and retry the request **once**. If the retry also fails, or the page redirects to `/login`, treat it as session expiry → bounce to the login screen.

### 1.4 Session persistence

Session lifetime is 7200s and the ID regenerates every 1800s of activity. Use a **persistent cookie jar** on disk so the operator is not logged out when the app is backgrounded. On cold start, validate the stored session by fetching the qr-tracking page; if it redirects to `/login`, show login.

**Suggested packages:** `dio` + `dio_cookie_manager` + `cookie_jar` (`PersistCookieJar` with `path_provider`), and `html` for parsing.

---

## 2. ENDPOINTS — exact contracts

Base path prefix for all three: `{baseUrl}/company/production/`

### 2.1 Batch list — scraped, not JSON

There is **no JSON endpoint** for the batch dropdown in the web namespace. `qrTracking()` renders the batches server-side into a `<select id="batch-select">`. Parse it:

```html
<select id="batch-select" ...>
  <option value="">Select Batch</option>
  <option value="17">B2507002</option>   <!-- value = production_order.id, text = production_no -->
</select>
```

Model: `Batch { int id; String productionNo; }`

Only batches with `status IN ('running','in_progress')` and `deleted_at IS NULL` for the logged-in company are returned, newest first. If the list is empty, show: *"No started production batches available. Start a batch in the ERP first."*

### 2.2 Stage list

```
GET {baseUrl}/company/production/batch-stages/{batchId}
```

```json
{ "success": true, "stages": [ { "key": "thread_cutting", "name": "Thread Cutting", "order": 1 } ] }
```

Notes:
- `stages` may contain plain strings instead of objects in legacy data — handle both. If it's a string, treat it as the key and derive the name as `key.replaceAll('_',' ').toUpperCase()`.
- Already sorted by `order` ascending.
- Dropdown label format is `#{order} {name}` (e.g. `#1 Thread Cutting`). The **value you submit is `key`**.
- On `success:false` or empty list → `"No stages configured for this style"`, keep the dropdown disabled.

### 2.3 Verify a scanned code

```
POST {baseUrl}/company/production/qr-tracking/verify
form: qr_code, stage, csrf_token
```

**Success:**
```json
{
  "success": true,
  "message": "QR Code verified successfully.",
  "product": {
    "batch_no": "B2507002", "style_no": "ST-100", "style_name": "Crew Neck Tee",
    "category": "tshirt", "brand": "N/A", "composition": "100% Premium Cotton",
    "buyer_po": "PO-9001", "size": "XXL", "serial": 1, "target_qty": 500
  }
}
```

**Failure** — `success:false` plus **at most one** of these boolean flags:

| Flag | Meaning | Example message |
|---|---|---|
| `already_validated` | Same QR already logged in **this** stage | `This QR Code (B2507002-XXL-0001) has ALREADY been validated in stage 'Sewing' by Ravi on 12-Jul-2026 09:14 AM.` |
| `failed_unit` | Unit was marked FAIL in a preceding stage | `Quality Gate Blocked: Unit (…) was marked as FAILED in preceding stage 'Cutting'. Edit entry in stage log to PASS to unblock.` |
| `sequence_mismatch` | A preceding stage has no PASS log yet | `Order Sequence Error: Unit (…) cannot enter stage 'Sewing' yet. Preceding stage 'Cutting' must be completed first!` |
| *(no flag)* | Generic rejection | empty QR / `Invalid tag format. QR code must match: [BATCH_CODE]-[SIZE]-[SERIAL].` / `Production batch 'X' is not registered or active in this ERP.` / `Serial number #N exceeds target quantity limit of 500 pieces.` / `Size 'XS' is not configured for production batch 'X'.` |

### 2.4 Log the PASS/FAIL result

```
POST {baseUrl}/company/production/qr-tracking/log
form: qr_code, stage, status(pass|fail), duration_seconds, csrf_token
```

**Success:**
```json
{ "success": true,
  "message": "Piece #5 (Size S) logged successfully as PASS under stage Sewing.",
  "details": { "batch_no": "B2507002", "size": "S", "serial": 5, "status": "pass" } }
```

**Failure flags:** same as verify, **plus** one extra that only exists here:

| Flag | Meaning |
|---|---|
| `duplicate_qr` | `QR Code Duplicate Error: QR code 'X' is already registered to another Production Batch 'Y'. All QR codes must be globally unique per company!` |

Server-side effects you should be aware of (do not replicate client-side): writes one `production_stage_logs` row with `qty_in=1`, and `qty_out=1,waste_qty=0` for PASS or `qty_out=0,waste_qty=1` for FAIL; `duration_minutes = max(1, ceil(duration_seconds/60))`; flips `production_orders.status` from `pending` to `running`.

---

## 3. THE QR PAYLOAD

Plain text. **Not JSON, not a URL.**

```
{production_no}-{SIZE}-{SERIAL:4 digits}
e.g.  B2507002-XXL-0001        BATCH-TOCCO-001-S-0005
```

Parsing is **right-to-left**, because the batch code may itself contain hyphens:

```dart
final parts = raw.split('-');
if (parts.length < 3) => invalid format
final serial  = int.parse(parts.removeLast());
final size    = parts.removeLast();
final batchNo = parts.join('-');
```

Client-side you only need this for display/pre-validation — **the server is authoritative**. Trim whitespace before sending. Do not upper-case or otherwise mutate the string.

---

## 4. SCREENS

One screen with two states, matching `qr_tracking.php`. Keep the phone-sized card aesthetic: light slate background `#F1F5F9`, white card, 24px corner radius, dark header `#0F172A` with white text, `Outfit` font (fallback system), **monospace for all codes**.

### 4.1 Header (persistent)

Title `QR Code Scanner Hub`, subtitle `Garment Floor Scan Unit`.
- Left: **Setup** back button — visible only in scanner state.
- Right: **Complete** button — visible only in scanner state.

### 4.2 State 1 — "Stage & Batch Setup"

- Factory icon in a light-blue circle, heading `Stage & Batch Setup`, subtext *"Select your active production batch to auto-load style WIP stages."*
- **1. SELECT STARTED PRODUCTION BATCH \*** — dropdown, monospace, default empty (`Select Batch`). **Always reset to empty when entering this state.**
- **2. SELECT WIP STAGE \*** — dropdown, **disabled** until a batch is chosen. Placeholder `-- Please Select Batch First --`; while fetching show `Loading Style WIP Stages...`; once loaded the first entry is `-- Step 2: Select WIP Stage --`.
- **Start Work / Scan** — full-width pill button, **disabled until both** batch and stage are selected.
- Footer: `Logged User: <name>` badge + **Logout** button.

Behaviour: changing the batch clears the stage dropdown and re-disables the Start button, then fetches §2.2.

### 4.3 State 2 — "Scanning Active"

- Red pill badge with a pulsing dot: `SCANNING ACTIVE: {STAGE}` (stage name upper-cased, underscores → spaces).
- **Camera picker** — only shown when more than one camera exists. Auto-select the first whose label contains `back`, `rear` or `environment`; otherwise the first device.
- **Viewport** — 4:3 aspect ratio, black, 16px radius, 3px `#0F172A` border, preview scaled **cover** (crop, never letterbox).
- **Manual entry card** — hidden by default; monospace centred text field, placeholder `e.g. BATCH-001-S-0005`, submit on Enter, plus a `Submit Scanned Code` button.
- **Switch to Manual Entry Mode** / **Switch to Camera Mode** text button below the viewport.
- **Footer stats** — `Scanned pieces: N` and `Elapsed: MM:SS` (ticks every 1s from the moment Start Work was pressed).

Both overlays below render **inside the viewport bounds**, over the camera preview.

#### Verified-item card (overlay, on verify success)

White, fills the viewport, scrollable, **no page scroll**. Contents top→bottom:

- Green badge `VERIFIED ACTIVE ITEM`
- `Batch Code:` **{batch_no}** (monospace, primary blue)
- Bordered light panel: `Style No:` / `Style Name:` / `Category:` → rendered as **`{category} | {brand}`** / `Fabric:` → `{composition}`
- Blue-tinted row: left `GARMENT SIZE` = `{size}`, right `SERIAL NO` = `#{serial padded to 4} / {target_qty padded to 4}` (e.g. `#0005 / 0500`)
- Bottom row: **PASS** (green) and **FAIL** (red) pill buttons, equal width.

#### Alert banner (overlay, on any failure)

Centred, ~92% width, dark translucent `rgba(15,23,42,0.96)` with blur, 2px border, white text, monospace message, and a `Tap to Continue` button.

| Condition | Title | Border / icon |
|---|---|---|
| `already_validated` | `ALREADY VALIDATED IN THIS STAGE` | blue `#3B82F6`, info icon |
| `failed_unit` | `FAILED / REJECTED UNIT BLOCKED` | red `#EF4444`, warning icon |
| `sequence_mismatch` | `SEQUENCE ORDER MISMATCH` | red, warning |
| generic verify failure | `SCAN REJECTED` *(see §7.1)* | red, warning |
| network error on verify | `CONNECTION ERROR` + *"Unable to connect to verification server. Please check internet connection."* | red |
| log failure | `LOGGING FAILED` | red |
| network error on log | `CONNECTION FAILURE` + *"Failed to communicate with production server."* | red |

**Auto-dismisses after 4500 ms**, then resumes scanning. `Tap to Continue` dismisses immediately and resumes.

---

## 5. SCAN FLOW / STATE MACHINE

```
IDLE ──decode or manual submit──► VERIFYING
```

1. **On decode:** immediately **pause** the camera (stop delivering frames) so the same code isn't decoded 20×/sec.
2. Show an inline info strip at the top of the scanner body: spinner + `Verifying Tag Authenticity...`
3. `POST …/verify` with `qr_code`, `stage` (the **key**), `csrf_token`.
4. **Success** → store `currentScannedCode`, populate and show the verified-item card. Camera stays paused.
   **Failure** → show the matching alert banner; after dismissal/timeout **resume** the camera.
5. **PASS/FAIL tapped:**
   - `durationSeconds = round((now - pieceStartTime) / 1000)` where `pieceStartTime` was set at Start Work and reset after every successful log.
   - Disable both buttons for the duration of the request; re-enable in a `finally`.
   - `POST …/log`.
   - **Success** → increment the piece counter, hide the card, show a toast for **2500 ms** (green for pass, red for fail) containing `data.message`, clear `currentScannedCode`, reset `pieceStartTime`, clear the manual field, and **resume the camera** (or refocus the manual field if in manual mode).
   - **Failure** → hide the card, show the `LOGGING FAILED` banner.

**Debounce:** ignore any decode while state is `VERIFYING`, while the verified-item card is showing, or while an alert banner is visible. Also ignore a re-decode of the *same* string within ~1.5s.

### Session controls

- **Complete** → confirmation dialog *"Complete scanning session and return to stage selection?"* → on confirm: stop the camera, stop the timer, return to State 1, **reset both dropdowns and disable the Start button**.
- **Setup** (back arrow) → same as Complete but **without** the confirmation dialog.
- **Android hardware back while scanning:** the web version deliberately locks back-navigation for floor operators (`history.pushState` + `onpopstate`). Replicate with `PopScope(canPop: false)` — swallow the back gesture and instead show the same Complete confirmation dialog.

### Lifecycle

Use `WidgetsBindingObserver`:
- `paused` / `inactive` / `hidden` → stop the camera and release it.
- `resumed` → if still on the scanner state and in camera mode, re-initialise the camera.
- Always dispose the controller in `dispose()`.

---

## 6. CAMERA & PERMISSIONS

Use **`mobile_scanner`** (ML Kit / AVFoundation) — do not embed a WebView running `html5-qrcode`.

- Formats: enable **QR plus the common 1D formats** (Code128, Code39, EAN13). The web version sets no format filter, so it decodes everything; matching that keeps handheld barcode guns and any legacy labels working.
- Detection speed: `noDuplicates`, with your own debounce on top.
- Torch toggle in the viewport corner is acceptable and welcome (the web version lacks one).

### Error → action mapping (mirrors `handleCameraError`)

| Failure | Action |
|---|---|
| Permission denied | Go straight to manual mode, toast: *"Camera permission was denied. Please enable camera access in settings."* |
| No camera hardware | Manual mode, toast: *"No camera detected. Switched to manual mode."* |
| Camera in use / cannot start / over-constrained | **Retry up to 3 times with a 1500 ms delay**, then fall back to manual mode. |

All fallback toasts show for **4000 ms**. Manual mode must autofocus the text field so a hardware scanner gun can type straight into it.

Add the platform bits: `NSCameraUsageDescription` in `Info.plist`, `<uses-permission android:name="android.permission.CAMERA"/>` in the manifest, and `minSdkVersion 21`.

---

## 7. INTENTIONAL DEVIATIONS FROM THE WEB VERSION

Implement these three improvements, and nothing else beyond parity:

### 7.1 Fix the mislabelled generic error
The web JS uses an `if/else-if/else` chain where the **final `else` assumes sequence mismatch**, so "Invalid tag format", "batch not found", "serial exceeds target" and "size not configured" all display under the title `SEQUENCE ORDER MISMATCH`. That is a display bug. Branch on the **explicit flags only**, and use the title `SCAN REJECTED` when no flag is present. Always show the server's `message` verbatim.

### 7.2 Audible + haptic feedback
The web version has **no sound and no vibration anywhere** — a real usability gap when the operator isn't looking at the screen. Add: a short high beep + light haptic on verify success, a low double-beep + heavy haptic on any rejection, and a distinct confirmation tick on a successful log. Make it a toggle in the app, default **on**.

### 7.3 Correct duration on the first piece
Keep the existing semantics (`pieceStartTime` set at Start Work, reset after each successful log) — this matches the server's `start_time = now - duration_seconds`. Just guard against a negative or absurd value: clamp `durationSeconds` to `[1, 86400]`.

**Do NOT add:** offline queueing, local caching of scans, background sync, or any client-side re-implementation of the sequence/quality gates. The server owns all validation.

---

## 8. CODE STRUCTURE

Follow whatever state management and folder conventions already exist in my app. If none are established, use:

```
lib/features/production_qr/
  data/
    qr_api_client.dart        // dio + persistent cookie jar, CSRF handling, 1x retry on 403
    models/  batch.dart  stage.dart  verify_result.dart  log_result.dart
  presentation/
    qr_login_page.dart
    qr_setup_page.dart        // State 1
    qr_scanner_page.dart      // State 2
    widgets/  verified_item_card.dart  scanner_alert_banner.dart  toast.dart
  qr_controller.dart          // session state machine
```

`baseUrl` must be configurable (a `--dart-define` or a settings field), not hard-coded — the ERP is multi-tenant and may be served from a subdomain. Persist the last-used value.

Model every failure as a sealed/typed result — never `dynamic`. Parse the four boolean flags explicitly:

```dart
sealed class VerifyResult {}
class VerifySuccess     extends VerifyResult { final Product product; }
class AlreadyValidated  extends VerifyResult { final String message; }
class FailedUnit        extends VerifyResult { final String message; }
class SequenceMismatch  extends VerifyResult { final String message; }
class GenericRejection  extends VerifyResult { final String message; }
class NetworkFailure    extends VerifyResult { final String message; }
```

---

## 9. ACCEPTANCE CHECKLIST

Work through these and tell me the result of each:

1. Login with a valid operator, kill the app, reopen → still authenticated (persistent cookie jar), scanner reachable without re-login.
2. Login with bad credentials → clear error, no crash, stays on login.
3. A user **without** `company.production.rfid_tracking` → friendly permission message, not a raw HTML dump or a redirect loop.
4. Batch dropdown lists only started batches; picking one loads stages in `#{order} {name}` order.
5. Start Work is disabled until both dropdowns have values.
6. Scan a valid QR → verified card shows correct style, `{category} | {brand}`, and `#0005 / 0500` zero-padded serial.
7. Tap PASS → green toast with the server message, counter increments, camera **auto-resumes** within ~1s.
8. Re-scan the same code in the same stage → blue `ALREADY VALIDATED IN THIS STAGE` banner, auto-dismiss at 4.5s.
9. Scan a code whose preceding stage has no log → red `SEQUENCE ORDER MISMATCH`.
10. Scan a code that FAILED in a preceding stage → red `FAILED / REJECTED UNIT BLOCKED`.
11. Scan a malformed string (e.g. `HELLO`) → `SCAN REJECTED` with the *Invalid tag format* message — **not** a sequence-mismatch title.
12. Enable airplane mode mid-session → `CONNECTION ERROR`, no crash, scanning resumes after dismissal.
13. Deny camera permission → falls back to manual entry with the field focused; typing a code + Enter runs the identical flow.
14. Background the app during scanning and return → camera reinitialises, elapsed timer still correct.
15. Android hardware back during scanning → shows the Complete confirmation, does not pop the route.
16. Complete → returns to setup with **both dropdowns cleared** and Start Work disabled.
17. Rapidly present the same QR to the camera for 3 seconds → exactly **one** verify request is sent.
18. Force a stale CSRF token → the client silently re-fetches, retries once, and the scan succeeds.

---

## 10. WHAT TO DO FIRST

Before writing code, reply with:

1. Which state management approach you'll use and why, given my existing app.
2. The exact `dio` + cookie-jar setup you'll use for session persistence, and how you'll handle the PHPSESSID rotation on login.
3. Anything in this spec that conflicts with my existing app structure.

Then implement, and report against §9.
