import AsyncButton from '../../../Components/AsyncButton';

export default function DepartmentForm({ data, setData, errors, processing, onSubmit, department }) {
    return (
        <form onSubmit={onSubmit} className="space-y-4">
            <div>
                <label htmlFor="name" className="block text-xs font-semibold text-zinc-700 mb-1">
                    Department Name
                </label>
                <input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    required
                    className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                />
                {errors.name && <p className="text-xs text-rose-600 mt-1">{errors.name}</p>}
            </div>

            <div>
                <label htmlFor="queue_prefix" className="block text-xs font-semibold text-zinc-700 mb-1">
                    Queue Prefix (1–3 Uppercase Letters e.g. CARD)
                </label>
                <input
                    id="queue_prefix"
                    value={data.queue_prefix}
                    onChange={(e) => setData('queue_prefix', e.target.value.toUpperCase())}
                    required
                    className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm font-mono"
                />
                {errors.queue_prefix && <p className="text-xs text-rose-600 mt-1">{errors.queue_prefix}</p>}
            </div>

            <div>
                <label htmlFor="room_label" className="block text-xs font-semibold text-zinc-700 mb-1">
                    Room / Location Label (e.g. Consultation Room 2)
                </label>
                <input
                    id="room_label"
                    value={data.room_label}
                    onChange={(e) => setData('room_label', e.target.value)}
                    className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                />
                {errors.room_label && <p className="text-xs text-rose-600 mt-1">{errors.room_label}</p>}
            </div>

            <div>
                <label htmlFor="payment_mode" className="block text-xs font-semibold text-zinc-700 mb-1">
                    Payment Policy Mode
                </label>
                <select
                    id="payment_mode"
                    value={data.payment_mode}
                    onChange={(e) => setData('payment_mode', e.target.value)}
                    className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                >
                    <option value="allow_both">Allow both (Online & Physical)</option>
                    <option value="physical_only">Physical Cash / POS Only</option>
                    <option value="online_required">Online Paystack Required Prior to Slot</option>
                </select>
                {errors.payment_mode && <p className="text-xs text-rose-600 mt-1">{errors.payment_mode}</p>}
            </div>

            <div>
                <label htmlFor="base_fee_kobo" className="block text-xs font-semibold text-zinc-700 mb-1">
                    Base Consultation Fee (Kobo e.g. 1500000 = ₦15,000)
                </label>
                <input
                    id="base_fee_kobo"
                    type="number"
                    min="0"
                    value={data.base_fee_kobo}
                    onChange={(e) => setData('base_fee_kobo', Number(e.target.value))}
                    required
                    className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm font-mono"
                />
                {errors.base_fee_kobo && <p className="text-xs text-rose-600 mt-1">{errors.base_fee_kobo}</p>}
            </div>

            {department && (
                <div className="flex items-center space-x-2 pt-1">
                    <input
                        id="is_active"
                        type="checkbox"
                        checked={data.is_active}
                        onChange={(e) => setData('is_active', e.target.checked)}
                        className="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"
                    />
                    <label htmlFor="is_active" className="text-xs text-zinc-700 font-medium">
                        Active Status
                    </label>
                </div>
            )}

            <div className="pt-3">
                <AsyncButton
                    type="submit"
                    loading={processing}
                    loadingText="Saving department..."
                    variant="primary"
                    className="w-full bg-zinc-900 hover:bg-zinc-800 text-white font-semibold py-2 rounded text-xs shadow-sm"
                >
                    Save Department
                </AsyncButton>
            </div>
        </form>
    );
}
