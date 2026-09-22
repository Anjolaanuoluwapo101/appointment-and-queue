# PRD — Patient Appointment & Queue Management System

---

## 1. Product Overview

A web-based system that allows patients to book and manage hospital appointments while enabling hospital staff to manage schedules, patient check-ins, and daily queues from a centralised dashboard.

The system provides patients with visibility into their appointment and queue status while giving hospital staff the tools to control the flow of patients through outpatient services.

Built for single hospital/clinic deployment, with multi-hospital and multi-branch support baked into the data model from day one via `hospital_id` scoping.

**Primary users:**

- Patients
- Reception / front-desk staff
- Practitioners (doctors, nurses, physiotherapists, pharmacists, lab scientists, and any other healthcare worker who sees patients)
- Hospital administrators

---

## 2. Problem

Appointment scheduling and patient queue management involve significant manual coordination.

Patients may need to:

- Visit or call the hospital to arrange appointments.
- Wait at the hospital without knowing their position in the queue.
- Contact staff to confirm or change appointments.
- Spend unnecessary time waiting when schedules change.

Hospital staff may need to:

- Maintain appointment schedules manually.
- Prevent appointment conflicts.
- Register arriving patients.
- Organise daily queues.
- Track which patients have been called or attended to.
- Coordinate between reception and consulting rooms through phone calls or manual signals.
- Deal with cancellations and no-shows.

The product provides a **single digital workflow from appointment booking through patient check-in and queue completion**, eliminating manual coordination between reception and consulting rooms.

---

## 3. Goals

### Patient goals

Patients should be able to:

- Create an account.
- Find an appropriate department/service.
- Select a practitioner where applicable.
- See available appointment slots.
- Book an appointment (free, holds slot).
- Pay online via Paystack now or choose Pay at Hospital.
- Reschedule or cancel appointments (auto-refund if paid online).
- View upcoming and previous appointments.
- Check in for an appointment.
- Receive a queue number.
- Monitor queue status in real time.
- Receive relevant notifications.

### Hospital goals

Hospital staff should be able to:

- Configure practitioner schedules.
- Manage appointment slots.
- View upcoming appointments.
- Check patients in.
- Collect consultation fees online (Paystack) or clear physical payments for queue entry.
- Manage the daily queue.
- Call patients in order.
- Update patient status.
- Handle cancellations and no-shows.
- Monitor department activity.
- Generate and export operational reports.

---

## 4. Non-Goals

For the first version, the system **will not** attempt to become an EMR.

It will not manage:

- Clinical notes
- Diagnoses
- Prescriptions
- Laboratory results
- Radiology results
- Full patient medical records
- Full hospital billing (inpatient, pharmacy, lab, multi-item invoicing)
- Pharmacy inventory

**Note on payment:** Many Nigerian hospitals have a cashier step between check-in and seeing the practitioner. The system collects the consultation fee for appointments via two modes — **pay online directly (Paystack)** or **pay physically at the hospital (cash/transfer/POS)**.

Booking is free and holds the slot. Payment is enforced at queue entry (Option B): a patient who has paid online is auto-cleared at check-in; a patient who has not paid goes to `Pending Clearance` and the receptionist clears them after physical payment (receipt no + method required). Admin configures per department: `allow_both` (default), `physical_only`, or `online_required`, plus the fee. Cancellation after successful online payment triggers auto-refund via Paystack. No-show after payment is forfeited (no auto-refund).

This product is a **patient appointment and flow system**, not a replacement for hospital clinical information systems.

---

## 5. User Roles

### Patient

Can manage only their own appointments and queue information.

### Receptionist / Queue Staff

Can:

- View appointments.
- Register and check in patients.
- Clear patients for queue entry after physical payment (receipt no + method required, no separate Cashier role in MVP).
- Add walk-in patients.
- Manage queues.
- Call patients.
- Update queue statuses.

### Practitioner

Replaces the term "Doctor/Consultant" throughout the system and codebase. Covers any healthcare worker who sees patients — doctors, nurses, physiotherapists, pharmacists, lab scientists, etc.

Can:

- View their schedule.
- View patients assigned to their queue.
- Call the next patient.
- Mark consultations as completed.
- Skip or recall patients.

### Hospital Administrator

Can:

- Manage users and staff accounts.
- Manage departments.
- Manage practitioners.
- Configure schedules.
- Configure appointment rules.
- Configure payment per department (mode: allow_both / physical_only / online_required; base fee).
- Configure consultation fees (per-department base + optional per-practitioner override).
- Configure Paystack keys per hospital and review refund failures.
- View and export reports.
- View and export audit logs.
- Access system-wide settings.

### Super Admin *(Post-MVP — Multi-hospital)*

Can:

- Onboard new hospitals.
- Manage hospital admin accounts.
- Monitor cross-hospital activity.

---

## 6. Core Patient Workflow

```
Create account
      ↓
Select department/service
      ↓
Select practitioner (if applicable)
      ↓
Select date
      ↓
Select available time slot
      ↓
Confirm appointment (free — holds slot, shows fee)
      ↓
Choose: [Pay Online Now via Paystack | Pay at Hospital]
      ↓
Appointment confirmed (paid OR unpaid)
      ↓
Patient arrives
      ↓
Check-in (by receptionist)
      ↓
Paid? → Auto-cleared → Queue number generated
Unpaid? → Pending Clearance → Pay physically → Receptionist clears (receipt no + method) → Queue number generated
      ↓
Waiting
      ↓
Called
      ↓
In consultation
      ↓
Completed
```

Payment is enforced at queue entry (Option B), not at booking. Booking always holds the slot. Per-department mode (`allow_both` default, `physical_only`, `online_required`) controls which choices the patient sees. Walk-ins are physical-only in MVP.

This is the **central workflow around which the entire product is designed.**

---

## 7. Appointment Management

### Patient

Patients can:

- Browse departments/services.
- View practitioners.
- View available dates and time slots.
- Book appointments (free — fee shown, slot held).
- Pay online via Paystack now or choose Pay at Hospital.
- View appointment details (including payment status + receipt).
- Cancel appointments (auto-refund if paid online).
- Reschedule appointments.
- View appointment history.

### Staff

Staff can:

- View daily appointments.
- Search appointments.
- Filter by practitioner, department, date and status.
- Create appointments on behalf of patients.
- Modify appointments.
- Cancel appointments.
- Mark appointments as no-show.

### Appointment statuses

```
Scheduled
Checked In
Pending Clearance   ← checked in but unpaid; awaiting online or physical payment
Cleared             ← paid online (auto) or cleared physically by receptionist
In Queue
Completed
Cancelled
No-show
```

### Payment statuses (orthogonal to appointment status)

```
unpaid      ← booked with Pay at Hospital, not yet paid
pending     ← Paystack initialized, awaiting verification/webhook
paid        ← Paystack verified (webhook or verify endpoint)
failed      ← Paystack failed/abandoned
refunded    ← auto-refunded on cancellation after payment
waived      ← admin exception (MVP manual flag)
```

Server always loads the fee (department base → practitioner override); client amount is never trusted. Unpaid bookings still hold the slot in MVP (no expiry).

---

## 8. Scheduling

Administrators configure:

- Practitioner working days.
- Practitioner working hours.
- Appointment duration.
- Maximum appointments per slot/session.
- Break periods.
- Days off.
- Temporary schedule exceptions.
- Booking window — how far ahead slots are visible and bookable (e.g., 30 days).
- Booking cutoff — how close to the appointment time bookings are blocked (e.g., 2 hours before).

**Example:**

**Dr. Ade — Cardiology**

Monday: `09:00 – 13:00`
Appointment duration: `30 minutes`

Available slots:
```
09:00
09:30
10:00
10:30
11:00
...
```

Once a slot reaches capacity it is no longer available for booking.

### Schedule modification policy

If an admin attempts to modify a practitioner's schedule and existing bookings fall within the affected window, **the system blocks the change** and displays the conflicting appointments. The admin must resolve all conflicts (cancel or reschedule each affected appointment) before the schedule change is saved.

This prevents double-handling across multiple user roles.

### Multi-department practitioners

A practitioner can belong to more than one department. Their schedule is configured independently per department.

---

## 9. Queue Management

When a patient checks in and is paid (online via Paystack, auto-cleared) or cleared physically by the receptionist, the system places them into the appropriate department queue with a department-scoped queue number.

**Queue numbering format:** Department-prefixed, sequential per day.

```
Cardiology:   C001, C002, C003...
General OPD:  G001, G002, G003...
Paediatrics:  P001, P002, P003...
```

Counters reset daily.

**Example queue view (staff):**

```
CARDIOLOGY
-------------------------
Currently serving: C012

C013  Waiting
C014  Waiting
C015  Waiting
C016  Waiting
C017  Waiting
```

### Staff queue actions

Staff can:

- View the queue.
- Call the next patient.
- Skip a patient.
- Recall a skipped patient (they rejoin at the next position).
- Mark a patient as completed.
- Cancel a queue entry.

### Skip logic

When a patient is skipped:

```
Called → no response → Skipped
```

The skipped patient's entry remains visible on the queue dashboard with a "Skipped" status. The receptionist or practitioner can recall them at any time — they rejoin at the next position in the active queue. If they are not recalled, staff can cancel their entry.

No automatic recall logic in MVP. Staff are fully in control.

### Queue statuses

```
Waiting
Called
In Consultation
Completed
Skipped
Cancelled
```

The system preserves the patient's full queue history rather than overwriting statuses.

---

## 10. Patient Queue View

The patient's dashboard shows their real-time queue position, updated via WebSockets:

```
Today's Appointment

Cardiology
Dr. Ade

Appointment: 10:00 AM

Queue Number
C017

Currently Serving
C013

Patients Ahead
3

Status
Waiting
```

Progressive updates as the queue advances:

```
Patients ahead: 5 → 3 → 1 → You're next
```

When called:

```
C017

Please proceed to Consultation Room 2.
```

Before check-in, the patient sees their appointment card only. The queue view activates after check-in and queue number assignment.

---

## 11. Public Queue Display Board

A read-only, real-time display view intended for TV screens in hospital waiting rooms. No login required.

**URL format:**
```
/display/queue/{department_id}
```

Each department gets its own display URL. A hospital with multiple departments can run multiple screens simultaneously.

**Display format:**
```
CARDIOLOGY
Currently Serving: C014
Next: C015, C016, C017
```

Driven by WebSockets — updates in real time without refresh.

---

## 12. Check-In

There is a clear distinction between:

**Appointment** — "I have an appointment."

**Check-in** — "I am physically here and ready to be seen."

```
Appointment (paid OR unpaid)
    ↓
Checked In (by receptionist)
    ↓
Paid? → Auto-cleared → Queue Number Generated
Unpaid? → Pending Clearance → Pay online (Paystack) OR Pay physically → Receptionist clears → Queue Number Generated
```

**Staff check-in flow (MVP):**
Patient arrives → receptionist searches by phone number or name → locates appointment → clicks Check In → if `paid`, system auto-clears and generates queue number; if `unpaid/failed`, patient goes to Pending Clearance.

**Online payment flow (MVP — Paystack only):**
Book → `POST /appointments/{id}/pay/initialize` → authorization_url → patient pays → `GET /appointments/{id}/pay/verify` + `POST /webhooks/paystack` (`charge.success`) → `payments.success → appointments.paid`. Webhook is idempotent on provider `reference`. Pending >30min is reconciled by cron. Amount is always resolved server-side from consultation fees.

**Physical payment flow:**
Patient chooses Pay at Hospital → checks in to Pending Clearance → pays cash/transfer/POS → receptionist enters receipt no + method → clicks Cleared → queue number generated.

Patient self-check-in (QR code or kiosk) is a post-MVP feature.

### Walk-ins

Patients without a prior appointment can be added directly by the receptionist.

**Walk-in flow (physical-only in MVP):**
Receptionist enters phone number → if patient exists, their profile loads → if not, receptionist fills in name and phone number → collects payment physically → clears → queue number generated immediately.

Walk-in patients get a proper patient record so their visit history is preserved for future visits.

---

## 13. Cancellation & No-Show

### Cancellation

Patient or staff cancels before the appointment.

```
Scheduled → Cancelled
```

The slot becomes immediately available for rebooking with no buffer period.

**Refund rule (MVP):** If `payment_status=paid` (online via Paystack), cancellation triggers auto-refund via Paystack refund API (`paid → refunded`). Slot is freed immediately; refund is async with 3x retry. On final failure, admin is notified with the payment reference for manual resolution. If unpaid, no refund needed.

### No-show

Patient doesn't arrive. Staff marks:

```
Scheduled → No-show
```

Recorded for reporting purposes.

**No-show after online payment:** forfeited — no auto-refund. Admin may issue a manual exception refund.

---

## 14. Notifications

Notifications are event-driven. MVP delivery channel: **email**. Post-MVP: **WhatsApp** (prioritised over SMS given Nigerian market penetration).

Real-time queue position updates on the patient dashboard are delivered via **WebSockets** and are separate from push notification events.

---

### Patient notifications

- Account created successfully (welcome email)
- Login from new device
- Multiple failed login attempts
- Password reset requested
- Password changed successfully
- Profile updated confirmation
- Account deactivated
- Appointment confirmed
- Appointment reminder (day before)
- Appointment reminder (day of — 2 hours before)
- Inactivity reminder (appointment is tomorrow, patient hasn't confirmed attendance)
- Appointment rescheduled by staff
- Appointment rescheduled by patient (confirmation)
- Appointment cancelled by staff
- Appointment cancelled by patient (confirmation)
- Appointment slot no longer available
- Practitioner changed for their appointment
- Department changed for their appointment
- Schedule change affecting their appointment
- Bulk cancellation affecting their appointment (e.g., practitioner sick day)
- Checked in / queue number assigned
- Payment link sent (Pay Online Now chosen)
- Payment successful — receipt available
- Payment failed — retry required
- Payment refunded (auto-refund on cancellation)
- Payment clearance pending (Pay at Hospital, awaiting physical payment)
- Payment cleared — now in active queue
- Queue position update (progressive — 5 ahead, 3 ahead, 1 ahead)
- You're next in queue
- Called — please proceed to [room]
- Queue entry cancelled by staff
- Consultation completed
- Walk-in registered on their behalf

---

### Receptionist notifications

- New appointment booked in their department
- Appointment rescheduled by patient
- Appointment cancelled by patient
- Practitioner changed for an appointment in their department
- Bulk cancellation triggered in their department
- Patient checked in
- Patient pending payment clearance
- Payment cleared for patient
- Patient arrived early (appointment not for another hour)
- Skipped patient ready for recall
- Patient marked as no-show by practitioner
- Queue entry cancelled by practitioner
- Queue running low (configurable threshold)
- Queue empty for the day
- Walk-in added to queue
- Practitioner running late
- Practitioner unavailable / schedule cancelled
- End of day summary for their department

---

### Practitioner notifications

- Daily schedule summary (morning of)
- New appointment booked in their schedule
- Appointment cancelled for a slot in their schedule
- Walk-in added to their queue
- Day's appointments modified in bulk
- Replacement practitioner assigned to their patients
- Schedule modified by admin
- First patient ready in queue
- Next patient called
- Patient no-showed for their slot
- Patient marked as skipped
- Patient recalled
- Consultation time running long alert
- Queue growing unusually long
- Queue cleared for the day

---

### Admin notifications

- New patient registered
- Patient account flagged or deactivated
- New staff account created
- Staff account deactivated
- Role permissions changed for a staff member
- Failed login attempts spike
- New device login for admin account
- Practitioner added to system
- Practitioner schedule created
- Practitioner schedule modified
- Practitioner marked as unavailable
- Replacement practitioner assigned
- Bulk appointment cancellation triggered
- Schedule conflict detected and blocked
- Department created
- Department modified
- Payment mode / fee changed for a department
- Paystack refund failed (requires manual resolution)
- Paystack webhook / verification failure
- High no-show rate alert
- Appointment cancellation spike
- Queue backlog alert per department
- Unusual audit log activity
- Failed notification delivery
- Notification queue backed up
- Integration failure (email/WhatsApp provider down)
- Database backup completed
- High load / performance degradation alert
- End of day operational summary per department
- Weekly report ready
- Monthly report ready
- Audit log export requested and completed

---

### Super Admin notifications *(Post-MVP)*

- New hospital onboarded
- Hospital admin account created
- Hospital subscription expiring
- Cross-hospital anomaly detected

---

### System / Cross-cutting

- Notification delivery failure (retry or escalate)
- Scheduled maintenance window alert (all users)

---

### MVP vs Post-MVP notifications

**MVP:**
Appointment confirmed, reminders (day before and day of), cancellations, check-in and queue number assigned, progressive queue updates, called notification, password reset, welcome email, daily schedule summary for practitioners, schedule conflict blocked, bulk cancellation alert, payment successful / failed / refunded, payment cleared, refund failure (admin).

**Post-MVP:**
Security alerts (new device, failed login spike), system health alerts, end of day summaries, weekly/monthly reports, WhatsApp delivery, Super Admin events.

---

## 15. Dashboards

### Patient dashboard

- Upcoming appointment (with fee + Pay Online Now / Pay at Hospital choice + receipt)
- Appointment history (including payment status)
- Current queue status (real-time)
- Notifications

### Receptionist dashboard

- Today's appointments (with paid / unpaid / pending badge)
- Walk-in quick-add (physical-only)
- Patients pending check-in
- Patients pending payment clearance (unpaid-online-pending vs awaiting-cash, with receipt modal)
- Waiting patients
- Currently in consultation
- Completed patients
- No-shows
- Cancelled appointments

### Practitioner dashboard

- Today's schedule
- Current queue
- Current patient
- Completed consultations

### Admin dashboard

- Appointment statistics
- Queue statistics
- Practitioner utilisation
- No-show statistics
- Cancellation statistics
- Average waiting and consultation times
- Payment summary (paid / pending / failed / refunded) + refund failure inbox
- Quick links to schedule management, department management, staff management, fee and Paystack-key management

---

## 16. Search & Filtering

Staff can search by:

- Patient name
- Patient phone number
- Patient ID
- Appointment ID
- Queue number
- Practitioner
- Department

And filter by:

- Date
- Department
- Practitioner
- Appointment status
- Queue status

---

## 17. Reports

Reports are readable within the dashboard and exportable as PDF or Excel.

Filterable by date, department and practitioner.

**Appointment reports**
- Total appointments
- Completed vs cancelled vs no-show breakdown
- Cancellation reasons breakdown
- Rescheduling frequency
- Booking lead time (how far in advance patients book)

**Queue reports**
- Total patients per day/week/month
- Average waiting time per department/practitioner
- Average consultation time per practitioner
- Queue completion rate
- Skipped and recalled patient frequency

**Operational reports**
- Peak hours analysis per department
- Practitioner utilisation rate
- Walk-in vs booked patient ratio
- No-show rate trends over time
- Payment clearance time (pending → cleared)

**Payment reports (MVP)**
- Online (Paystack) vs physical collections per department/day
- Successful / failed / refunded breakdown
- Refund failures requiring manual resolution

**Patient reports**
- New patient registrations over time
- Returning vs new patient ratio
- Patient volume per department

**Staff reports**
- Check-ins processed per receptionist
- Queue actions per staff member

---

## 18. Security

- Secure authentication
- Password hashing
- Role-based access control (RBAC)
- Authorization checks on every data access — a patient cannot retrieve another patient's data by modifying an ID in a URL or API request
- Input validation (client and server side)
- HTTPS
- Session and token security
- Rate limiting on booking, authentication, pay initialize/verify, and webhook endpoints
- Paystack webhook signature verification + server-side amount resolution + idempotency on provider reference
- Audit logging (including payment init/verify/webhook/clear/refund)
- Network status indicator in UI — staff are alerted immediately if connectivity drops

---

## 19. Audit Log

Important actions are recorded with:

- User
- Action
- Record affected
- Timestamp
- Relevant before/after values where appropriate

**Example:**
```
10:03 — Receptionist checked in Patient #1023
10:17 — Practitioner called C014
10:41 — C014 marked In Consultation
11:05 — C014 marked Completed
```

Read access: Admin only.
Export: Available to admin (PDF or Excel) for compliance and incident investigation.

---

## 20. Core Data Model

### Entities

```
hospitals
users
patients
staff
departments
practitioners
practitioner_departments
services
practitioner_schedules
schedule_exceptions
appointment_slots
appointments
consultation_fees
payments
payment_logs
queue_entries
queue_number_sequences
walk_ins
notifications
notification_logs
audit_logs
```

### Key relationships

```
Hospital
   ↓
Departments (payment_mode + base_fee_kobo)
   ↓
Practitioners (many-to-many via practitioner_departments)
   ↓
Schedules
   ↓
Appointment Slots
   ↓
Appointments (payment_mode + payment_status + payment_id)
   ↓
Payments via Paystack (online) or manual clearance (physical)
   ↓
Check-in
   ↓
Paid? Auto-cleared : Pending Clearance → Cleared
   ↓
Queue Entry
```

### Notes

- Everything is scoped to `hospital_id` from day one to support multi-hospital and multi-branch deployments.
- `queue_number_sequences` tracks the current day's counter per department, reset daily.
- `notification_logs` is separate from `notifications` — records what was sent and delivery status.
- Walk-ins are flagged on the appointment/queue entry record rather than a separate table, but a patient profile is always created.
- `consultation_fees` holds per-department base fee with optional per-practitioner override; server is source of truth for amounts (NGN/kobo).
- `payments` holds one row per Paystack or manual collection attempt (`pending/success/failed/abandoned/refunded`), keyed by unique provider `reference` for idempotency.
- `payment_logs` stores raw Paystack init/verify/webhook/refund payloads for audit and dispute resolution.

---

## 21. Patient Profile

### Required at registration

```
Full name
Phone number
Email
Password
Date of birth
Gender
Address
```

### Optional

```
Secondary contact name
Secondary contact phone number (preferably a family member)
```

---

## 22. Practitioner Profile

### Required

```
Full name
Department(s)
Specialisation
```

### Optional (can be completed later)

```
Profile photo
Qualifications
Bio (visible to patients during booking)
Internal contact information
Availability status (Active / On Leave / Unavailable)
```

---

## 23. Departments

The system ships with a preloaded set of common Nigerian hospital departments:

```
General OPD
Cardiology
Paediatrics
Gynaecology
Orthopaedics
ENT
Ophthalmology
Dermatology
Dental
Emergency
```

Admins can edit, deactivate, delete or create departments freely.

---

## 24. Authentication & Sessions

### Login portals

```
/login/patient    ← patient-facing
/login/staff      ← receptionist, practitioner, admin (role-based routing after login)
```

### Password reset

Standard email-based reset for both patients and staff.

### Session duration

- Staff: 8 hours (covers a full shift)
- Patients: 30 days (personal devices, convenience prioritised)

Both configurable by admin.

---

## 25. Internationalisation

English only for MVP.

The codebase uses i18n architecture (translation files) from the start so that Yoruba, Igbo and Hausa can be added later without a painful retrofit. When extending to other languages, Google Translate API is used as a base, reviewed and corrected by native speakers for medical/clinical accuracy.

---

## 26. MVP Scope

### Patient
- Registration and login
- Profile management
- Browse departments and practitioners (with fees shown)
- View available slots
- Book (free, holds slot), pay online via Paystack now or Pay at Hospital, cancel (auto-refund if paid) and reschedule appointments
- View appointment, payment (status + receipt) and queue status (real-time)
- Email notifications (core events including payment success/failed/refunded)

### Receptionist/Staff
- Login
- Appointment dashboard (paid / unpaid / pending badges)
- Walk-in quick-add (physical-only)
- Check-in (auto-clears if paid, else Pending Clearance)
- Physical payment clearance with receipt no + method
- Queue dashboard
- Call next patient
- Skip and recall patients
- Update queue status
- Search and filter

### Practitioner
- Login
- Daily schedule view
- Queue dashboard
- Call next patient
- Mark consultation completed

### Admin
- Manage practitioners
- Manage departments
- Configure schedules (booking window, cutoff, duration, capacity)
- Manage staff accounts
- Configure payment per department (mode + base fee), practitioner fee overrides, Paystack keys, refund failure inbox
- Basic audit log view and export (including payment events)

### Backend
- Authentication and RBAC
- Appointment conflict protection
- Schedule modification blocking
- Queue generation (department-scoped, daily reset)
- Paystack integration (Paystack-only): initialize, verify, webhook (idempotent on reference), reconcile cron, auto-refund on cancel with retry
- Fee resolution (department base → practitioner override, server-trusted amounts in NGN/kobo)
- WebSockets (Laravel Reverb + Echo)
- Public display board
- Rate limiting (including pay init/verify + webhooks)
- Audit logging (including payment transitions)
- Email notifications (core events including payment events)

### Post-MVP (V2+)
- Reports and exports
- Self check-in (QR code / kiosk)
- WhatsApp notifications
- Advanced notification events (security alerts, system health, summaries)
- Super Admin and multi-hospital management UI
- i18n (Yoruba, Igbo, Hausa)
- Session-based scheduling (alternative to slot-based)

---

## 27. Technology Stack

**Frontend**
React + TypeScript + Inertia.js

**Backend**
Laravel (with Inertia server-side adapter)

**Database**
PostgreSQL

**Authentication**
Laravel Sanctum

**Real-time**
Laravel Reverb (WebSocket server) + Laravel Echo (client)

**Payments**
Paystack-only (initialize, verify, webhook `charge.success`, refund API, reconcile cron). NGN/kobo. Keys per hospital, server-only.

**Deployment**
- Fly.io or Railway — Laravel + PostgreSQL on the same instance (avoids latency between API and DB, no cold starts)
- Vercel — React/Inertia frontend

Move to a persistent VPS when the system moves beyond demonstration/testing.

---

## 28. The Product in One Sentence

> **A patient-facing appointment and queue management platform that helps hospitals manage the journey from appointment booking to consultation while giving patients and waiting room screens real-time visibility into the queue.**
