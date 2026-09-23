import { Link, usePage } from '@inertiajs/react';
import { Badge } from '../../Components/Badge';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/Card';
import AppLayout from '../../Layouts/AppLayout';

export default function Dashboard() {
    const { auth, today, appointments, queue } = usePage().props;

    const user = auth?.user;

    const metricCard = (title, count, badgeVariant = 'default') => (
        <Card className="bg-white border border-zinc-200">
            <CardHeader className="pb-1 mb-1 border-b-0">
                <CardTitle className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">{title}</CardTitle>
                <Badge variant={badgeVariant} className="font-mono">{count ?? 0}</Badge>
            </CardHeader>
            <CardContent className="space-y-0">
                <span className="text-2xl font-bold font-mono text-zinc-900">{count ?? 0}</span>
            </CardContent>
        </Card>
    );

    return (
        <AppLayout>
            <div className="space-y-8 max-w-7xl mx-auto bg-white">
                {/* Dashboard Header Strip */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-zinc-100">
                    <div>
                        <div className="flex items-center space-x-2">
                            <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Staff Overview Dashboard</h1>
                            <Badge variant="outline" className="font-mono text-xs">{today}</Badge>
                        </div>
                        <p className="text-xs text-zinc-500 mt-1">
                            Welcome back, <strong className="text-zinc-900">{user?.name}</strong> · Role: <span className="uppercase font-mono text-zinc-700">{user?.role}</span>
                        </p>
                    </div>

                    {/* Quick Access Actions */}
                    <div className="flex flex-wrap gap-2 text-xs">
                        <Link href="/staff/queue" className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-3 py-1.5 rounded transition-colors shadow-sm">
                            Queue Desk
                        </Link>
                        <Link href="/staff/walk-in" className="bg-white hover:bg-zinc-50 text-zinc-700 font-medium px-3 py-1.5 rounded border border-zinc-200 transition-colors shadow-sm">
                            + Walk-In Ticket
                        </Link>
                        <Link href="/staff/search" className="bg-white hover:bg-zinc-50 text-zinc-700 font-medium px-3 py-1.5 rounded border border-zinc-200 transition-colors shadow-sm">
                            Search
                        </Link>
                        {(user?.role === 'practitioner' || user?.role === 'admin') && (
                            <Link href="/staff/my-queue" className="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 py-1.5 rounded transition-colors shadow-sm">
                                My Queue Desk
                            </Link>
                        )}
                        {user?.role === 'admin' && (
                            <Link href="/admin/dashboard" className="bg-zinc-100 hover:bg-zinc-200 text-zinc-900 font-semibold px-3 py-1.5 rounded border border-zinc-300 transition-colors">
                                Admin Console
                            </Link>
                        )}
                    </div>
                </div>

                {/* Today's Appointments Section */}
                <div className="space-y-4">
                    <h2 className="text-sm font-bold tracking-tight text-zinc-900 uppercase">Today&apos;s Appointments Summary</h2>
                    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                        {metricCard('Scheduled', appointments?.scheduled, 'outline')}
                        {metricCard('Pending Clearance', appointments?.pending_clearance, 'amber')}
                        {metricCard('In Queue', appointments?.in_queue, 'default')}
                        {metricCard('Completed', appointments?.completed, 'success')}
                        {metricCard('No-Show', appointments?.no_show, 'destructive')}
                        {metricCard('Cancelled', appointments?.cancelled, 'destructive')}
                    </div>
                </div>

                {/* Today's Queue Section */}
                <div className="space-y-4">
                    <h2 className="text-sm font-bold tracking-tight text-zinc-900 uppercase">Today&apos;s Real-Time Queue Summary</h2>
                    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                        {metricCard('Waiting', queue?.waiting, 'amber')}
                        {metricCard('Called', queue?.called, 'default')}
                        {metricCard('In Room', queue?.in_consultation, 'success')}
                        {metricCard('Skipped', queue?.skipped, 'outline')}
                        {metricCard('Completed', queue?.completed, 'success')}
                        {metricCard('Cancelled', queue?.cancelled, 'destructive')}
                    </div>
                </div>

                {/* Quick Navigation Quick Bar */}
                <Card className="bg-white border border-zinc-200">
                    <CardHeader>
                        <CardTitle className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Quick System Shortcuts</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-wrap gap-3 text-xs font-medium text-zinc-700">
                            <Link href="/staff/patients" className="hover:text-zinc-900 underline">Patient Directory</Link>
                            <span>•</span>
                            <Link href="/staff/appointments" className="hover:text-zinc-900 underline">Appointment History</Link>
                            <span>•</span>
                            <Link href="/notifications" className="hover:text-zinc-900 underline">Delivery Notifications</Link>
                            {user?.role === 'admin' && (
                                <>
                                    <span>•</span>
                                    <Link href="/admin/departments" className="hover:text-zinc-900 underline">Departments</Link>
                                    <span>•</span>
                                    <Link href="/admin/practitioners" className="hover:text-zinc-900 underline">Practitioners</Link>
                                    <span>•</span>
                                    <Link href="/admin/schedules" className="hover:text-zinc-900 underline">Schedules</Link>
                                    <span>•</span>
                                    <Link href="/admin/bulk-cancellation" className="hover:text-zinc-900 underline">Bulk Cancellation</Link>
                                    <span>•</span>
                                    <Link href="/admin/settings" className="hover:text-zinc-900 underline">Settings</Link>
                                </>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
