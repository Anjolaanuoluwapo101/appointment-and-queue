import { useForm, usePage } from '@inertiajs/react';

export default function BulkCancellation() {
    const { filters, preview, practitioners, status, errors } = usePage().props;
    const filter = useForm({
        practitioner_id: filters.practitioner_id ?? '',
        department_id: filters.department_id ?? '',
        date: filters.date ?? '',
    });
    const confirm = useForm({
        practitioner_id: filters.practitioner_id ?? '',
        department_id: filters.department_id ?? '',
        date: filters.date ?? '',
        reason: '',
        replacement_practitioner_id: '',
    });

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem', maxWidth: '32rem' }}>
            <h1>Bulk Cancellation</h1>
            {status && <p>{status}</p>}
            <form
                method="get"
                onSubmit={(e) => {
                    e.preventDefault();
                    filter.get('/admin/bulk-cancellation');
                }}
            >
                <label>
                    Practitioner
                    <select value={filter.data.practitioner_id} onChange={(e) => filter.setData('practitioner_id', e.target.value)} required>
                        <option value="">Select…</option>
                        {practitioners.map((p) => (
                            <option key={p.id} value={p.id}>
                                {p.full_name}
                            </option>
                        ))}
                    </select>
                </label>
                <label>
                    Date
                    <input type="date" value={filter.data.date} onChange={(e) => filter.setData('date', e.target.value)} required />
                </label>
                <button type="submit">Preview</button>
            </form>

            {preview && (
                <>
                    <p>
                        {preview.total} scheduled appointment(s), {preview.paid} paid (auto-refund on cancel).
                    </p>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            confirm.setData({
                                ...confirm.data,
                                practitioner_id: filter.data.practitioner_id,
                                date: filter.data.date,
                            });
                            confirm.post('/admin/bulk-cancellation');
                        }}
                    >
                        <label htmlFor="reason">Reason</label>
                        <input
                            id="reason"
                            value={confirm.data.reason}
                            onChange={(e) => confirm.setData('reason', e.target.value)}
                            required
                        />
                        {errors.reason && <p>{errors.reason}</p>}
                        <label htmlFor="replacement_practitioner_id">Replacement practitioner (optional)</label>
                        <select
                            id="replacement_practitioner_id"
                            value={confirm.data.replacement_practitioner_id}
                            onChange={(e) => confirm.setData('replacement_practitioner_id', e.target.value)}
                        >
                            <option value="">None — cancel all</option>
                            {practitioners.map((p) => (
                                <option key={p.id} value={p.id}>
                                    {p.full_name}
                                </option>
                            ))}
                        </select>
                        <button type="submit" disabled={confirm.processing}>
                            Confirm bulk action
                        </button>
                    </form>
                </>
            )}
        </main>
    );
}
