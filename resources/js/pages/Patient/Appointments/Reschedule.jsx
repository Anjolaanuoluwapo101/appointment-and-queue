import { Link, useForm, usePage } from '@inertiajs/react';

export default function Reschedule() {
    const { appointment, slots, errors } = usePage().props;
    const { data, setData, post, processing } = useForm({ slot_id: '' });

    const submit = (e) => {
        e.preventDefault();
        post(`/patient/appointments/${appointment.id}/reschedule`);
    };

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem', maxWidth: '28rem' }}>
            <h1>Reschedule — {appointment.department.name}</h1>
            {errors.slot && <p>{errors.slot}</p>}
            <form onSubmit={submit}>
                <div>
                    <label htmlFor="slot_id">New slot</label>
                    <select id="slot_id" value={data.slot_id} onChange={(e) => setData('slot_id', Number(e.target.value))} required>
                        <option value="">Select…</option>
                        {slots.map((s) => (
                            <option key={s.id} value={s.id}>
                                {s.starts_at.slice(0, 16).replace('T', ' ')} · {s.practitioner.full_name}
                            </option>
                        ))}
                    </select>
                </div>
                <button type="submit" disabled={processing}>Move appointment</button>
            </form>
            <p>
                <Link href={`/patient/appointments/${appointment.id}`}>Back</Link>
            </p>
        </main>
    );
}
