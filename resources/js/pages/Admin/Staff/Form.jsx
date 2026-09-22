export default function StaffForm({ data, setData, errors, processing, onSubmit, practitioners = [], isCreate }) {
    return (
        <form onSubmit={onSubmit} className="space-y-4">
            <div>
                <label htmlFor="name" className="block font-medium">Name</label>
                <input
                    id="name"
                    className="w-full border p-2 rounded"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    required
                />
                {errors.name && <p className="text-red-500 text-sm">{errors.name}</p>}
            </div>
            <div>
                <label htmlFor="email" className="block font-medium">Email</label>
                <input
                    type="email"
                    id="email"
                    className="w-full border p-2 rounded"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    required
                />
                {errors.email && <p className="text-red-500 text-sm">{errors.email}</p>}
            </div>
            
            {isCreate && (
                <div>
                    <label htmlFor="password" className="block font-medium">Password</label>
                    <input
                        type="password"
                        id="password"
                        className="w-full border p-2 rounded"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        required={isCreate}
                    />
                    {errors.password && <p className="text-red-500 text-sm">{errors.password}</p>}
                </div>
            )}

            <div>
                <label htmlFor="role" className="block font-medium">Role</label>
                <select
                    id="role"
                    className="w-full border p-2 rounded"
                    value={data.role}
                    onChange={(e) => setData('role', e.target.value)}
                >
                    <option value="receptionist">Receptionist</option>
                    <option value="practitioner">Practitioner</option>
                    <option value="admin">Admin</option>
                </select>
                {errors.role && <p className="text-red-500 text-sm">{errors.role}</p>}
            </div>

            {isCreate && data.role === 'practitioner' && (
                <div>
                    <label htmlFor="practitioner_id" className="block font-medium">Link to Practitioner (optional)</label>
                    <select
                        id="practitioner_id"
                        className="w-full border p-2 rounded"
                        value={data.practitioner_id}
                        onChange={(e) => setData('practitioner_id', e.target.value)}
                    >
                        <option value="">-- None --</option>
                        {practitioners.map((p) => (
                            <option key={p.id} value={p.id}>{p.full_name}</option>
                        ))}
                    </select>
                    {errors.practitioner_id && <p className="text-red-500 text-sm">{errors.practitioner_id}</p>}
                </div>
            )}

            <button type="submit" disabled={processing} className="bg-blue-600 text-white px-4 py-2 rounded">
                Save
            </button>
        </form>
    );
}
