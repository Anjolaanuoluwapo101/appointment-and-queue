import React from 'react';
import AsyncButton from '../../../Components/AsyncButton';

export default function ScheduleForm({ data, setData, errors, processing, onSubmit, practitioners, departments, weekdays, schedule }) {
    const practitionerDepts = practitioners?.find((p) => p.id === Number(data.practitioner_id))?.departments ?? [];

    return (
        <form onSubmit={onSubmit} className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label htmlFor="practitioner_id" className="block text-xs font-semibold text-zinc-700 mb-1">
                        Practitioner
                    </label>
                    <select
                        id="practitioner_id"
                        value={data.practitioner_id}
                        onChange={(e) => setData({ ...data, practitioner_id: Number(e.target.value), department_id: '' })}
                        required
                        disabled={!!schedule}
                        className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm disabled:bg-zinc-100 disabled:text-zinc-500"
                    >
                        <option value="">Select Practitioner...</option>
                        {practitioners?.map((p) => (
                            <option key={p.id} value={p.id}>
                                {p.full_name}
                            </option>
                        ))}
                    </select>
                    {errors.practitioner_id && <p className="text-xs text-rose-600 mt-1">{errors.practitioner_id}</p>}
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
                        disabled={!!schedule}
                        className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm disabled:bg-zinc-100 disabled:text-zinc-500"
                    >
                        <option value="">Select Department...</option>
                        {(schedule ? departments : practitionerDepts)?.map((d) => (
                            <option key={d.id} value={d.id}>
                                {d.name}
                            </option>
                        ))}
                    </select>
                    {errors.department_id && <p className="text-xs text-rose-600 mt-1">{errors.department_id}</p>}
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label htmlFor="weekday" className="block text-xs font-semibold text-zinc-700 mb-1">
                        Day of Week
                    </label>
                    <select
                        id="weekday"
                        value={data.weekday}
                        onChange={(e) => setData('weekday', Number(e.target.value))}
                        className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                    >
                        {weekdays?.map((d, i) => (
                            <option key={d} value={i}>
                                {d}
                            </option>
                        ))}
                    </select>
                    {errors.weekday && <p className="text-xs text-rose-600 mt-1">{errors.weekday}</p>}
                </div>

                <div>
                    <label htmlFor="start_time" className="block text-xs font-semibold text-zinc-700 mb-1">
                        Shift Start
                    </label>
                    <input
                        id="start_time"
                        type="time"
                        value={data.start_time}
                        onChange={(e) => setData('start_time', e.target.value)}
                        required
                        className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                    />
                    {errors.start_time && <p className="text-xs text-rose-600 mt-1">{errors.start_time}</p>}
                </div>

                <div>
                    <label htmlFor="end_time" className="block text-xs font-semibold text-zinc-700 mb-1">
                        Shift End
                    </label>
                    <input
                        id="end_time"
                        type="time"
                        value={data.end_time}
                        onChange={(e) => setData('end_time', e.target.value)}
                        required
                        className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                    />
                    {errors.end_time && <p className="text-xs text-rose-600 mt-1">{errors.end_time}</p>}
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label htmlFor="slot_duration_minutes" className="block text-xs font-semibold text-zinc-700 mb-1">
                        Slot Duration (minutes)
                    </label>
                    <input
                        id="slot_duration_minutes"
                        type="number"
                        min="5"
                        max="480"
                        value={data.slot_duration_minutes}
                        onChange={(e) => setData('slot_duration_minutes', Number(e.target.value))}
                        required
                        className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm font-mono"
                    />
                    {errors.slot_duration_minutes && <p className="text-xs text-rose-600 mt-1">{errors.slot_duration_minutes}</p>}
                </div>

                <div>
                    <label htmlFor="max_per_slot" className="block text-xs font-semibold text-zinc-700 mb-1">
                        Max Capacity per Slot
                    </label>
                    <input
                        id="max_per_slot"
                        type="number"
                        min="1"
                        max="100"
                        value={data.max_per_slot}
                        onChange={(e) => setData('max_per_slot', Number(e.target.value))}
                        required
                        className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm font-mono"
                    />
                    {errors.max_per_slot && <p className="text-xs text-rose-600 mt-1">{errors.max_per_slot}</p>}
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-zinc-100">
                <div>
                    <label htmlFor="break_start" className="block text-xs font-semibold text-zinc-700 mb-1">
                        Break Start (Optional)
                    </label>
                    <input
                        id="break_start"
                        type="time"
                        value={data.break_start}
                        onChange={(e) => setData('break_start', e.target.value)}
                        className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                    />
                </div>

                <div>
                    <label htmlFor="break_end" className="block text-xs font-semibold text-zinc-700 mb-1">
                        Break End (Optional)
                    </label>
                    <input
                        id="break_end"
                        type="time"
                        value={data.break_end}
                        onChange={(e) => setData('break_end', e.target.value)}
                        className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                    />
                </div>
            </div>

            {schedule && (
                <div className="flex items-center space-x-2 pt-1">
                    <input
                        id="is_active"
                        type="checkbox"
                        checked={data.is_active}
                        onChange={(e) => setData('is_active', e.target.checked)}
                        className="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"
                    />
                    <label htmlFor="is_active" className="text-xs text-zinc-700 font-medium">
                        Active Schedule Status
                    </label>
                </div>
            )}

            <div className="pt-2">
                <AsyncButton
                    type="submit"
                    loading={processing}
                    loadingText={schedule ? 'Saving schedule...' : 'Creating schedule...'}
                    variant="primary"
                    className="w-full bg-zinc-900 hover:bg-zinc-800 text-white font-semibold py-2 rounded-md text-xs shadow-sm"
                >
                    {schedule ? 'Update Schedule' : 'Create Weekly Schedule'}
                </AsyncButton>
            </div>
        </form>
    );
}
