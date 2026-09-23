import React from 'react';
import AsyncButton from '../../../Components/AsyncButton';

export default function PractitionerForm({ data, setData, errors, processing, onSubmit, departments, practitioner }) {
    return (
        <form onSubmit={onSubmit} className="space-y-4">
            <div>
                <label htmlFor="full_name" className="block text-xs font-semibold text-zinc-700 mb-1">
                    Full Name & Title
                </label>
                <input
                    id="full_name"
                    value={data.full_name}
                    onChange={(e) => setData('full_name', e.target.value)}
                    required
                    placeholder="e.g. Dr. Jane Doe"
                    className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                />
                {errors.full_name && <p className="text-xs text-rose-600 mt-1">{errors.full_name}</p>}
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label htmlFor="specialisation" className="block text-xs font-semibold text-zinc-700 mb-1">
                        Specialisation
                    </label>
                    <input
                        id="specialisation"
                        value={data.specialisation}
                        onChange={(e) => setData('specialisation', e.target.value)}
                        required
                        placeholder="e.g. Cardiology"
                        className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                    />
                    {errors.specialisation && <p className="text-xs text-rose-600 mt-1">{errors.specialisation}</p>}
                </div>

                <div>
                    <label htmlFor="availability" className="block text-xs font-semibold text-zinc-700 mb-1">
                        Availability Status
                    </label>
                    <select
                        id="availability"
                        value={data.availability}
                        onChange={(e) => setData('availability', e.target.value)}
                        className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                    >
                        <option value="active">Active</option>
                        <option value="on_leave">On Leave</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                    {errors.availability && <p className="text-xs text-rose-600 mt-1">{errors.availability}</p>}
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label htmlFor="qualifications" className="block text-xs font-semibold text-zinc-700 mb-1">
                        Qualifications
                    </label>
                    <input
                        id="qualifications"
                        value={data.qualifications}
                        onChange={(e) => setData('qualifications', e.target.value)}
                        placeholder="e.g. MBBS, FWACP"
                        className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                    />
                </div>

                <div>
                    <label htmlFor="internal_contact" className="block text-xs font-semibold text-zinc-700 mb-1">
                        Internal Contact (Extension / Phone)
                    </label>
                    <input
                        id="internal_contact"
                        value={data.internal_contact}
                        onChange={(e) => setData('internal_contact', e.target.value)}
                        placeholder="e.g. Ext 402 / +234..."
                        className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                    />
                </div>
            </div>

            <div>
                <label htmlFor="bio" className="block text-xs font-semibold text-zinc-700 mb-1">
                    Public Bio (visible to patients during booking)
                </label>
                <textarea
                    id="bio"
                    rows={3}
                    value={data.bio}
                    onChange={(e) => setData('bio', e.target.value)}
                    placeholder="Brief background and clinical focus..."
                    className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm resize-none"
                />
            </div>

            <div>
                <label className="block text-xs font-semibold text-zinc-700 mb-2">Assigned Departments</label>
                <div className="border border-zinc-200 rounded-md p-3 space-y-2 bg-zinc-50/50 max-h-48 overflow-y-auto">
                    {departments && departments.length > 0 ? (
                        departments.map((d) => (
                            <label key={d.id} className="flex items-center space-x-2 text-xs text-zinc-800 cursor-pointer">
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
                                    className="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"
                                />
                                <span className="font-medium">{d.name}</span>
                                {d.room_label && <span className="text-zinc-400">({d.room_label})</span>}
                            </label>
                        ))
                    ) : (
                        <p className="text-xs text-zinc-500">No departments available.</p>
                    )}
                </div>
                {errors.department_ids && <p className="text-xs text-rose-600 mt-1">{errors.department_ids}</p>}
            </div>

            <div className="pt-2">
                <AsyncButton
                    type="submit"
                    loading={processing}
                    loadingText={practitioner ? 'Saving changes...' : 'Creating practitioner...'}
                    variant="primary"
                    className="w-full bg-zinc-900 hover:bg-zinc-800 text-white font-semibold py-2 rounded-md text-xs shadow-sm"
                >
                    {practitioner ? 'Update Practitioner' : 'Create Practitioner'}
                </AsyncButton>
            </div>
        </form>
    );
}
