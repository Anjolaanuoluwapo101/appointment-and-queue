import { Link, useForm, usePage } from '@inertiajs/react';
import AsyncButton from '../../../Components/AsyncButton';
import { Badge } from '../../../Components/Badge';
import { Card, CardContent, CardHeader, CardTitle } from '../../../Components/Card';
import AppLayout from '../../../Layouts/AppLayout';

export default function Show() {
    const { appointment } = usePage().props;
    const cancel = useForm({ reason: '' });
    const action = useForm({ slot_id: '' });
    const clear = useForm({ receipt_no: '', method: 'cash' });

    return (
        <AppLayout>
            <div className="space-y-6 max-w-3xl mx-auto bg-white py-6">
                <div className="flex items-center justify-between border-b border-zinc-100 pb-4">
                    <div>
                        <span className="text-xs font-mono text-zinc-400 block uppercase">Staff Appointment Management</span>
                        <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Appointment #{appointment.id}</h1>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Badge variant="outline" className="uppercase font-mono">{appointment.status}</Badge>
                        <Badge variant={appointment.payment_status === 'paid' ? 'success' : 'amber'}>
                            {appointment.payment_status}
                        </Badge>
                    </div>
                </div>

                <Card className="bg-white border border-zinc-200">
                    <CardHeader>
                        <CardTitle className="text-sm">Patient & Schedule Summary</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 text-xs text-zinc-700">
                        <div className="flex justify-between border-b border-zinc-100 pb-2">
                            <span className="text-zinc-500">Hospital / Folder No.</span>
                            <span className="font-mono font-semibold text-zinc-900">{appointment.patient?.patient_number || `PAT-#${appointment.patient?.id}`}</span>
                        </div>
                        <div className="flex justify-between border-b border-zinc-100 pb-2">
                            <span className="text-zinc-500">Patient Name</span>
                            <span className="font-semibold text-zinc-900">{appointment.patient.full_name} ({appointment.patient.phone})</span>
                        </div>
                        <div className="flex justify-between border-b border-zinc-100 pb-2">
                            <span className="text-zinc-500">Department</span>
                            <span className="font-semibold text-zinc-900">{appointment.department.name}</span>
                        </div>
                        <div className="flex justify-between border-b border-zinc-100 pb-2">
                            <span className="text-zinc-500">Practitioner</span>
                            <span className="font-semibold text-zinc-900">{appointment.practitioner?.full_name ?? 'Unassigned'}</span>
                        </div>
                        <div className="flex justify-between border-b border-zinc-100 pb-2">
                            <span className="text-zinc-500">Scheduled Time</span>
                            <span className="font-mono text-zinc-900">{appointment.scheduled_at.slice(0, 16).replace('T', ' ')}</span>
                        </div>
                        <div className="flex justify-between border-b border-zinc-100 pb-2">
                            <span className="text-zinc-500">Payment Status</span>
                            <span className="font-mono text-zinc-900">{appointment.payment_status} ({appointment.payment_mode})</span>
                        </div>
                    </CardContent>
                </Card>

                {/* Clear Payment Form if Pending */}
                {appointment.status === 'pending_clearance' && (
                    <Card className="bg-white border border-zinc-200">
                        <CardHeader>
                            <CardTitle className="text-sm text-amber-800">Clear Onsite Payment</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    clear.post(`/staff/appointments/${appointment.id}/clear`);
                                }}
                                className="space-y-3"
                            >
                                <div>
                                    <label htmlFor="receipt_no" className="block text-xs font-semibold text-zinc-700 mb-1">Receipt No</label>
                                    <input
                                        id="receipt_no"
                                        value={clear.data.receipt_no}
                                        onChange={(e) => clear.setData('receipt_no', e.target.value)}
                                        required
                                        className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                    />
                                </div>
                                <div>
                                    <label htmlFor="method" className="block text-xs font-semibold text-zinc-700 mb-1">Method</label>
                                    <select
                                        id="method"
                                        value={clear.data.method}
                                        onChange={(e) => clear.setData('method', e.target.value)}
                                        className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                    >
                                        <option value="cash">Cash</option>
                                        <option value="transfer">Bank Transfer</option>
                                        <option value="pos">POS Terminal</option>
                                    </select>
                                </div>
                                <AsyncButton
                                    type="submit"
                                    loading={clear.processing}
                                    loadingText="Clearing payment..."
                                    variant="primary"
                                    className="bg-zinc-900 text-white text-xs font-semibold px-4 py-2 rounded shadow-sm"
                                >
                                    Clear Payment & Queue Ticket
                                </AsyncButton>
                            </form>
                        </CardContent>
                    </Card>
                )}

                {/* Actions: Reschedule, Cancel, No-Show */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Card className="bg-white border border-zinc-200">
                        <CardHeader>
                            <CardTitle className="text-sm">Reschedule Slot</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    action.post(`/staff/appointments/${appointment.id}/reschedule`);
                                }}
                                className="space-y-3"
                            >
                                <div>
                                    <label htmlFor="slot_id" className="block text-xs font-semibold text-zinc-700 mb-1">Target Slot ID</label>
                                    <input
                                        id="slot_id"
                                        type="number"
                                        value={action.data.slot_id}
                                        onChange={(e) => action.setData('slot_id', Number(e.target.value))}
                                        required
                                        className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                    />
                                </div>
                                <AsyncButton
                                    type="submit"
                                    loading={action.processing}
                                    loadingText="Rescheduling..."
                                    variant="outline"
                                    className="w-full text-xs font-semibold py-2 rounded"
                                >
                                    Reschedule Appointment
                                </AsyncButton>
                            </form>
                        </CardContent>
                    </Card>

                    <Card className="bg-white border border-zinc-200">
                        <CardHeader>
                            <CardTitle className="text-sm text-rose-700">Cancel / No-Show</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    cancel.post(`/staff/appointments/${appointment.id}/cancel`);
                                }}
                                className="space-y-3"
                            >
                                <div>
                                    <label htmlFor="reason" className="block text-xs font-semibold text-zinc-700 mb-1">Cancel Reason</label>
                                    <input
                                        id="reason"
                                        value={cancel.data.reason}
                                        onChange={(e) => cancel.setData('reason', e.target.value)}
                                        className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                    />
                                </div>
                                <div className="flex space-x-2">
                                    <AsyncButton
                                        type="submit"
                                        loading={cancel.processing}
                                        loadingText="Cancelling..."
                                        variant="destructive"
                                        className="bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold px-3 py-2 rounded flex-1"
                                    >
                                        Cancel
                                    </AsyncButton>
                                    <AsyncButton
                                        type="button"
                                        onClick={() => action.post(`/staff/appointments/${appointment.id}/no-show`)}
                                        variant="outline"
                                        className="text-xs font-semibold px-3 py-2 rounded flex-1"
                                    >
                                        Mark No-Show
                                    </AsyncButton>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <div className="pt-2">
                    <Link href="/staff/appointments" className="text-xs font-medium text-zinc-600 hover:text-zinc-900">
                        ← Back to all staff appointments
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
