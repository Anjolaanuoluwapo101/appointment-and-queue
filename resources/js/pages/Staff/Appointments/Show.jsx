import { Link, useForm, usePage } from '@inertiajs/react';

export default function Show() {
    const { appointment, status } = usePage().props;
    const cancel = useForm({ reason: '' });
    const action = useForm({ slot_id: '' });
    const clear = useForm({ receipt_no: '', method: 'cash' });

    const checkIn = () => action.post(`/staff/appointments/${appointment.id}/check-in`);

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem', maxWidth: '32rem' }}>
            <h1>Appointment #{appointment.id}</h1>
            {status && <p>{status}</p>}
            <dl>
                <dt>Patient</dt>
                <dd>
                    {appointment.patient.full_name} · {appointment.patient.phone}
                </dd>
                <dt>Department</dt>
                <dd>{appointment.department.name}</dd>
                <dt>Practitioner</dt>
                <dd>{appointment.practitioner?.full_name ?? '—'}</dd>
                <dt>When</dt>
                <dd>{appointment.scheduled_at.slice(0, 16).replace('T', ' ')}</dd>
                <dt>Status</dt>
                <dd>{appointment.status}</dd>
                <dt>Payment</dt>
                <dd>
                    {appointment.payment_status} ({appointment.payment_mode})
                    {appointment.payment && ` · ref ${appointment.payment.reference}`}
                </dd>
            </dl>

            {appointment.status === 'pending_clearance' && (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        clear.post(`/staff/appointments/${appointment.id}/clear`);
                    }}
                >
                    <label htmlFor="receipt_no">Receipt no</label>
                    <input
                        id="receipt_no"
                        value={clear.data.receipt_no}
                        onChange={(e) => clear.setData('receipt_no', e.target.value)}
                        required
                    />
                    <label htmlFor="method">Method</label>
                    <select id="method" value={clear.data.method} onChange={(e) => clear.setData('method', e.target.value)}>
                        <option value="cash">Cash</option>
                        <option value="transfer">Transfer</option>
                        <option value="pos">POS</option>
                    </select>
                    <button type="submit" disabled={clear.processing}>Clear payment</button>
                </form>
            )}

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    action.post(`/staff/appointments/${appointment.id}/reschedule`);
                }}
            >
                <label htmlFor="slot_id">Move to slot ID</label>
                <input
                    id="slot_id"
                    type="number"
                    value={action.data.slot_id}
                    onChange={(e) => action.setData('slot_id', Number(e.target.value))}
                    required
                />
                <button type="submit" disabled={action.processing}>Reschedule</button>
            </form>

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    cancel.post(`/staff/appointments/${appointment.id}/cancel`);
                }}
            >
                <label htmlFor="reason">Cancel reason</label>
                <input id="reason" value={cancel.data.reason} onChange={(e) => cancel.setData('reason', e.target.value)} />
                <button type="submit" disabled={cancel.processing}>Cancel</button>
            </form>

            <button type="button" onClick={() => action.post(`/staff/appointments/${appointment.id}/no-show`)}>
                Mark no-show
            </button>

            <p>
                <Link href="/staff/appointments">Back</Link>
            </p>
        </main>
    );
}
