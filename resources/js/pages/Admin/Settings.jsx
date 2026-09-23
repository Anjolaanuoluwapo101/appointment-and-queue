import { useForm, usePage } from '@inertiajs/react';
import AsyncButton from '../../Components/AsyncButton';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/Card';
import AppLayout from '../../Layouts/AppLayout';

export default function Settings() {
    const { settings } = usePage().props;
    const { data, setData, put, processing, errors } = useForm({
        session_lifetime_staff: settings.session_lifetime_staff,
        session_lifetime_patient: settings.session_lifetime_patient,
        queue_low_threshold: settings.queue_low_threshold,
        booking_window_days: settings.booking_window_days,
        booking_cutoff_minutes: settings.booking_cutoff_minutes,
    });

    const submit = (e) => {
        e.preventDefault();
        put('/admin/settings');
    };

    const renderInput = (name, label) => (
        <div>
            <label htmlFor={name} className="block text-xs font-semibold text-zinc-700 mb-1">
                {label}
            </label>
            <input
                id={name}
                type="number"
                value={data[name]}
                onChange={(e) => setData(name, Number(e.target.value))}
                required
                className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm font-mono"
            />
            {errors[name] && <p className="text-xs text-rose-600 mt-1">{errors[name]}</p>}
        </div>
    );

    return (
        <AppLayout>
            <div className="space-y-6 max-w-2xl mx-auto bg-white py-6">
                <div className="border-b border-zinc-100 pb-4">
                    <h1 className="text-2xl font-bold tracking-tight text-zinc-900">System Configuration Settings</h1>
                    <p className="text-xs text-zinc-500 mt-1">Configure session timeouts, booking windows, and queue threshold parameters</p>
                </div>

                <Card className="bg-white border border-zinc-200">
                    <CardHeader>
                        <CardTitle className="text-sm">Operational Parameters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            {renderInput('session_lifetime_staff', 'Staff Session Lifetime (Minutes)')}
                            {renderInput('session_lifetime_patient', 'Patient Session Lifetime (Minutes)')}
                            {renderInput('queue_low_threshold', 'Queue Low Threshold (Count)')}
                            {renderInput('booking_window_days', 'Booking Advance Window (Days)')}
                            {renderInput('booking_cutoff_minutes', 'Booking Cutoff Time (Minutes before slot starts)')}

                            <div className="pt-3">
                                <AsyncButton
                                    type="submit"
                                    loading={processing}
                                    loadingText="Saving settings..."
                                    variant="primary"
                                    className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-5 py-2 rounded text-xs shadow-sm"
                                >
                                    Save System Configuration
                                </AsyncButton>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
