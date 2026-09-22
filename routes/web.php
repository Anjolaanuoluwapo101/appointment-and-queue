<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BulkCancellationController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\NotificationLogController;
use App\Http\Controllers\Admin\PractitionerController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ScheduleExceptionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Patient\BookingController;
use App\Http\Controllers\Patient\DashboardController as PatientDashboardController;
use App\Http\Controllers\PatientProfileController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\Staff\AppointmentController as StaffAppointmentController;
use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Staff\PatientController;
use App\Http\Controllers\Staff\QueueController;
use App\Http\Controllers\Staff\SearchController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $user = auth()->user();

    if ($user instanceof User) {
        return redirect($user->role === User::ROLE_PATIENT ? '/patient/dashboard' : '/staff/dashboard');
    }

    return Inertia::render('Home', [
        'status' => 'Inertia + React is wired. Supabase Postgres connected.',
    ]);
});

Route::middleware(['guest', 'throttle:auth'])->group(function (): void {
    Route::get('/login/patient', [LoginController::class, 'showPatient'])->name('login.patient');
    Route::get('/login/staff', [LoginController::class, 'showStaff'])->name('login.staff');
    Route::post('/login/{portal}', [LoginController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

    Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequest'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle'])->middleware('throttle:webhooks')->name('webhooks.paystack');

Route::get('/cron/run', [CronController::class, 'run'])->middleware('throttle:60,1')->name('cron.run');

Route::get('/display/queue/{department}', [DisplayController::class, 'show'])->name('display.board');

Route::middleware(['auth', 'role:patient'])->prefix('patient')->group(function (): void {
    Route::get('/dashboard', [PatientDashboardController::class, 'show'])->name('patient.dashboard');
    Route::get('/profile', [PatientProfileController::class, 'show'])->name('patient.profile');
    Route::patch('/profile', [PatientProfileController::class, 'update'])->name('patient.profile.update');

    Route::get('/book', [BookingController::class, 'departments'])->name('patient.book');
    Route::get('/book/{department}/practitioners', [BookingController::class, 'practitioners'])->name('patient.book.practitioners');
    Route::get('/book/{department}/slots', [BookingController::class, 'slots'])->name('patient.book.slots');
    Route::post('/book', [BookingController::class, 'store'])->middleware('throttle:booking')->name('patient.book.store');

    Route::get('/appointments', [BookingController::class, 'index'])->name('patient.appointments');
    Route::get('/appointments/{appointment}', [BookingController::class, 'show'])->name('patient.appointments.show');
    Route::post('/appointments/{appointment}/pay', [BookingController::class, 'payInitialize'])->middleware('throttle:payments')->name('patient.appointments.pay');
    Route::get('/appointments/{appointment}/pay/verify', [BookingController::class, 'payVerify'])->middleware('throttle:payments')->name('patient.appointments.pay.verify');
    Route::post('/appointments/{appointment}/cancel', [BookingController::class, 'cancel'])->name('patient.appointments.cancel');
    Route::get('/appointments/{appointment}/reschedule', [BookingController::class, 'rescheduleForm'])->name('patient.appointments.reschedule');
    Route::post('/appointments/{appointment}/reschedule', [BookingController::class, 'reschedule'])->name('patient.appointments.reschedule.store');
});

Route::middleware(['auth', 'role:receptionist,practitioner,admin'])->prefix('staff')->group(function (): void {
    Route::get('/dashboard', [StaffDashboardController::class, 'show'])->name('staff.dashboard');

    Route::get('/queue', [QueueController::class, 'dashboard'])->name('staff.queue');
    Route::post('/queue/call-next', [QueueController::class, 'callNext'])->name('staff.queue.call-next');
    Route::post('/queue/{entry}/call', [QueueController::class, 'call'])->name('staff.queue.call');
    Route::post('/queue/{entry}/begin', [QueueController::class, 'begin'])->name('staff.queue.begin');
    Route::post('/queue/{entry}/skip', [QueueController::class, 'skip'])->name('staff.queue.skip');
    Route::post('/queue/{entry}/recall', [QueueController::class, 'recall'])->name('staff.queue.recall');
    Route::post('/queue/{entry}/complete', [QueueController::class, 'complete'])->name('staff.queue.complete');
    Route::post('/queue/{entry}/cancel', [QueueController::class, 'cancelEntry'])->name('staff.queue.cancel');

    Route::get('/my-queue', [QueueController::class, 'practitionerBoard'])->name('staff.my-queue');
});

Route::middleware(['auth', 'role:receptionist,admin'])->prefix('staff')->group(function (): void {
    Route::get('/patients', [PatientController::class, 'index'])->name('staff.patients');
    Route::get('/patients/create', [PatientController::class, 'create'])->name('staff.patients.create');
    Route::post('/patients', [PatientController::class, 'store'])->name('staff.patients.store');

    Route::get('/appointments', [StaffAppointmentController::class, 'index'])->name('staff.appointments');
    Route::get('/appointments/create', [StaffAppointmentController::class, 'create'])->name('staff.appointments.create');
    Route::post('/appointments', [StaffAppointmentController::class, 'store'])->name('staff.appointments.store');
    Route::get('/appointments/{appointment}', [StaffAppointmentController::class, 'show'])->name('staff.appointments.show');
    Route::post('/appointments/{appointment}/reschedule', [StaffAppointmentController::class, 'reschedule'])->name('staff.appointments.reschedule');
    Route::post('/appointments/{appointment}/cancel', [StaffAppointmentController::class, 'cancel'])->name('staff.appointments.cancel');
    Route::post('/appointments/{appointment}/no-show', [StaffAppointmentController::class, 'noShow'])->name('staff.appointments.no-show');

    Route::post('/appointments/{appointment}/check-in', [QueueController::class, 'checkIn'])->name('staff.queue.check-in');
    Route::post('/appointments/{appointment}/clear', [QueueController::class, 'clear'])->name('staff.queue.clear');
    Route::get('/walk-in', [QueueController::class, 'walkInForm'])->name('staff.walk-in');
    Route::post('/walk-in', [QueueController::class, 'walkIn'])->name('staff.walk-in.store');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function (): void {
    Route::get('/departments', [DepartmentController::class, 'index'])->name('admin.departments');
    Route::get('/departments/create', [DepartmentController::class, 'create'])->name('admin.departments.create');
    Route::post('/departments', [DepartmentController::class, 'store'])->name('admin.departments.store');
    Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])->name('admin.departments.edit');
    Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('admin.departments.update');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('admin.departments.destroy');
    Route::get('/settings', [SettingsController::class, 'show'])->name('admin.settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('admin.settings.update');
    Route::get('/dashboard', [AdminDashboardController::class, 'show'])->name('admin.dashboard');
    Route::get('/bulk-cancellation', [BulkCancellationController::class, 'create'])->name('admin.bulk');
    Route::post('/bulk-cancellation', [BulkCancellationController::class, 'store'])->name('admin.bulk.store');
    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('admin.reports.export');
    Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('admin.reports.export-pdf');
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('admin.audit');
    Route::get('/audit-log/export', [AuditLogController::class, 'export'])->name('admin.audit.export');
    Route::get('/audit-log/export-pdf', [AuditLogController::class, 'exportPdf'])->name('admin.audit.export-pdf');
    Route::get('/notification-logs', [NotificationLogController::class, 'index'])->name('admin.notification-logs');
    Route::get('/practitioners', [PractitionerController::class, 'index'])->name('admin.practitioners');
    Route::get('/practitioners/create', [PractitionerController::class, 'create'])->name('admin.practitioners.create');
    Route::post('/practitioners', [PractitionerController::class, 'store'])->name('admin.practitioners.store');
    Route::get('/practitioners/{practitioner}/edit', [PractitionerController::class, 'edit'])->name('admin.practitioners.edit');
    Route::put('/practitioners/{practitioner}', [PractitionerController::class, 'update'])->name('admin.practitioners.update');
    Route::get('/schedules', [ScheduleController::class, 'index'])->name('admin.schedules');
    Route::get('/schedules/create', [ScheduleController::class, 'create'])->name('admin.schedules.create');
    Route::post('/schedules', [ScheduleController::class, 'store'])->name('admin.schedules.store');
    Route::get('/schedules/{schedule}/edit', [ScheduleController::class, 'edit'])->name('admin.schedules.edit');
    Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->name('admin.schedules.update');
    Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('admin.schedules.destroy');
    Route::get('/exceptions', [ScheduleExceptionController::class, 'index'])->name('admin.exceptions');
    Route::get('/exceptions/create', [ScheduleExceptionController::class, 'create'])->name('admin.exceptions.create');
    Route::post('/exceptions', [ScheduleExceptionController::class, 'store'])->name('admin.exceptions.store');
    Route::delete('/exceptions/{exception}', [ScheduleExceptionController::class, 'destroy'])->name('admin.exceptions.destroy');
    Route::get('/staff', [StaffController::class, 'index'])->name('admin.staff');
    Route::get('/staff/create', [StaffController::class, 'create'])->name('admin.staff.create');
    Route::post('/staff', [StaffController::class, 'store'])->name('admin.staff.store');
    Route::get('/staff/{staff}/edit', [StaffController::class, 'edit'])->name('admin.staff.edit');
    Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('admin.staff.update');
    Route::patch('/staff/{staff}/toggle', [StaffController::class, 'toggleActive'])->name('admin.staff.toggle');
});

Route::middleware(['auth', 'role:receptionist,admin'])->group(function (): void {
    Route::get('/staff/search', [SearchController::class, 'search'])->name('staff.search');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
});
