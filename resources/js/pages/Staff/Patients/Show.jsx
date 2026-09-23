import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';
import Card, { CardHeader, CardTitle, CardContent } from '../../../Components/Card';
import { Badge } from '../../../Components/Badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../Components/Table';

export default function Show() {
    const { patient, appointments, queue_entries } = usePage().props;

    return (
        <AppLayout activeRoute="staff.patients">
            <div className="space-y-6 max-w-6xl mx-auto bg-white py-6">
                {/* Header Banner */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 pb-4">
                    <div>
                        <div className="flex items-center space-x-3">
                            <h1 className="text-2xl font-bold tracking-tight text-zinc-900">{patient.full_name}</h1>
                            <Badge variant="outline" className="font-mono text-xs px-2.5 py-0.5">
                                {patient.patient_number || `PAT-#${patient.id}`}
                            </Badge>
                        </div>
                        <p className="text-xs text-zinc-500 mt-1">Medical Record File & Electronic Patient Folder</p>
                    </div>

                    <div className="flex items-center space-x-2">
                        <Link
                            href={`/staff/appointments/create?phone=${encodeURIComponent(patient.phone)}`}
                            className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-3 py-2 rounded text-xs transition-colors shadow-sm"
                        >
                            + Book Appointment
                        </Link>
                        <Link
                            href="/staff/walk-in"
                            className="bg-white border border-zinc-300 hover:bg-zinc-50 text-zinc-800 font-semibold px-3 py-2 rounded text-xs transition-colors shadow-sm"
                        >
                            + Issue Walk-In Ticket
                        </Link>
                    </div>
                </div>

                {/* Patient Profile & Demographics Grid */}
                <Card className="bg-white border border-zinc-200 shadow-sm">
                    <CardHeader>
                        <CardTitle className="text-sm font-bold text-zinc-900">Demographic & Contact Information</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
                            <div>
                                <span className="text-zinc-500 block font-semibold mb-0.5">Phone Number</span>
                                <span className="font-mono font-medium text-zinc-900">{patient.phone}</span>
                            </div>

                            <div>
                                <span className="text-zinc-500 block font-semibold mb-0.5">Email Address</span>
                                <span className="text-zinc-900 font-medium">{patient.email || '—'}</span>
                            </div>

                            <div>
                                <span className="text-zinc-500 block font-semibold mb-0.5">Gender</span>
                                <span className="text-zinc-900 font-medium capitalize">{patient.gender || '—'}</span>
                            </div>

                            <div>
                                <span className="text-zinc-500 block font-semibold mb-0.5">Date of Birth</span>
                                <span className="text-zinc-900 font-medium">
                                    {patient.date_of_birth ? new Date(patient.date_of_birth).toLocaleDateString() : '—'}
                                </span>
                            </div>

                            <div>
                                <span className="text-zinc-500 block font-semibold mb-0.5">Residential Address</span>
                                <span className="text-zinc-900 font-medium">{patient.address || '—'}</span>
                            </div>

                            <div>
                                <span className="text-zinc-500 block font-semibold mb-0.5">Secondary Emergency Contact</span>
                                <span className="text-zinc-900 font-medium">
                                    {patient.secondary_contact_name
                                        ? `${patient.secondary_contact_name} (${patient.secondary_contact_phone || 'No Phone'})`
                                        : '—'}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Appointment History Section */}
                <div className="space-y-3 pt-2">
                    <h2 className="text-sm font-bold tracking-tight text-zinc-900">Appointment History ({appointments.length})</h2>
                    {appointments.length === 0 ? (
                        <div className="p-4 rounded-md border border-zinc-200 bg-white text-xs text-zinc-500 italic">
                            No appointment records found for this patient.
                        </div>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>ID</TableHead>
                                    <TableHead>Scheduled Date</TableHead>
                                    <TableHead>Department</TableHead>
                                    <TableHead>Practitioner</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Payment</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {appointments.map((a) => (
                                    <TableRow key={a.id}>
                                        <TableCell className="font-mono text-xs font-semibold text-zinc-900">#{a.id}</TableCell>
                                        <TableCell className="font-medium text-zinc-900">
                                            {new Date(a.scheduled_at).toLocaleString()}
                                        </TableCell>
                                        <TableCell className="text-zinc-900 font-medium">{a.department?.name || '—'}</TableCell>
                                        <TableCell className="text-zinc-700">{a.practitioner?.full_name || '—'}</TableCell>
                                        <TableCell>
                                            <Badge variant="outline" className="uppercase text-[10px]">
                                                {a.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={a.payment_status === 'paid' ? 'success' : 'secondary'} className="uppercase text-[10px]">
                                                {a.payment_status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Link href={`/staff/appointments/${a.id}`} className="text-xs font-semibold text-zinc-900 underline">
                                                View Appointment →
                                            </Link>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </div>

                {/* Queue History Section */}
                <div className="space-y-3 pt-4 border-t border-zinc-100">
                    <h2 className="text-sm font-bold tracking-tight text-zinc-900">Live Queue Tokens ({queue_entries.length})</h2>
                    {queue_entries.length === 0 ? (
                        <div className="p-4 rounded-md border border-zinc-200 bg-white text-xs text-zinc-500 italic">
                            No queue entries recorded.
                        </div>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Queue Token</TableHead>
                                    <TableHead>Department</TableHead>
                                    <TableHead>Practitioner</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Created At</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {queue_entries.map((e) => (
                                    <TableRow key={e.id}>
                                        <TableCell className="font-mono font-bold text-xs text-zinc-900">{e.queue_number}</TableCell>
                                        <TableCell className="text-zinc-900 font-medium">{e.department?.name || '—'}</TableCell>
                                        <TableCell className="text-zinc-700">{e.practitioner?.full_name || '—'}</TableCell>
                                        <TableCell>
                                            <Badge variant="outline" className="uppercase text-[10px]">
                                                {e.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-zinc-500 text-xs">
                                            {new Date(e.created_at).toLocaleString()}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
