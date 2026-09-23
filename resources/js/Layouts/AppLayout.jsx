import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function AppLayout({ children }) {
    const { auth, status, flash, url } = usePage().props;
    const currentUrl = usePage().url;
    const { t, i18n } = useTranslation();
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const user = auth?.user;
    const role = user?.role ?? 'guest';

    const changeLanguage = (lang) => {
        i18n.changeLanguage(lang);
    };

    const flashMessage = status ?? flash?.status ?? flash?.success ?? flash?.message;
    const flashError = flash?.error;

    const isActive = (path) => {
        if (!currentUrl) return false;
        if (path === '/staff/queue') return currentUrl === '/staff/queue';
        return currentUrl.startsWith(path);
    };

    const navLinkClass = (path) => {
        return isActive(path)
            ? 'px-3 py-1.5 rounded bg-zinc-900 text-white font-semibold shadow-sm transition-colors'
            : 'px-3 py-1.5 rounded text-zinc-700 hover:text-zinc-900 hover:bg-zinc-100 transition-colors font-medium';
    };

    const mobileNavLinkClass = (path) => {
        return isActive(path)
            ? 'block px-3 py-2 rounded bg-zinc-900 text-white font-semibold'
            : 'block px-3 py-2 rounded text-zinc-700 hover:bg-zinc-200 font-medium';
    };

    return (
        <div className="min-h-screen bg-white text-zinc-900 antialiased flex flex-col font-sans">
            {/* Top Responsive App Navbar */}
            <header className="h-14 border-b border-zinc-200 bg-white sticky top-0 z-50 px-4 sm:px-6 flex items-center justify-between">
                <div className="flex items-center space-x-3 sm:space-x-4">
                    {/* Mobile Menu Button */}
                    <button
                        type="button"
                        onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                        className="md:hidden p-1.5 rounded-md text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100 transition-colors"
                        aria-label="Toggle Navigation"
                    >
                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    {/* Brand Identity */}
                    <Link href="/" className="flex items-center space-x-2 text-xs font-semibold text-zinc-900">
                        <span className="h-6 w-6 rounded bg-zinc-900 text-white flex items-center justify-center font-bold text-xs">
                            C
                        </span>
                        <span className="font-bold tracking-tight text-sm">{t('app.name')}</span>
                    </Link>

                    {/* Desktop Navigation Links */}
                    <nav className="hidden md:flex items-center space-x-1 text-xs pl-4 border-l border-zinc-200">
                        {role === 'patient' && (
                            <>
                                <Link href="/patient/dashboard" className={navLinkClass('/patient/dashboard')}>
                                    Dashboard
                                </Link>
                                <Link href="/patient/book" className={navLinkClass('/patient/book')}>
                                    Book Appointment
                                </Link>
                                <Link href="/patient/appointments" className={navLinkClass('/patient/appointments')}>
                                    My Appointments
                                </Link>
                            </>
                        )}

                        {role === 'practitioner' && (
                            <>
                                <Link href="/staff/my-queue" className={navLinkClass('/staff/my-queue')}>
                                    My Queue Desk
                                </Link>
                                <Link href="/staff/dashboard" className={navLinkClass('/staff/dashboard')}>
                                    Staff Dashboard
                                </Link>
                                <Link href="/staff/search" className={navLinkClass('/staff/search')}>
                                    Patient Search
                                </Link>
                            </>
                        )}

                        {(role === 'receptionist' || role === 'staff') && (
                            <>
                                <Link href="/staff/queue" className={navLinkClass('/staff/queue')}>
                                    Queue Desk
                                </Link>
                                <Link href="/staff/walk-in" className={navLinkClass('/staff/walk-in')}>
                                    + Walk-In Ticket
                                </Link>
                                <Link href="/staff/search" className={navLinkClass('/staff/search')}>
                                    Search
                                </Link>
                                <Link href="/staff/patients" className={navLinkClass('/staff/patients')}>
                                    Patient Directory
                                </Link>
                            </>
                        )}

                        {role === 'admin' && (
                            <>
                                <Link href="/admin/dashboard" className={navLinkClass('/admin/dashboard')}>
                                    Admin Overview
                                </Link>
                                <Link href="/admin/staff" className={navLinkClass('/admin/staff')}>
                                    Staff Accounts
                                </Link>
                                <Link href="/admin/departments" className={navLinkClass('/admin/departments')}>
                                    Departments
                                </Link>
                                <Link href="/admin/practitioners" className={navLinkClass('/admin/practitioners')}>
                                    Practitioners
                                </Link>
                                <Link href="/admin/schedules" className={navLinkClass('/admin/schedules')}>
                                    Schedules
                                </Link>
                                <Link href="/admin/reports" className={navLinkClass('/admin/reports')}>
                                    Reports
                                </Link>
                                <Link href="/admin/audit-log" className={navLinkClass('/admin/audit-log')}>
                                    Audit Log
                                </Link>
                            </>
                        )}
                    </nav>
                </div>

                {/* Right Utilities */}
                <div className="flex items-center space-x-3 text-xs">
                    {/* Language Switcher */}
                    <select
                        onChange={(e) => changeLanguage(e.target.value)}
                        value={i18n.language}
                        className="bg-white border border-zinc-200 rounded px-2 py-1 text-xs text-zinc-700 font-medium focus:outline-none focus:border-zinc-400"
                    >
                        <option value="en">English</option>
                        <option value="yo">Yorùbá</option>
                        <option value="ha">Hausa</option>
                        <option value="ig">Igbo</option>
                        <option value="pcm">Pidgin</option>
                    </select>

                    {/* Role Indicator & Profile */}
                    {user ? (
                        <div className="flex items-center space-x-2 border-l border-zinc-200 pl-3">
                            <span className="hidden sm:inline px-2 py-0.5 rounded text-[11px] font-semibold bg-zinc-100 text-zinc-800 border border-zinc-200 capitalize">
                                {role}
                            </span>
                            <span className="font-semibold text-zinc-900 hidden md:inline text-xs">{user.name}</span>
                            <Link
                                href="/logout"
                                method="post"
                                as="button"
                                className="bg-rose-50 hover:bg-rose-100 text-rose-700 hover:text-rose-800 border border-rose-200 px-3 py-1 rounded text-xs font-bold transition-colors shadow-sm"
                            >
                                Logout
                            </Link>
                        </div>
                    ) : (
                        <div className="flex items-center space-x-2">
                            <Link href="/login/patient" className="text-zinc-700 hover:text-zinc-900 text-xs font-medium px-2 py-1">
                                {t('nav.patientLogin')}
                            </Link>
                            <Link href="/login/staff" className="bg-zinc-900 text-white hover:bg-zinc-800 text-xs font-semibold px-3 py-1.5 rounded transition-colors">
                                {t('nav.staffLogin')}
                            </Link>
                        </div>
                    )}
                </div>
            </header>

            {/* Mobile Navigation Drawer */}
            {mobileMenuOpen && (
                <div className="md:hidden border-b border-zinc-200 bg-zinc-50 px-4 py-3 space-y-2 text-xs font-medium border-t">
                    {role === 'patient' && (
                        <>
                            <Link href="/patient/dashboard" className={mobileNavLinkClass('/patient/dashboard')}>Dashboard</Link>
                            <Link href="/patient/book" className={mobileNavLinkClass('/patient/book')}>Book Appointment</Link>
                            <Link href="/patient/appointments" className={mobileNavLinkClass('/patient/appointments')}>My Appointments</Link>
                        </>
                    )}
                    {role === 'practitioner' && (
                        <>
                            <Link href="/staff/my-queue" className={mobileNavLinkClass('/staff/my-queue')}>My Queue Desk</Link>
                            <Link href="/staff/dashboard" className={mobileNavLinkClass('/staff/dashboard')}>Staff Dashboard</Link>
                            <Link href="/staff/search" className={mobileNavLinkClass('/staff/search')}>Patient Search</Link>
                        </>
                    )}
                    {(role === 'receptionist' || role === 'staff') && (
                        <>
                            <Link href="/staff/queue" className={mobileNavLinkClass('/staff/queue')}>Queue Desk</Link>
                            <Link href="/staff/walk-in" className={mobileNavLinkClass('/staff/walk-in')}>+ Walk-In Ticket</Link>
                            <Link href="/staff/search" className={mobileNavLinkClass('/staff/search')}>Search</Link>
                            <Link href="/staff/patients" className={mobileNavLinkClass('/staff/patients')}>Patient Directory</Link>
                        </>
                    )}
                    {role === 'admin' && (
                        <>
                            <Link href="/admin/dashboard" className={mobileNavLinkClass('/admin/dashboard')}>Admin Overview</Link>
                            <Link href="/admin/staff" className={mobileNavLinkClass('/admin/staff')}>Staff Accounts</Link>
                            <Link href="/admin/departments" className={mobileNavLinkClass('/admin/departments')}>Departments</Link>
                            <Link href="/admin/practitioners" className={mobileNavLinkClass('/admin/practitioners')}>Practitioners</Link>
                            <Link href="/admin/schedules" className={mobileNavLinkClass('/admin/schedules')}>Schedules</Link>
                            <Link href="/admin/reports" className={mobileNavLinkClass('/admin/reports')}>Reports</Link>
                            <Link href="/admin/audit-log" className={mobileNavLinkClass('/admin/audit-log')}>Audit Log</Link>
                        </>
                    )}
                </div>
            )}

            {/* Flash Notice Banner */}
            {flashMessage && (
                <div className="bg-emerald-50 border-b border-emerald-200 text-emerald-800 px-6 py-2.5 text-xs font-medium text-center flex items-center justify-center space-x-2">
                    <span className="font-bold">✓</span>
                    <span>{flashMessage}</span>
                </div>
            )}
            {flashError && (
                <div className="bg-rose-50 border-b border-rose-200 text-rose-800 px-6 py-2.5 text-xs font-medium text-center flex items-center justify-center space-x-2">
                    <span className="font-bold">✕</span>
                    <span>{flashError}</span>
                </div>
            )}

            {/* Main Workspace Body */}
            <main className="max-w-7xl mx-auto px-4 sm:px-6 py-6 flex-1 w-full bg-white">
                {children}
            </main>

            {/* Minimal Pure White Footer */}
            <footer className="border-t border-zinc-200 py-4 px-6 text-center text-xs text-zinc-400 bg-white">
                <span>{t('app.name')} · Healthcare Queue & Appointment System</span>
            </footer>
        </div>
    );
}
