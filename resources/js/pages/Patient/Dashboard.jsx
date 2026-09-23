import { Link, usePage } from '@inertiajs/react';
import { Badge } from '../../Components/Badge';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/Card';
import AppLayout from '../../Layouts/AppLayout';

export default function Dashboard() {
    const { auth, upcoming, unread } = usePage().props;

    return (
        <AppLayout>
            <div className="space-y-8 max-w-5xl mx-auto bg-white">
                {/* Header Banner */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-zinc-100">
                    <div>
                        <div className="flex items-center space-x-2">
                            <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Patient Dashboard</h1>
                            {auth?.user?.patient_number && (
                                <Badge variant="outline" className="font-mono text-xs">
                                    Hospital No: {auth.user.patient_number}
                                </Badge>
                            )}
                        </div>
                        <p className="text-xs text-zinc-500 mt-1">Welcome back, <strong className="text-zinc-900">{auth?.user?.name}</strong></p>
                    </div>

                    <div className="flex items-center space-x-2">
                        <Link
                            href="/patient/book/1/practitioners"
                            className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-4 py-2 rounded text-xs transition-colors shadow-sm"
                        >
                            + Book New Appointment
                        </Link>
                    </div>
                </div>

                {/* Upcoming Appointments Section */}
                <div className="space-y-4">
                    <h2 className="text-sm font-bold tracking-tight text-zinc-900 uppercase">Upcoming Appointments ({upcoming.length})</h2>

                    {upcoming.length === 0 ? (
                        <Card className="bg-white border border-zinc-200 text-center py-8">
                            <p className="text-xs text-zinc-500">You currently have no upcoming appointments scheduled.</p>
                            <Link href="/patient/book/1/practitioners" className="mt-3 inline-block font-semibold text-xs text-zinc-900 underline">
                                Book an appointment now →
                            </Link>
                        </Card>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {upcoming.map((a) => (
                                <Card key={a.id} className="bg-white border border-zinc-200 hover:border-zinc-400 transition-all">
                                    <CardHeader className="pb-2 border-b-0">
                                        <CardTitle className="text-sm">{a.department.name}</CardTitle>
                                        <Badge variant="outline" className="uppercase font-mono text-[10px]">
                                            {a.status}
                                        </Badge>
                                    </CardHeader>
                                    <CardContent className="space-y-2">
                                        <p className="text-xs text-zinc-600">Practitioner: <strong>{a.practitioner?.full_name ?? 'Any Practitioner'}</strong></p>
                                        <p className="text-xs text-zinc-500 font-mono">Scheduled: {a.scheduled_at.slice(0, 16).replace('T', ' ')}</p>
                                        <div className="pt-2">
                                            <Link href={`/patient/appointments/${a.id}`} className="text-xs font-semibold text-zinc-900 underline">
                                                View Appointment Details & Queue Ticket →
                                            </Link>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    )}
                </div>

                {/* Latest Notices Section */}
                {unread.length > 0 && (
                    <div className="space-y-4">
                        <h2 className="text-sm font-bold tracking-tight text-zinc-900 uppercase">Latest Hospital Notifications ({unread.length})</h2>
                        <div className="space-y-2">
                            {unread.map((n) => (
                                <div key={n.id} className="p-4 bg-zinc-50 border border-zinc-200 rounded-lg text-xs space-y-1">
                                    <span className="font-bold text-zinc-900 block">{n.title}</span>
                                    <p className="text-zinc-600">{n.body}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
