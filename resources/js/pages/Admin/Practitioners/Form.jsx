export default function PractitionerForm({ data, setData, errors, processing, onSubmit, departments, practitioner }) {
    return (
        <form onSubmit={onSubmit}>
            <div>
                <label htmlFor="full_name">Full name</label>
                <input id="full_name" value={data.full_name} onChange={(e) => setData('full_name', e.target.value)} required />
                {errors.full_name && <p>{errors.full_name}</p>}
            </div>
            <div>
                <label htmlFor="specialisation">Specialisation</label>
                <input
                    id="specialisation"
                    value={data.specialisation}
                    onChange={(e) => setData('specialisation', e.target.value)}
                    required
                />
                {errors.specialisation && <p>{errors.specialisation}</p>}
            </div>
            <div>
                <label htmlFor="qualifications">Qualifications</label>
                <input
                    id="qualifications"
                    value={data.qualifications}
                    onChange={(e) => setData('qualifications', e.target.value)}
                />
            </div>
            <div>
                <label htmlFor="bio">Bio (visible to patients)</label>
                <textarea id="bio" value={data.bio} onChange={(e) => setData('bio', e.target.value)} />
            </div>
            <div>
                <label htmlFor="internal_contact">Internal contact</label>
                <input
                    id="internal_contact"
                    value={data.internal_contact}
                    onChange={(e) => setData('internal_contact', e.target.value)}
                />
            </div>
            <div>
                <label htmlFor="availability">Availability</label>
                <select id="availability" value={data.availability} onChange={(e) => setData('availability', e.target.value)}>
                    <option value="active">Active</option>
                    <option value="on_leave">On Leave</option>
                    <option value="unavailable">Unavailable</option>
                </select>
                {errors.availability && <p>{errors.availability}</p>}
            </div>
            <fieldset>
                <legend>Departments</legend>
                {departments.map((d) => (
                    <label key={d.id}>
                        <input
                            type="checkbox"
                            checked={data.department_ids.includes(d.id)}
                            onChange={(e) => {
                                setData(
                                    'department_ids',
                                    e.target.checked
                                        ? [...data.department_ids, d.id]
                                        : data.department_ids.filter((id) => id !== d.id),
                                );
                            }}
                        />
                        {d.name}
                    </label>
                ))}
                {errors.department_ids && <p>{errors.department_ids}</p>}
            </fieldset>
            <button type="submit" disabled={processing}>Save</button>
        </form>
    );
}
