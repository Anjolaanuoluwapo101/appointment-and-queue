import { useForm, usePage } from '@inertiajs/react';

export default function Create() {
    const { phone, patient, departments, department_id, practitioner_id, date, slots, errors } = usePage().props;
    const { data, setData, post, processing } = useForm({
        patient_id: patient?.id ?? '',
        department_id: department_id ?? '',
        practitioner_id: practitioner_id ?? '',
        slot_id: '',
        payment_mode: 'physical',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/staff/appointments');
    };

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem', maxWidth: '30rem' }}>
            <h1>Book on Behalf</h1>
            <form method="get">
                <label>
                    Find patient by phone
                    <input type="tel" name="phone" defaultValue={phone} />
                </label>
                <button type="submit">Find</button>
            </form>
            {phone !== '' && (patient ? <p>Patient: {patient.full_name}</p> : <p>No patient with that phone. Register them first.</p>)}

            <form method="get">
                <input type="hidden" name="phone" value={phone} />
                <label>
                    Department
                    <select name="department_id" defaultValue={department_id ?? ''}>
                        <option value="">Select…</option>
                        {departments.map((d) => (
                            <option key={d.id} value={d.id}>
                                {d.name}
                            </option>
                        ))}
                    </select>
                </label>
                <label>
                    Date
                    <input type="date" name="date" defaultValue={date ?? ''} />
                </label>
                <button type="submit">Show slots</button>
            </form>

            {patient && (
                <form onSubmit={submit}>
                    <input type="hidden" value={patient.id} />
                    <div>
                        <label htmlFor="slot_id">Slot</label>
                        <select id="slot_id" value={data.slot_id} onChange={(e) => setData('slot_id', Number(e.target.value))} required>
                            <option value="">Select…</option>
                            {slots.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.starts_at.slice(0, 16).replace('T', ' ')} · {s.practitioner.full_name}
                                </option>
                            ))}
                        </select>
                        {errors.slot && <p>{errors.slot}</p>}
                    </div>
                    <div>
                        <label>
                            <input
                                type="radio"
                                checked={data.payment_mode === 'physical'}
                                onChange={() => setData('payment_mode', 'physical')}
                            />
                            Pay at Hospital
                        </label>
                        <label>
                            <input
                                type="radio"
                                checked={data.payment_mode === 'online'}
                                onChange={() => setData('payment_mode', 'online')}
                            />
                            Pay Online
                        </label>
                    </div>
                    <button type="submit" disabled={processing}>
                        Book appointment
                    </button>
                </form>
            )}
        </main>
    );
}
