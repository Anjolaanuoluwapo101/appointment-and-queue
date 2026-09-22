import { Link, router, useForm, usePage } from '@inertiajs/react';
import AsyncButton from '../../../Components/AsyncButton';

export default function Slots() {
    const { department, practitioner_id, date, fee_kobo, slots, status, errors } = usePage().props;
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
        <main className="p-8 font-sans max-w-3xl mx-auto">
            <h1 className="text-2xl font-bold mb-4">{department.name} — Available Slots</h1>
            {status && <p className="mb-4 text-emerald-600 font-medium">{status}</p>}
            {errors.slot && <p className="mb-4 text-rose-600">{errors.slot}</p>}
            <p className="mb-6 text-gray-700">Fee: <span className="font-semibold">₦{(fee_kobo / 100).toLocaleString()}</span></p>

            <div className="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                    Filter Date
                    <input
                        type="date"
                        defaultValue={date ?? ''}
                        onChange={handleDateChange}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"
                    />
                </label>
            </div>

            <form onSubmit={submit} className="space-y-6">
                <div>
                    <label htmlFor="slot_id" className="block text-sm font-medium text-gray-700 mb-1">Available Slot</label>
                    <select
                        id="slot_id"
                        value={data.slot_id}
                        onChange={(e) => setData('slot_id', Number(e.target.value))}
                        required
                        className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"
                    >
                        <option value="">Select a time slot…</option>
                        {slots.map((s) => (
                            <option key={s.id} value={s.id}>
                                {s.starts_at.slice(0, 16).replace('T', ' ')} · {s.practitioner.full_name}
                            </option>
                        ))}
                    </select>
                </div>

                {department.payment_mode === 'allow_both' && (
                    <div className="space-y-2 bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <span className="block text-sm font-medium text-gray-700 mb-1">Payment Method</span>
                        <div className="flex space-x-6">
                            <label className="inline-flex items-center space-x-2 text-sm text-gray-700">
                                <input
                                    type="radio"
                                    checked={data.payment_mode === 'online'}
                                    onChange={() => setData('payment_mode', 'online')}
                                    className="text-blue-600 focus:ring-blue-500"
                                />
                                <span>Pay Online Now</span>
                            </label>
                            <label className="inline-flex items-center space-x-2 text-sm text-gray-700">
                                <input
                                    type="radio"
                                    checked={data.payment_mode === 'physical'}
                                    onChange={() => setData('payment_mode', 'physical')}
                                    className="text-blue-600 focus:ring-blue-500"
                                />
                                <span>Pay at Hospital</span>
                            </label>
                        </div>
                    </div>
                )}

                <div className="pt-2">
                    <AsyncButton
                        type="submit"
                        loading={processing}
                        loadingText="Confirming appointment..."
                        variant="primary"
                    >
                        Confirm appointment
                    </AsyncButton>
                </div>
            </form>

            <div className="mt-8">
                <Link href={`/patient/book/${department.id}/practitioners`} className="text-blue-600 hover:underline text-sm font-medium">
                    ← Back to practitioners
                </Link>
            </div>
        </main>
    );
}
