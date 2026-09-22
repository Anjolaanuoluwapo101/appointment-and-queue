import { Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import echo from '../../../echo';

export default function Show() {
    const { appointment, fee_kobo, status, errors, queue: initialQueue } = usePage().props;
    const [queue, setQueue] = useState(initialQueue ?? null);
    const cancel = useForm({ reason: '' });
    const pay = useForm({});

    const submitCancel = (e) => {
        e.preventDefault();
        cancel.post(`/patient/appointments/${appointment.id}/cancel`);
    };

    const canPayOnline =
        appointment.payment_mode === 'online' && ['unpaid', 'pending', 'failed'].includes(appointment.payment_status);
    const cancellable = ['scheduled', 'pending_clearance'].includes(appointment.status);
    const reschedulable = appointment.status === 'scheduled';

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
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem', maxWidth: '32rem' }}>
            <h1>Appointment</h1>
            {status && <p>{status}</p>}
            {errors.payment && <p>{errors.payment}</p>}
            <dl>
                <dt>Department</dt>
                <dd>{appointment.department.name}</dd>
                <dt>Practitioner</dt>
                <dd>{appointment.practitioner?.full_name ?? 'Any practitioner'}</dd>
                <dt>When</dt>
                <dd>{appointment.scheduled_at.slice(0, 16).replace('T', ' ')}</dd>
                <dt>Status</dt>
                <dd>{appointment.status}</dd>
                <dt>Payment</dt>
                <dd>
                    {appointment.payment_status} · ₦{(fee_kobo / 100).toLocaleString()}
                </dd>
                {appointment.payment_status === 'paid' && (
                    <>
                        <dt>Receipt</dt>
                        <dd>Ref: {appointment.payment?.reference}</dd>
                    </>
                )}
            </dl>

            {queue ? (
                <>
                    <h2>Queue Number: {queue.queue_number}</h2>
                    <p>Currently Serving: {queue.serving ?? '—'}</p>
                    <p>
                        {queue.patients_ahead > 0
                            ? `Patients Ahead: ${queue.patients_ahead}`
                            : queue.status === 'called' || queue.status === 'in_consultation'
                              ? `Please proceed${queue.room ? ` to ${queue.room}` : ''}.`
                              : "You're next"}
                    </p>
                    <p>Status: {queue.status}</p>
                </>
            ) : (
                <p>Queue view activates after check-in.</p>
            )}

            {canPayOnline && (
                <button type="button" disabled={pay.processing} onClick={() => pay.post(`/patient/appointments/${appointment.id}/pay`)}>
                    Pay Online Now
                </button>
            )}
            {appointment.payment_mode === 'physical' && appointment.payment_status === 'unpaid' && (
                <p>Pay cash/transfer/POS at the hospital desk on arrival.</p>
            )}

            {reschedulable && (
                <p>
                    <Link href={`/patient/appointments/${appointment.id}/reschedule`}>Reschedule</Link>
                </p>
            )}
            {cancellable && (
                <form onSubmit={submitCancel}>
                    <label htmlFor="reason">Cancel reason (optional)</label>
                    <input id="reason" value={cancel.data.reason} onChange={(e) => cancel.setData('reason', e.target.value)} />
                    <button type="submit" disabled={cancel.processing}>Cancel appointment</button>
                </form>
            )}
            <p>
                <Link href="/patient/appointments">Back to appointments</Link>
            </p>
        </main>
    );
}
