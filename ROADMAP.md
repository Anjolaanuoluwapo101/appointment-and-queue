# Roadmap — Patient Appointment & Queue Management System

Source of truth: `PRD-Patient-Appointment-Queue-Management.md`.
Payment decisions (locked): Paystack-only · Option B (book free, pay to join queue) ·
fee = department base + practitioner override · receptionist clears physical ·
auto-refund on cancel · no-show forfeits.
Stack: Laravel 13 + Inertia 3 + React 19 + Supabase Postgres + Sanctum + Reverb.

Legend: `[x]` done · `[ ]` todo. Update boxes as work lands.

Guardrails (PRD §4 Non-Goals — never build in MVP): clinical notes, diagnoses,
prescriptions, lab/radiology results, full medical records, full hospital billing
beyond consultation fees, pharmacy inventory. Not an EMR.

## Phase 0 — Foundation, Auth, Multi-tenancy (PRD §5, §20, §21, §22, §23, §24, §25)

- [x] Laravel + Inertia React + Vite + Tailwind scaffold
- [x] Supabase Postgres connection (`pgsql`, pooler)
- [x] Sanctum + Reverb installed, `HandleInertiaRequests` wired, `/` renders via Inertia
- [x] `scripts/setup.ps1` + `scripts/setup.sh` + Supabase/Reverb/Paystack `.env.example`
- [x] `hospitals` table + `hospital_id` scope on every tenant table (`BelongsToHospital` trait + container binding, console-safe) (PRD §20)
- [x] `users` + `patients` + `staff` + `practitioners` tables (profiles link to auth user; appointments/schedules/fees link practitioners) (PRD §20/§21/§22)
- [x] Phone unique + lookup index on patients (receptionist search + walk-in lookup by phone) (PRD §12/§16/§21)
- [x] Admin seeder: first hospital + hospital admin account (no Super Admin UI in MVP, so bootstrap via seeder) (PRD §5)
- [x] Timezone Africa/Lagos for app + scheduler (slots, reminders, daily queue reset, morning summaries) (PRD §8/§9/§14)
- [x] Roles on users (`patient/receptionist/practitioner/admin`) + `is_active` + RBAC helpers + `role` middleware alias ("Practitioner" term everywhere, never Doctor/Consultant) (PRD §5)
- [x] `/login/patient` + `/login/staff` portals with role-based routing + guest redirect (PRD §24)
- [x] Dual session lifetimes via middleware (staff 8h / patient 30d, env-overridable) (PRD §24)
- [x] Admin settings UI: session lifetimes + queue-low threshold (settings store, middleware reads settings first) (PRD §14/§24)
- [x] Email password reset for patients + staff (PRD §24)
- [x] Patient self-registration (account + patient profile, attached to first active hospital) (PRD §3/§21)
- [ ] `services` entity decision: model as department-level services list for browsing/booking (PRD §20 lists it; booking browses "departments/services" per §3/§7)
- [ ] Walk-ins as `is_walk_in` flag on appointment/queue record (no separate table), always with patient profile (PRD §20 notes)
- [ ] Patient profile fields: required (name, phone, email, password, DOB, gender, address) + optional secondary contact (PRD §21)
- [x] Patient profile management: view/edit own profile (PRD §21/§26)
- [x] Receptionist patient registration: create full patient profile on behalf of arrivals (PRD §5)
- [ ] Practitioner profile: required (name, department(s), specialisation) + optional (photo, qualifications, bio shown at booking, internal contact, availability Active/On Leave/Unavailable) (PRD §22)
- [x] Departments seed (10 Nigerian defaults with prefixes + placeholder ₦5,000 fees)
- [x] Department admin CRUD; deactivated hidden from booking; delete blocked when practitioners/appointments linked (deactivate instead) (PRD §23)
- [x] Staff + practitioner account management UI (create/deactivate receptionist, practitioner, admin accounts) (PRD §5/§26)
- [x] Department `queue_prefix` per department (C/G/P…, admin-configurable, feeds queue numbering) (PRD §9)
- [x] `practitioner_departments` many-to-many (PRD §8, §20)
- [x] i18n architecture: React i18next + key-based EN bundle (Yoruba/Igbo/Hausa post-MVP) (PRD §25)

## Phase 1 — Scheduling (PRD §8)

- [x] `practitioner_schedules`: working days/hours, duration, max per slot, breaks, days off (per practitioner per department)
- [x] `schedule_exceptions`: day-off + adjusted-hours overrides with affected-booking warning
- [x] `appointment_slots`: idempotent generation from schedules; full slots unbookable (`bookable` scope)
- [x] Booking window + cutoff in settings store (window drives generation horizon; cutoff enforced at booking in Phase 2)
- [x] `SlotGenerator` service + `slots:generate` command + daily scheduler entry
- [x] Schedule modification blocking: update/delete refused with booked-slot list; clean updates regenerate future unbooked slots
- [x] Overlap guard + practitioner-department link validation
- [x] Availability status gates generation (only `active` practitioners generate slots)
- [x] Practitioner-optional booking: department-only appointments allowed where applicable (enforced at booking in Phase 2)
- [x] Minimal practitioner admin CRUD (profile + departments + availability; login linking later)
- [x] Admin UI: schedule CRUD + exception CRUD + window/cutoff/duration/capacity config

## Phase 2 — Booking + Paystack (PRD §6, §7, §13 + payment delta)

- [x] `consultation_fees` (dept base + practitioner override via partial unique indexes; server is source of truth) + `FeeResolver`
- [x] `payments` (`pending/success/failed/abandoned/refunded`, unique provider reference) + `payment_logs` (raw payloads)
- [x] `appointments`: `payment_mode` + `payment_status` + `payment_id`, `is_walk_in`, cancellation reason, reschedule count, check-in attribution
- [x] Department `payment_mode`: `allow_both` / `physical_only` / `online_required` enforced at booking
- [x] Booking flow: free booking holds slot, fee shown, Pay Online Now / Pay at Hospital; cutoff + availability + dept-link enforced; practitioner optional (follows slot)
- [x] Appointment conflict protection: atomic conditional `booked_count` increment, transactional (PRD §26 backend)
- [x] Paystack: `initialize` → authorization_url, `verify` endpoint, `webhooks/paystack` (CSRF-exempt, signature-checked, idempotent, per-hospital secret)
- [x] Paystack keys stored per hospital (encrypted DB) with `.env` fallback for single-hospital MVP (PRD §5 admin + §27)
- [x] Reconcile command (`payments:reconcile`, hourly): stale pending → paid/failed/abandoned
- [x] Patient UI: browse departments/practitioners (fees shown), slot filter, book, pay now, verify callback, receipt, cancel with reason, reschedule (payment carries over)
- [x] Staff UI: daily appointments with filters, phone lookup, book on behalf, reschedule, cancel, no-show
- [x] Cancellation: slot freed immediately + auto-refund job (3x retry, manual-resolution log) + reason captured
- [x] Reschedule keeps payment link + records history/count (same-department; practitioner follows new slot for replacements)
- [x] Actor attribution on check-ins (`checked_in_by/at`)
- [x] No-show: forfeit (no refund dispatched, payment preserved)
- [x] Bulk day cancellation + replacement moves (same-date free slot, else cancel+refund) with preview counts; MVP bulk alerts fire in Phase 4 (moved from Phase 1) (PRD §13/§14)
- [x] Appointment + payment policies (patient owns / staff same hospital)
- [x] Appointment statuses end-to-end: all 8 implemented

## Phase 3 — Check-in + Queue + Realtime (PRD §9, §10, §11, §12)

- [x] `queue_entries` (statuses, recall flag, cancel reason, timing stamps, actor) + `queue_number_sequences` (daily row per dept) + department `room_label`
- [x] Check-in gate: paid/waived auto-clear + queue; unpaid parks at Pending Clearance
- [x] Manual clearance: physical payment recorded (receipt + method) + queued
- [x] Walk-in quick-add (physical-only): phone find-or-create + collect + clear + queue; history preserved
- [x] Numbering: transactional per-day sequence, dept prefix + zero-padded (C001…), daily reset implicit
- [x] Staff queue actions: call next (FIFO, recalled first, practitioner filter), call, begin consultation, skip (stays visible), manual recall to next position, complete (+appointment completed), cancel (queue-only, stays visible, appointment intact)
- [x] Queue statuses: all 6; full history preserved
- [x] Pre-check-in card vs post-check-in queue view on patient page
- [x] Realtime via Reverb + Echo: public `queue.{dept}` channel (snapshot) + private `patient.{id}` channel (position); staff/practitioner boards + patient page subscribe; display board live
- [x] Public display board `/display/queue/{department}`: no login, Currently Serving + Next 3
- [x] Patient position: queue number, serving, patients ahead, room, called instruction
- [x] Practitioner board: today's schedule + shared queue filtered to own patients; practitioners blocked from check-in/clear/walk-in, scoped to own entries for queue ops
- [x] Channel auth: public queue channel open; private patient channel restricted to owner + same-hospital staff

## Phase 4 — Notifications + Dashboards + Reports + Audit (PRD §14, §15, §16, §17, §19)

- [x] Email delivery (MVP channel) + `notifications` vs `notification_logs` (sent + delivery status) + retry/escalation on delivery failure (PRD §14/§20)
- [x] Laravel scheduler + queue worker: day-before reminders, 2h day-of reminders, practitioner morning summaries, pending-payment reconcile (PRD §14)
- [x] MVP emails only (PRD §14 MVP list + payments): welcome, password reset, appointment confirmed, reminders (day-before + 2h day-of), cancellations (both sides), reschedule confirmations, schedule-conflict-blocked, bulk-cancellation alerts, check-in/queue-number assigned, pay link/success/failed/refunded, pending/cleared, progressive queue emails at 5/3/1-ahead thresholds + you're-next (realtime ticks via WebSocket, not email), called, practitioner daily schedule summary
- [x] Flow-closure notices (MVP-adjacent, confirm): consultation completed, queue entry cancelled by staff, walk-in registered on their behalf — closes the central workflow loop, not in PRD MVP list explicitly
- [ ] Post-MVP emails (do NOT build now): new-device/failed-login security alerts, practitioner-late/unavailable, low/empty-queue, first-patient-ready, skip/recall/no-show notices, attendance confirmation + inactivity reminder, long-consult/long-queue alerts, end-of-day/weekly/monthly summaries, system-health/backup alerts, maintenance broadcasts, Super Admin events, WhatsApp channel
- [x] Patient dashboard: upcoming (fee + pay choice + receipt), history + payment status, realtime queue, notifications
- [x] Receptionist dashboard: today's appointments (paid badges), walk-in quick-add, pending check-in/clearance (online-pending vs awaiting-cash + receipt modal), waiting/in-consultation/completed/no-show/cancelled
- [x] Practitioner dashboard: today's schedule, current queue, current patient, completed
- [x] Admin dashboard: appointment/queue stats, utilisation, no-show/cancel stats, waiting/consultation averages, payment summary (paid/pending/failed/refunded) + refund-failure inbox, links to schedules/departments/staff/fees/keys
- [x] Search by name/phone/patient ID/appointment ID/queue number/practitioner/department; filter by date/department/practitioner/appointment status/queue status (PRD §16)
- [x] Reports (ASSUMED MVP — PRD §3/§17 imply MVP but §26 lists reports/exports as V2+; confirm if contested): in-dashboard + PDF/Excel via export libs, filterable by date/dept/practitioner — appointments (incl. cancellation reasons, reschedule frequency, booking lead time), queue (incl. completion rate, skip/recall frequency), operational (incl. clearance time, peak hours, walk-in ratio), payments (online vs physical, refunded, failures), patients, staff (check-ins + queue actions per staff member)
- [x] Audit log: user/action/record/timestamp/before-after (e.g. check-in, call, consult, complete, pay, clear, refund); admin-only read + PDF/Excel export

## Phase 5 — Hardening + Deploy (PRD §18, §27)

- [x] Password hashing via framework default (bcrypt, BCRYPT_ROUNDS); secure authentication (PRD §18)
- [x] RBAC per-row checks (no cross-patient access via ID tampering), client + server validation, HTTPS, Sanctum session/token security
- [x] Rate limiting: auth, booking, pay init/verify, webhooks
- [x] Paystack: signature verify, server-side amounts, idempotency, tamper/double-webhook tests, test-mode E2E (success/fail/refund/reconcile)
- [x] Feature-test coverage on critical paths: slot conflict/double-booking, webhook idempotency, queue ordering + skip/recall, auto-refund, RBAC boundaries
- [x] Network status indicator in UI (staff alerted on connectivity drop)
- [ ] Mail provider chosen and off `log`; provider-down monitoring (email + Paystack integration failures)
- [ ] Supabase backup verification for prod data
- [x] `vendor/bin/pint` clean, full test suite green
- [ ] Prod deploy: monolith Laravel + Inertia on one host + Postgres (Inertia can't split across Vercel cleanly, overriding PRD §27's split suggestion; Fly.io/Railway same-instance or VPS), `APP_URL` public, Paystack live keys + webhook URL, Reverb prod host/scheme, mail provider off `log`

## Open decisions (need answers before/during build)

1. Reports MVP vs V2 (PRD §26 says V2+, §3/§17 imply MVP) — currently assumed MVP.
2. ~~Queue scope~~ — DECIDED: department queue + practitioner filter.
3. ~~Called-room source~~ — DECIDED: free-text room field on department, shown in called instruction.
4. ~~Queue-entry cancel semantics~~ — DECIDED: queue-only removal (appointment stays for history); cancelled entries stay visible to queue viewers + audit-logged.
5. Cancellation cutoff: any time before the appointment, or a cutoff (e.g. booking cutoff)?
6. Flow-closure notices as MVP email (completed, entry-cancelled, walk-in-registered) — confirm in or out.

## Post-MVP / V2+ (explicitly out)

- Advanced reports/exports beyond MVP set, QR/kiosk self check-in, WhatsApp notifications, security/system-health/end-of-day/weekly/monthly alerts, Super Admin + multi-hospital UI, Yoruba/Igbo/Hausa, session-based scheduling alternative
