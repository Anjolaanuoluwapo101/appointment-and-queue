import { Link, router, useForm, usePage } from '@inertiajs/react';
import AsyncButton from '../../../Components/AsyncButton';
import { Badge } from '../../../Components/Badge';
import { Card, CardContent, CardHeader, CardTitle } from '../../../Components/Card';
import AppLayout from '../../../Layouts/AppLayout';

export default function Slots() {
    const { department, practitioner_id, date, fee_kobo, slots, errors } = usePage().props;
    const { data, setData, post, processing } = useForm({
        department_id: department.id,
        practitioner_id: practitioner_id ?? '',
        slot_id: '',
        payment_mode: department.payment_mode === 'online_required' ? 'online' : department.payment_mode === 'physical_only' ? 'physical' : 'online',
    });

    const handleDateChange = (e) => {
        const selectedDate = e.target.value;
        router.get(
            window.location.pathname,
            { date: selectedDate, practitioner_id: practitioner_id ?? '' },
            { preserveState: true, preserveScroll: true, only: ['slots', 'date'] }
        );
    };

    const submit = (e) => {
        e.preventDefault();
        post('/patient/book');
    };

    return (
        <AppLayout>
            <div className="py-6 space-y-6 max-w-3xl mx-auto bg-white">
                <div className="flex items-center justify-between border-b border-zinc-100 pb-4">
                    <div>
                        <span className="text-xs font-mono text-zinc-400 block uppercase">Step 3 of 3 · Appointment Slot</span>
                        <h1 className="text-2xl font-bold tracking-tight text-zinc-900">{department.name}</h1>
                    </div>
                    <Badge variant="outline" className="text-xs">
                        Fee: ₦{(fee_kobo / 100).toLocaleString()}
                    </Badge>
                </div>

                {errors.slot && (
                    <div className="p-3 bg-rose-50 border border-rose-200 rounded text-rose-800 text-xs font-medium">
                        {errors.slot}
                    </div>
                )}

                <Card className="bg-white border border-zinc-200">
                    <CardHeader>
                        <CardTitle className="text-sm">Filter Slot Date</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <input
                            type="date"
                            defaultValue={date ?? ''}
                            onChange={handleDateChange}
                            className="w-full bg-white border border-zinc-200 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-400 shadow-sm"
                        />
                    </CardContent>
                </Card>

                <form onSubmit={submit} className="space-y-6">
                    <Card className="bg-white border border-zinc-200">
                        <CardHeader>
                            <CardTitle className="text-sm">Select Available Time Slot</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <select
                                id="slot_id"
                                value={data.slot_id}
                                onChange={(e) => setData('slot_id', Number(e.target.value))}
                                required
                                className="w-full bg-white border border-zinc-200 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-400 shadow-sm"
                            >
                                <option value="">Select a time slot…</option>
                                {slots.map((s) => (
                                    <option key={s.id} value={s.id}>
                                        {s.starts_at.slice(0, 16).replace('T', ' ')} · {s.practitioner.full_name}
                                    </option>
                                ))}
                            </select>
                        </CardContent>
                    </Card>

                    {department.payment_mode === 'allow_both' && (
                        <Card className="bg-white border border-zinc-200">
                            <CardHeader>
                                <CardTitle className="text-sm">Payment Method</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex space-x-6 text-xs text-zinc-800">
                                    <label className="inline-flex items-center space-x-2">
                                        <input
                                            type="radio"
                                            checked={data.payment_mode === 'online'}
                                            onChange={() => setData('payment_mode', 'online')}
                                            className="text-zinc-900 focus:ring-zinc-900"
                                        />
                                        <span>Pay Online Now (Paystack)</span>
                                    </label>
                                    <label className="inline-flex items-center space-x-2">
                                        <input
                                            type="radio"
                                            checked={data.payment_mode === 'physical'}
                                            onChange={() => setData('payment_mode', 'physical')}
                                            className="text-zinc-900 focus:ring-zinc-900"
                                        />
                                        <span>Pay at Hospital</span>
                                    </label>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    <div className="flex items-center justify-between pt-2">
                        <Link href={`/patient/book/${department.id}/practitioners`} className="text-xs font-medium text-zinc-600 hover:text-zinc-900">
                            ← Back to Practitioners
                        </Link>
                        <AsyncButton
                            type="submit"
                            loading={processing}
                            loadingText="Confirming appointment..."
                            variant="primary"
                            className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-5 py-2 rounded text-xs shadow-sm"
                        >
                            Confirm Appointment
                        </AsyncButton>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
