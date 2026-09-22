import { useForm, usePage } from '@inertiajs/react';

export default function WalkIn() {
    const { departments, errors } = usePage().props;
    const { data, setData, post, processing } = useForm({
        department_id: '',
        practitioner_id: '',
        full_name: '',
        phone: '',
        receipt_no: '',
        method: 'cash',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/staff/walk-in');
    };

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem', maxWidth: '28rem' }}>
            <h1>Walk-in Quick-add</h1>
            <form onSubmit={submit}>
                <div>
                    <label htmlFor="department_id">Department</label>
                    <select
                        id="department_id"
                        value={data.department_id}
                        onChange={(e) => setData('department_id', Number(e.target.value))}
                        required
                    >
                        <option value="">Select…</option>
                        {departments.map((d) => (
                            <option key={d.id} value={d.id}>
                                {d.name}
                            </option>
                        ))}
                    </select>
                </div>
                <div>
                    <label htmlFor="full_name">Full name</label>
                    <input id="full_name" value={data.full_name} onChange={(e) => setData('full_name', e.target.value)} required />
                    {errors.full_name && <p>{errors.full_name}</p>}
                </div>
                <div>
                    <label htmlFor="phone">Phone</label>
                    <input id="phone" type="tel" value={data.phone} onChange={(e) => setData('phone', e.target.value)} required />
                    {errors.phone && <p>{errors.phone}</p>}
                </div>
                <div>
                    <label htmlFor="receipt_no">Receipt no</label>
                    <input id="receipt_no" value={data.receipt_no} onChange={(e) => setData('receipt_no', e.target.value)} required />
                    {errors.receipt_no && <p>{errors.receipt_no}</p>}
                </div>
                <div>
                    <label htmlFor="method">Method</label>
                    <select id="method" value={data.method} onChange={(e) => setData('method', e.target.value)}>
                        <option value="cash">Cash</option>
                        <option value="transfer">Transfer</option>
                        <option value="pos">POS</option>
                    </select>
                </div>
                <button type="submit" disabled={processing}>Queue walk-in</button>
            </form>
        </main>
    );
}
