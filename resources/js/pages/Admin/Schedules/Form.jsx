export default function ScheduleForm({ data, setData, errors, processing, onSubmit, practitioners, departments, weekdays, schedule }) {
    const practitionerDepts = practitioners.find((p) => p.id === Number(data.practitioner_id))?.departments ?? [];

    return (
        <form onSubmit={onSubmit}>
            <div>
                <label htmlFor="practitioner_id">Practitioner</label>
                <select
                    id="practitioner_id"
                    value={data.practitioner_id}
                    onChange={(e) => setData({ practitioner_id: Number(e.target.value), department_id: '' })}
                    required
                    disabled={!!schedule}
                >
                    <option value="">Select…</option>
                    {practitioners.map((p) => (
                        <option key={p.id} value={p.id}>
                            {p.full_name}
                        </option>
                    ))}
                </select>
            </div>
            <div>
                <label htmlFor="department_id">Department</label>
                <select
                    id="department_id"
                    value={data.department_id}
                    onChange={(e) => setData('department_id', Number(e.target.value))}
                    required
                    disabled={!!schedule}
                >
                    <option value="">Select…</option>
                    {(schedule ? departments : practitionerDepts).map((d) => (
                        <option key={d.id} value={d.id}>
                            {d.name}
                        </option>
                    ))}
                </select>
                {errors.department_id && <p>{errors.department_id}</p>}
            </div>
            <div>
                <label htmlFor="weekday">Day</label>
                <select id="weekday" value={data.weekday} onChange={(e) => setData('weekday', Number(e.target.value))}>
                    {weekdays.map((d, i) => (
                        <option key={d} value={i}>
                            {d}
                        </option>
                    ))}
                </select>
            </div>
            <div>
                <label htmlFor="start_time">Start</label>
                <input id="start_time" type="time" value={data.start_time} onChange={(e) => setData('start_time', e.target.value)} required />
            </div>
            <div>
                <label htmlFor="end_time">End</label>
                <input id="end_time" type="time" value={data.end_time} onChange={(e) => setData('end_time', e.target.value)} required />
            </div>
            <div>
                <label htmlFor="slot_duration_minutes">Slot duration (minutes)</label>
                <input
                    id="slot_duration_minutes"
                    type="number"
                    min="5"
                    max="480"
                    value={data.slot_duration_minutes}
                    onChange={(e) => setData('slot_duration_minutes', Number(e.target.value))}
                    required
                />
            </div>
            <div>
                <label htmlFor="max_per_slot">Max per slot</label>
                <input
                    id="max_per_slot"
                    type="number"
                    min="1"
                    max="100"
                    value={data.max_per_slot}
                    onChange={(e) => setData('max_per_slot', Number(e.target.value))}
                    required
                />
            </div>
            <div>
                <label htmlFor="break_start">Break start (optional)</label>
                <input id="break_start" type="time" value={data.break_start} onChange={(e) => setData('break_start', e.target.value)} />
            </div>
            <div>
                <label htmlFor="break_end">Break end (optional)</label>
                <input id="break_end" type="time" value={data.break_end} onChange={(e) => setData('break_end', e.target.value)} />
            </div>
            {schedule && (
                <label>
                    <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />
                    Active
                </label>
            )}
            <button type="submit" disabled={processing}>Save</button>
        </form>
    );
}
