import { useForm, usePage } from '@inertiajs/react';

export default function Create() {
    const { practitioners } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({
        practitioner_id: '',
        department_id: '',
        date: '',
        type: 'day_off',
        start_time: '',
        end_time: '',
        reason: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/admin/exceptions');
    };

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem', maxWidth: '28rem' }}>
            <h1>New Exception</h1>
            <form onSubmit={submit}>
                <div>
                    <label htmlFor="practitioner_id">Practitioner</label>
                    <select
                        id="practitioner_id"
                        value={data.practitioner_id}
                        onChange={(e) => setData('practitioner_id', Number(e.target.value))}
                        required
                    >
                        <option value="">Select…</option>
                        {practitioners.map((p) => (
                            <option key={p.id} value={p.id}>
                                {p.full_name}
                            </option>
                        ))}
                    </select>
                </div>
                <div>
                    <label htmlFor="date">Date</label>
                    <input id="date" type="date" value={data.date} onChange={(e) => setData('date', e.target.value)} required />
                    {errors.date && <p>{errors.date}</p>}
                </div>
                <div>
                    <label htmlFor="type">Type</label>
                    <select id="type" value={data.type} onChange={(e) => setData('type', e.target.value)}>
                        <option value="day_off">Day off</option>
                        <option value="adjusted">Adjusted hours</option>
                    </select>
                </div>
                {data.type === 'adjusted' && (
                    <>
                        <div>
                            <label htmlFor="start_time">Start</label>
                            <input
                                id="start_time"
                                type="time"
                                value={data.start_time}
                                onChange={(e) => setData('start_time', e.target.value)}
                            />
                        </div>
                        <div>
                            <label htmlFor="end_time">End</label>
                            <input id="end_time" type="time" value={data.end_time} onChange={(e) => setData('end_time', e.target.value)} />
                        </div>
                    </>
                )}
                <div>
                    <label htmlFor="reason">Reason</label>
                    <input id="reason" value={data.reason} onChange={(e) => setData('reason', e.target.value)} />
                </div>
                <button type="submit" disabled={processing}>Save</button>
            </form>
        </main>
    );
}
