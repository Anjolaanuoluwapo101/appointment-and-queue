import { useForm, usePage } from '@inertiajs/react';
import AsyncButton from '../../Components/AsyncButton';
import { Badge } from '../../Components/Badge';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/Card';
import AppLayout from '../../Layouts/AppLayout';

export default function BulkCancellation() {
    const { filters, preview, practitioners, errors } = usePage().props;
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
        <AppLayout>
            <div className="space-y-6 max-w-3xl mx-auto bg-white py-6">
                <div className="border-b border-zinc-100 pb-4">
                    <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Emergency Bulk Cancellation & Re-assignment</h1>
                    <p className="text-xs text-zinc-500 mt-1">Cancel or reassign all appointments for a specific practitioner on an emergency date</p>
                </div>

                <Card className="bg-white border border-zinc-200">
                    <CardHeader>
                        <CardTitle className="text-sm">Step 1: Select Practitioner & Date</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                filter.get('/admin/bulk-cancellation');
                            }}
                            className="space-y-4"
                        >
                            <div>
                                <label className="block text-xs font-semibold text-zinc-700 mb-1">Practitioner</label>
                                <select
                                    value={filter.data.practitioner_id}
                                    onChange={(e) => filter.setData('practitioner_id', e.target.value)}
                                    required
                                    className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                >
                                    <option value="">Select Practitioner…</option>
                                    {practitioners.map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.full_name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-zinc-700 mb-1">Cancellation Date</label>
                                <input
                                    type="date"
                                    value={filter.data.date}
                                    onChange={(e) => filter.setData('date', e.target.value)}
                                    required
                                    className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                />
                            </div>

                            <AsyncButton
                                type="submit"
                                loading={filter.processing}
                                loadingText="Calculating impact..."
                                variant="outline"
                                className="w-full text-xs font-semibold py-2 rounded"
                            >
                                Preview Affected Appointments
                            </AsyncButton>
                        </form>
                    </CardContent>
                </Card>

                {preview && (
                    <Card className="bg-white border-2 border-rose-200">
                        <CardHeader className="border-b border-rose-100 pb-3">
                            <CardTitle className="text-sm text-rose-800">Step 2: Confirm Bulk Action Impact</CardTitle>
                            <Badge variant="destructive" className="font-mono">{preview.total} Affected</Badge>
                        </CardHeader>
                        <CardContent className="space-y-4 pt-4">
                            <div className="p-3 bg-rose-50 border border-rose-200 rounded text-xs text-rose-900 space-y-1">
                                <p className="font-bold">Affected Appointments Impact:</p>
                                <p>• <strong>{preview.total}</strong> scheduled appointment(s) found on this date.</p>
                                <p>• <strong>{preview.paid}</strong> paid appointment(s) (Paystack online payments will automatically trigger refunds on cancellation).</p>
                            </div>

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
                                className="space-y-4"
                            >
                                <div>
                                    <label htmlFor="reason" className="block text-xs font-semibold text-zinc-700 mb-1">
                                        Cancellation Reason (Communicated to Patients)
                                    </label>
                                    <input
                                        id="reason"
                                        value={confirm.data.reason}
                                        onChange={(e) => confirm.setData('reason', e.target.value)}
                                        required
                                        placeholder="Specify emergency reason..."
                                        className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                    />
                                    {errors.reason && <p className="text-xs text-rose-600 mt-1">{errors.reason}</p>}
                                </div>

                                <div>
                                    <label htmlFor="replacement_practitioner_id" className="block text-xs font-semibold text-zinc-700 mb-1">
                                        Replacement Practitioner (Optional Re-assignment)
                                    </label>
                                    <select
                                        id="replacement_practitioner_id"
                                        value={confirm.data.replacement_practitioner_id}
                                        onChange={(e) => confirm.setData('replacement_practitioner_id', e.target.value)}
                                        className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                    >
                                        <option value="">None — Cancel and refund all affected patients</option>
                                        {practitioners.map((p) => (
                                            <option key={p.id} value={p.id}>
                                                Reassign to {p.full_name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <AsyncButton
                                    type="submit"
                                    loading={confirm.processing}
                                    loadingText="Processing bulk action..."
                                    variant="destructive"
                                    className="w-full bg-rose-600 hover:bg-rose-700 text-white font-semibold py-2.5 rounded text-xs shadow-sm"
                                >
                                    Confirm Bulk Emergency Action
                                </AsyncButton>
                            </form>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
