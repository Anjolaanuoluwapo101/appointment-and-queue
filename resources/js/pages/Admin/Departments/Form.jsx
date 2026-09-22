export default function DepartmentForm({ data, setData, errors, processing, onSubmit, department }) {
    return (
        <form onSubmit={onSubmit}>
            <div>
                <label htmlFor="name">Name</label>
                <input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                {errors.name && <p>{errors.name}</p>}
            </div>
            <div>
                <label htmlFor="queue_prefix">Queue prefix (1–3 uppercase letters)</label>
                <input
                    id="queue_prefix"
                    value={data.queue_prefix}
                    onChange={(e) => setData('queue_prefix', e.target.value.toUpperCase())}
                    required
                />
                {errors.queue_prefix && <p>{errors.queue_prefix}</p>}
            </div>
            <div>
                <label htmlFor="room_label">Called-instruction room (e.g. Consultation Room 2)</label>
                <input id="room_label" value={data.room_label} onChange={(e) => setData('room_label', e.target.value)} />
                {errors.room_label && <p>{errors.room_label}</p>}
            </div>
            <div>
                <label htmlFor="payment_mode">Payment mode</label>
                <select id="payment_mode" value={data.payment_mode} onChange={(e) => setData('payment_mode', e.target.value)}>
                    <option value="allow_both">Allow both</option>
                    <option value="physical_only">Physical only</option>
                    <option value="online_required">Online required</option>
                </select>
                {errors.payment_mode && <p>{errors.payment_mode}</p>}
            </div>
            <div>
                <label htmlFor="base_fee_kobo">Base fee (kobo)</label>
                <input
                    id="base_fee_kobo"
                    type="number"
                    min="0"
                    value={data.base_fee_kobo}
                    onChange={(e) => setData('base_fee_kobo', Number(e.target.value))}
                    required
                />
                {errors.base_fee_kobo && <p>{errors.base_fee_kobo}</p>}
            </div>
            {department && (
                <label>
                    <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />
                    Active
                </label>
            )}
            <button type="submit" disabled={processing}>Save</button>
        </form>
    );
}
