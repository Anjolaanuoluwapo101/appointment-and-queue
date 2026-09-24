import { Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AsyncButton from '../../../Components/AsyncButton';
import { Badge } from '../../../Components/Badge';
import { Card, CardContent, CardHeader, CardTitle } from '../../../Components/Card';
import AppLayout from '../../../Layouts/AppLayout';
import echo from '../../../echo';

export default function Show() {
    const { appointment, fee_kobo, errors, queue: initialQueue } = usePage().props;
    const [queue, setQueue] = useState(initialQueue ?? null);
    const cancel = useForm({ reason: '' });
    const pay = useForm({});
    const confirm = useForm({});

    const submitCancel = (e) => {
        e.preventDefault();
        cancel.post(`/patient/appointments/${appointment.id}/cancel`);
    };

    const canPayOnline =
        appointment.payment_mode === 'online' && ['unpaid', 'pending', 'failed'].includes(appointment.payment_status);
    const cancellable = ['scheduled', 'pending_clearance'].includes(appointment.status);
    const reschedulable = appointment.status === 'scheduled';
    const confirmable = appointment.status === 'scheduled' && !appointment.attendance_confirmed_at;

    useEffect(() => {
        setQueue(initialQueue ?? null);
    }, [initialQueue]);

    useEffect(() => {
        if (!initialQueue) return undefined;
        const channel = echo.private(`patient.${initialQueue.patient_id}`).listen('PatientQueueUpdated', (e) => setQueue(e));
        return () => {
            echo.leaveChannel(`patient.${initialQueue.patient_id}`);
        };
    }, [initialQueue]);

    return (
        <AppLayout>
            <div className="space-y-6 max-w-3xl mx-auto bg-white py-6">
                {/* Header Strip */}
                <div className="flex items-center justify-between border-b border-zinc-100 pb-4">
                    <div>
                        <span className="text-xs font-mono text-zinc-400 block uppercase">Appointment #{appointment.id}</span>
                        <h1 className="text-2xl font-bold tracking-tight text-zinc-900">{appointment.department.name}</h1>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Badge variant="outline" className="uppercase font-mono">{appointment.status}</Badge>
                        <Badge variant={appointment.payment_status === 'paid' ? 'success' : 'amber'}>
                            {appointment.payment_status}
                        </Badge>
                    </div>
                </div>

                {errors.payment && (
                    <div className="p-3 bg-rose-50 border border-rose-200 rounded text-rose-800 text-xs font-medium">
                        {errors.payment}
                    </div>
                )}

                {/* Queue Live Status Ticket Card */}
                {queue ? (
                    <Card className="bg-white border-2 border-zinc-900 p-6">
                        <div className="text-center space-y-2">
                            <span className="text-xs font-mono font-semibold uppercase text-zinc-500 tracking-wider">Live Queue Ticket</span>
                            <div className="text-6xl font-black font-mono text-zinc-900">{queue.queue_number}</div>
                            <div className="text-xs text-zinc-600 space-y-1">
                                <p>Currently Serving: <strong className="font-mono text-zinc-900">{queue.serving ?? '—'}</strong></p>
                                <p className="font-medium text-zinc-900">
                                    {queue.patients_ahead > 0
                                        ? `Patients Ahead in Queue: ${queue.patients_ahead}`
                                        : queue.status === 'called' || queue.status === 'in_consultation'
                                          ? `Please proceed${queue.room ? ` to ${queue.room}` : ' to consultation room'}.`
                                          : "You are next in line!"}
                                </p>
                            </div>
                        </div>
                    </Card>
                ) : (
                    <Card className="bg-white border border-zinc-200">
                        <CardContent className="text-center py-4">
                            <p className="text-xs text-zinc-500">Live queue ticket activates automatically on hospital check-in.</p>
                        </CardContent>
                    </Card>
                )}

                {/* Appointment Detail Summary */}
                <Card className="bg-white border border-zinc-200">
                    <CardHeader>
                        <CardTitle className="text-sm">Consultation Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 text-xs text-zinc-700">
                        <div className="flex justify-between border-b border-zinc-100 pb-2">
                            <span className="text-zinc-500">Practitioner</span>
                            <span className="font-semibold text-zinc-900">{appointment.practitioner?.full_name ?? 'Any Practitioner'}</span>
                        </div>
                        <div className="flex justify-between border-b border-zinc-100 pb-2">
                            <span className="text-zinc-500">Scheduled Time</span>
                            <span className="font-mono font-medium text-zinc-900">{appointment.scheduled_at.slice(0, 16).replace('T', ' ')}</span>
                        </div>
                        <div className="flex justify-between border-b border-zinc-100 pb-2">
                            <span className="text-zinc-500">Consultation Fee</span>
                            <span className="font-semibold text-zinc-900">₦{(fee_kobo / 100).toLocaleString()}</span>
                        </div>
                        {appointment.payment_status === 'paid' && appointment.payment && (
                            <div className="flex justify-between border-b border-zinc-100 pb-2">
                                <span className="text-zinc-500">Payment Reference</span>
                                <span className="font-mono text-zinc-700">{appointment.payment.reference}</span>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Actions & Payment Trigger */}
                <div className="space-y-4">
                    {canPayOnline && (
                        <div className="bg-zinc-50 p-4 border border-zinc-200 rounded-lg flex items-center justify-between">
                            <div>
                                <span className="text-xs font-bold text-zinc-900 block">Online Payment Required</span>
                                <span className="text-xs text-zinc-500">Pay securely online via Paystack</span>
                            </div>
                            <AsyncButton
                                type="button"
                                loading={pay.processing}
                                loadingText="Redirecting..."
                                onClick={() => pay.post(`/patient/appointments/${appointment.id}/pay`)}
                                variant="primary"
                                className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-4 py-2 rounded text-xs shadow-sm"
                            >
                                Pay Online Now
                            </AsyncButton>
                        </div>
                    )}

                    {confirmable ? (
                        <div className="bg-emerald-50 p-4 border border-emerald-200 rounded-lg flex items-center justify-between">
                            <div>
                                <span className="text-xs font-bold text-zinc-900 block">Will you attend?</span>
                                <span className="text-xs text-zinc-500">Confirm so the hospital holds your slot</span>
                            </div>
                            <AsyncButton
                                type="button"
                                loading={confirm.processing}
                                loadingText="Confirming..."
                                onClick={() => confirm.post(`/patient/appointments/${appointment.id}/confirm`)}
                                variant="primary"
                                className="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-4 py-2 rounded text-xs shadow-sm"
                            >
                                Confirm Attendance
                            </AsyncButton>
                        </div>
                    ) : (
                        appointment.attendance_confirmed_at && (
                            <p className="text-xs font-semibold text-emerald-700">
                                ✓ Attendance confirmed
                            </p>
                        )
                    )}

                    {reschedulable && (
                        <div>
                            <Link
                                href={`/patient/appointments/${appointment.id}/reschedule`}
                                className="text-xs font-semibold text-zinc-900 underline inline-block"
                            >
                                Need to change time? Reschedule Appointment →
                            </Link>
                        </div>
                    )}

                    {cancellable && (
                        <Card className="bg-white border border-zinc-200">
                            <CardHeader>
                                <CardTitle className="text-sm text-rose-700">Cancel Appointment</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={submitCancel} className="space-y-3">
                                    <div>
                                        <label htmlFor="reason" className="block text-xs font-medium text-zinc-700 mb-1">
                                            Cancellation Reason (Optional)
                                        </label>
                                        <input
                                            id="reason"
                                            value={cancel.data.reason}
                                            onChange={(e) => cancel.setData('reason', e.target.value)}
                                            placeholder="Specify reason..."
                                            className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                        />
                                    </div>
                                    <AsyncButton
                                        type="submit"
                                        loading={cancel.processing}
                                        loadingText="Cancelling..."
                                        variant="destructive"
                                        className="bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold px-4 py-2 rounded shadow-sm"
                                    >
                                        Confirm Cancellation
                                    </AsyncButton>
                                </form>
                            </CardContent>
                        </Card>
                    )}

                    <div className="pt-2">
                        <Link href="/patient/appointments" className="text-xs font-medium text-zinc-600 hover:text-zinc-900">
                            ← Back to all appointments
                        </Link>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
