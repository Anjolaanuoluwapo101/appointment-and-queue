import AsyncButton from '../../../Components/AsyncButton';

export default function StaffForm({ data, setData, errors, processing, onSubmit, practitioners = [], isCreate }) {
    const inputClass =
        'w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm';

    return (
        <form onSubmit={onSubmit} className="space-y-4">
            <div>
                <label htmlFor="name" className="block text-xs font-semibold text-zinc-700 mb-1">
                    Name
                </label>
                <input
                    id="name"
                    className={inputClass}
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    required
                />
                {errors.name && <p className="text-xs text-rose-600 mt-1">{errors.name}</p>}
            </div>

            <div>
                <label htmlFor="email" className="block text-xs font-semibold text-zinc-700 mb-1">
                    Email
                </label>
                <input
                    type="email"
                    id="email"
                    className={inputClass}
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    required
                />
                {errors.email && <p className="text-xs text-rose-600 mt-1">{errors.email}</p>}
            </div>

            {isCreate && (
                <div>
                    <label htmlFor="password" className="block text-xs font-semibold text-zinc-700 mb-1">
                        Password
                    </label>
                    <input
                        type="password"
                        id="password"
                        className={inputClass}
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        required={isCreate}
                    />
                    {errors.password && <p className="text-xs text-rose-600 mt-1">{errors.password}</p>}
                </div>
            )}

            <div>
                <label htmlFor="role" className="block text-xs font-semibold text-zinc-700 mb-1">
                    Role
                </label>
                <select
                    id="role"
                    className={inputClass}
                    value={data.role}
                    onChange={(e) => setData('role', e.target.value)}
                >
                    <option value="receptionist">Receptionist</option>
                    <option value="practitioner">Practitioner</option>
                    <option value="admin">Admin</option>
                </select>
                {errors.role && <p className="text-xs text-rose-600 mt-1">{errors.role}</p>}
            </div>

            {isCreate && data.role === 'practitioner' && (
                <div>
                    <label htmlFor="practitioner_id" className="block text-xs font-semibold text-zinc-700 mb-1">
                        Link to Practitioner (optional)
                    </label>
                    <select
                        id="practitioner_id"
                        className={inputClass}
                        value={data.practitioner_id}
                        onChange={(e) => setData('practitioner_id', e.target.value)}
                    >
                        <option value="">-- None --</option>
                        {practitioners.map((p) => (
                            <option key={p.id} value={p.id}>{p.full_name}</option>
                        ))}
                    </select>
                    {errors.practitioner_id && <p className="text-xs text-rose-600 mt-1">{errors.practitioner_id}</p>}
                </div>
            )}

            <div className="pt-3">
                <AsyncButton
                    type="submit"
                    loading={processing}
                    loadingText="Saving account..."
                    variant="primary"
                    className="w-full bg-zinc-900 hover:bg-zinc-800 text-white font-semibold py-2 rounded text-xs shadow-sm"
                >
                    Save Account
                </AsyncButton>
            </div>
        </form>
    );
}
