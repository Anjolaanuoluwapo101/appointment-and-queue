import { Link, usePage } from '@inertiajs/react';
import { Badge } from '../../Components/Badge';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/Card';
import AppLayout from '../../Layouts/AppLayout';

export default function Dashboard() {
    const { today, appointments_today, queue_active_today, no_show_rate_30d, cancel_rate_30d, payments, refund_failures } = usePage().props;

    return (
        <AppLayout>
            <div className="space-y-8 max-w-7xl mx-auto bg-white py-6">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-zinc-100">
                    <div>
                        <div className="flex items-center space-x-2">
                            <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Admin KPI Command Center</h1>
                            <Badge variant="outline" className="font-mono text-xs">{today}</Badge>
                        </div>
                        <p className="text-xs text-zinc-500 mt-1">Hospital system analytics, revenue counters, and operational management</p>
                    </div>

                    <div className="flex flex-wrap gap-2 text-xs font-semibold">
                        <Link href="/admin/staff" className="bg-zinc-900 text-white px-3 py-1.5 rounded shadow-sm hover:bg-zinc-800">
                            Staff Accounts
                        </Link>
                        <Link href="/admin/departments" className="bg-white border border-zinc-200 text-zinc-800 px-3 py-1.5 rounded hover:bg-zinc-50">
                            Departments
                        </Link>
                        <Link href="/admin/practitioners" className="bg-white border border-zinc-200 text-zinc-800 px-3 py-1.5 rounded hover:bg-zinc-50">
                            Practitioners
                        </Link>
                        <Link href="/admin/schedules" className="bg-white border border-zinc-200 text-zinc-800 px-3 py-1.5 rounded hover:bg-zinc-50">
                            Schedules
                        </Link>
                        <Link href="/admin/reports" className="bg-white border border-zinc-200 text-zinc-800 px-3 py-1.5 rounded hover:bg-zinc-50">
                            Reports
                        </Link>
                    </div>
                </div>

                {/* KPI Metrics Grid */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="bg-white border border-zinc-200">
                        <CardHeader className="pb-1 mb-1 border-b-0">
                            <CardTitle className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Today&apos;s Appointments</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <span className="text-3xl font-extrabold font-mono text-zinc-900">{appointments_today ?? 0}</span>
                        </CardContent>
                    </Card>

                    <Card className="bg-white border border-zinc-200">
                        <CardHeader className="pb-1 mb-1 border-b-0">
                            <CardTitle className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Active Queue Today</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <span className="text-3xl font-extrabold font-mono text-zinc-900">{queue_active_today ?? 0}</span>
                        </CardContent>
                    </Card>

                    <Card className="bg-white border border-zinc-200">
                        <CardHeader className="pb-1 mb-1 border-b-0">
                            <CardTitle className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">30-Day No-Show Rate</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <span className="text-3xl font-extrabold font-mono text-zinc-900">{no_show_rate_30d}%</span>
                        </CardContent>
                    </Card>

                    <Card className="bg-white border border-zinc-200">
                        <CardHeader className="pb-1 mb-1 border-b-0">
                            <CardTitle className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">30-Day Cancellation Rate</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <span className="text-3xl font-extrabold font-mono text-zinc-900">{cancel_rate_30d}%</span>
                        </CardContent>
                    </Card>
                </div>

                {/* Revenue Summary Section */}
                <div className="space-y-4">
                    <h2 className="text-sm font-bold text-zinc-900 uppercase tracking-tight">Payment & Revenue Summaries</h2>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        {(payments ?? []).map((p) => (
                            <Card key={p.status} className="bg-white border border-zinc-200">
                                <CardHeader className="pb-1 mb-1 border-b-0">
                                    <CardTitle className="text-xs font-semibold text-zinc-500 uppercase tracking-wider">{p.status} Payments</CardTitle>
                                    <Badge variant="outline" className="font-mono">{p.total}</Badge>
                                </CardHeader>
                                <CardContent>
                                    <span className="text-xl font-bold font-mono text-zinc-900">₦{(p.kobo / 100).toLocaleString()}</span>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>

                {/* Refund Failures Log */}
                {refund_failures.length > 0 && (
                    <div className="space-y-4">
                        <h2 className="text-sm font-bold text-rose-700 uppercase tracking-tight">Refund Failures ({refund_failures.length})</h2>
                        <Card className="bg-white border border-rose-200">
                            <CardContent className="divide-y divide-zinc-100 p-0">
                                {refund_failures.map((f) => (
                                    <div key={f.id} className="p-4 text-xs flex justify-between items-center">
                                        <span className="font-mono text-zinc-700">{f.payload?.reference ?? 'Unknown Reference'}</span>
                                        <span className="font-mono text-zinc-400">{f.created_at.slice(0, 16).replace('T', ' ')}</span>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
