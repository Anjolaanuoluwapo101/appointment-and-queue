import { useForm, usePage } from '@inertiajs/react';
import AsyncButton from '../../../Components/AsyncButton';
import { Card, CardContent, CardHeader, CardTitle } from '../../../Components/Card';
import AppLayout from '../../../Layouts/AppLayout';

export default function WalkIn() {
    const { departments, errors } = usePage().props;
    const { data, setData, post, processing } = useForm({
        department_id: '',
        practitioner_id: '',
        patient_number: '',
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
        <AppLayout>
            <div className="space-y-6 max-w-xl mx-auto bg-white py-6">
                <div className="border-b border-zinc-100 pb-4">
                    <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Walk-In Patient Registration</h1>
                    <p className="text-xs text-zinc-500 mt-1">Generate immediate walk-in queue ticket for on-site patients</p>
                </div>

                <Card className="bg-white border border-zinc-200">
                    <CardHeader>
                        <CardTitle className="text-sm">Ticket Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <label htmlFor="patient_number" className="block text-xs font-semibold text-zinc-700 mb-1">
                                    Hospital Patient Number (Optional)
                                </label>
                                <input
                                    id="patient_number"
                                    value={data.patient_number}
                                    onChange={(e) => setData('patient_number', e.target.value)}
                                    placeholder="e.g. PAT-2026-00001 (Leave blank for new patient)"
                                    className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm font-mono"
                                />
                                {errors.patient_number && <p className="text-xs text-rose-600 mt-1">{errors.patient_number}</p>}
                            </div>

                            <div>
                                <label htmlFor="department_id" className="block text-xs font-semibold text-zinc-700 mb-1">
                                    Department
                                </label>
                                <select
                                    id="department_id"
                                    value={data.department_id}
                                    onChange={(e) => setData('department_id', Number(e.target.value))}
                                    required
                                    className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                >
                                    <option value="">Select Department…</option>
                                    {departments.map((d) => (
                                        <option key={d.id} value={d.id}>
                                            {d.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label htmlFor="full_name" className="block text-xs font-semibold text-zinc-700 mb-1">
                                    Patient Full Name
                                </label>
                                <input
                                    id="full_name"
                                    value={data.full_name}
                                    onChange={(e) => setData('full_name', e.target.value)}
                                    required
                                    className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                />
                                {errors.full_name && <p className="text-xs text-rose-600 mt-1">{errors.full_name}</p>}
                            </div>

                            <div>
                                <label htmlFor="phone" className="block text-xs font-semibold text-zinc-700 mb-1">
                                    Phone Number
                                </label>
                                <input
                                    id="phone"
                                    type="tel"
                                    value={data.phone}
                                    onChange={(e) => setData('phone', e.target.value)}
                                    required
                                    className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                />
                                {errors.phone && <p className="text-xs text-rose-600 mt-1">{errors.phone}</p>}
                            </div>

                            <div>
                                <label htmlFor="receipt_no" className="block text-xs font-semibold text-zinc-700 mb-1">
                                    Physical Receipt / Reference No
                                </label>
                                <input
                                    id="receipt_no"
                                    value={data.receipt_no}
                                    onChange={(e) => setData('receipt_no', e.target.value)}
                                    required
                                    className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                />
                                {errors.receipt_no && <p className="text-xs text-rose-600 mt-1">{errors.receipt_no}</p>}
                            </div>

                            <div>
                                <label htmlFor="method" className="block text-xs font-semibold text-zinc-700 mb-1">
                                    Payment Method
                                </label>
                                <select
                                    id="method"
                                    value={data.method}
                                    onChange={(e) => setData('method', e.target.value)}
                                    className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                >
                                    <option value="cash">Cash</option>
                                    <option value="transfer">Bank Transfer</option>
                                    <option value="pos">POS Terminal</option>
                                </select>
                            </div>

                            <div className="pt-3">
                                <AsyncButton
                                    type="submit"
                                    loading={processing}
                                    loadingText="Generating ticket..."
                                    variant="primary"
                                    className="w-full bg-zinc-900 hover:bg-zinc-800 text-white font-semibold py-2.5 rounded text-xs shadow-sm"
                                >
                                    Generate Queue Ticket
                                </AsyncButton>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
