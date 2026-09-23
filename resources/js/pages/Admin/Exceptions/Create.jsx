import { useForm, usePage } from '@inertiajs/react';
import AsyncButton from '../../../Components/AsyncButton';
import { Card, CardContent, CardHeader, CardTitle } from '../../../Components/Card';
import AppLayout from '../../../Layouts/AppLayout';

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
        <AppLayout>
            <div className="space-y-6 max-w-xl mx-auto bg-white py-6">
                <div className="border-b border-zinc-100 pb-4">
                    <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Add Schedule Exception</h1>
                    <p className="text-xs text-zinc-500 mt-1">Configure leave dates or adjusted working hours for a practitioner</p>
                </div>

                <Card className="bg-white border border-zinc-200">
                    <CardHeader>
                        <CardTitle className="text-sm">Exception Parameters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <label htmlFor="practitioner_id" className="block text-xs font-semibold text-zinc-700 mb-1">
                                    Practitioner
                                </label>
                                <select
                                    id="practitioner_id"
                                    value={data.practitioner_id}
                                    onChange={(e) => setData('practitioner_id', Number(e.target.value))}
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
                                <label htmlFor="date" className="block text-xs font-semibold text-zinc-700 mb-1">
                                    Exception Date
                                </label>
                                <input
                                    id="date"
                                    type="date"
                                    value={data.date}
                                    onChange={(e) => setData('date', e.target.value)}
                                    required
                                    className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                />
                                {errors.date && <p className="text-xs text-rose-600 mt-1">{errors.date}</p>}
                            </div>

                            <div>
                                <label htmlFor="type" className="block text-xs font-semibold text-zinc-700 mb-1">
                                    Exception Type
                                </label>
                                <select
                                    id="type"
                                    value={data.type}
                                    onChange={(e) => setData('type', e.target.value)}
                                    className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                >
                                    <option value="day_off">Day Off (Leave)</option>
                                    <option value="adjusted">Adjusted Working Hours</option>
                                </select>
                            </div>

                            {data.type === 'adjusted' && (
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label htmlFor="start_time" className="block text-xs font-semibold text-zinc-700 mb-1">
                                            Adjusted Start Time
                                        </label>
                                        <input
                                            id="start_time"
                                            type="time"
                                            value={data.start_time}
                                            onChange={(e) => setData('start_time', e.target.value)}
                                            className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                        />
                                    </div>
                                    <div>
                                        <label htmlFor="end_time" className="block text-xs font-semibold text-zinc-700 mb-1">
                                            Adjusted End Time
                                        </label>
                                        <input
                                            id="end_time"
                                            type="time"
                                            value={data.end_time}
                                            onChange={(e) => setData('end_time', e.target.value)}
                                            className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                        />
                                    </div>
                                </div>
                            )}

                            <div>
                                <label htmlFor="reason" className="block text-xs font-semibold text-zinc-700 mb-1">
                                    Reason / Notes (Optional)
                                </label>
                                <input
                                    id="reason"
                                    value={data.reason}
                                    onChange={(e) => setData('reason', e.target.value)}
                                    placeholder="Leave, Conference, Training..."
                                    className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                />
                            </div>

                            <div className="pt-3">
                                <AsyncButton
                                    type="submit"
                                    loading={processing}
                                    loadingText="Saving exception..."
                                    variant="primary"
                                    className="w-full bg-zinc-900 hover:bg-zinc-800 text-white font-semibold py-2.5 rounded text-xs shadow-sm"
                                >
                                    Save Schedule Exception
                                </AsyncButton>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
