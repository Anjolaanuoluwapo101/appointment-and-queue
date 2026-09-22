import { useForm, usePage } from '@inertiajs/react';

export default function Settings() {
    const { settings, status } = usePage().props;
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

    const field = (name, label) => (
        <div>
            <label htmlFor={name}>{label}</label>
            <input
                id={name}
                type="number"
                value={data[name]}
                onChange={(e) => setData(name, Number(e.target.value))}
                required
            />
            {errors[name] && <p>{errors[name]}</p>}
        </div>
    );

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem', maxWidth: '28rem' }}>
            <h1>System Settings</h1>
            {status && <p>{status}</p>}
            <form onSubmit={submit}>
                {field('session_lifetime_staff', 'Staff session (minutes)')}
                {field('session_lifetime_patient', 'Patient session (minutes)')}
                {field('queue_low_threshold', 'Queue-low threshold')}
                {field('booking_window_days', 'Booking window (days)')}
                {field('booking_cutoff_minutes', 'Booking cutoff (minutes before slot)')}
                <button type="submit" disabled={processing}>Save</button>
            </form>
        </main>
    );
}
