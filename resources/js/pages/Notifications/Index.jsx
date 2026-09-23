import { Link, useForm, usePage } from '@inertiajs/react';
import AsyncButton from '../../Components/AsyncButton';
import { Badge } from '../../Components/Badge';
import { Card } from '../../Components/Card';
import AppLayout from '../../Layouts/AppLayout';

export default function Index() {
    const { notifications, unread_count } = usePage().props;
    const read = useForm({});

    return (
        <AppLayout>
            <div className="space-y-6 max-w-4xl mx-auto bg-white py-6">
                <div className="flex items-center justify-between border-b border-zinc-100 pb-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Hospital Notifications</h1>
                        <p className="text-xs text-zinc-500 mt-1">Real-time alerts, queue status updates, and clearance notices</p>
                    </div>

                    {unread_count > 0 && (
                        <AsyncButton
                            type="button"
                            loading={read.processing}
                            loadingText="Marking read..."
                            onClick={() => read.post('/notifications/read-all')}
                            variant="outline"
                            className="text-xs font-semibold px-4 py-2 rounded"
                        >
                            Mark All as Read ({unread_count})
                        </AsyncButton>
                    )}
                </div>

                {notifications.length === 0 ? (
                    <Card className="bg-white border border-zinc-200 text-center py-8">
                        <p className="text-xs text-zinc-500">You have no notification logs at this time.</p>
                    </Card>
                ) : (
                    <div className="space-y-3">
                        {notifications.map((n) => (
                            <Card
                                key={n.id}
                                className={`bg-white border transition-all ${
                                    !n.read_at ? 'border-zinc-400 bg-zinc-50/50' : 'border-zinc-200'
                                }`}
                            >
                                <div className="flex items-start justify-between gap-4">
                                    <div className="space-y-1">
                                        <div className="flex items-center space-x-2">
                                            <span className="font-bold text-zinc-900 text-sm">{n.title}</span>
                                            {!n.read_at && <Badge variant="amber">New</Badge>}
                                        </div>
                                        <p className="text-xs text-zinc-600">{n.body}</p>
                                        <span className="text-[10px] font-mono text-zinc-400 block pt-1">
                                            {n.created_at?.slice(0, 16).replace('T', ' ')}
                                        </span>
                                    </div>

                                    <div className="flex items-center space-x-2 shrink-0">
                                        {n.link && (
                                            <Link href={n.link} className="text-xs font-semibold text-zinc-900 underline">
                                                View Action →
                                            </Link>
                                        )}
                                        {!n.read_at && (
                                            <AsyncButton
                                                type="button"
                                                loading={read.processing}
                                                loadingText="Updating..."
                                                onClick={() => read.post(`/notifications/${n.id}/read`)}
                                                variant="outline"
                                                className="text-[11px] px-2 py-1 rounded"
                                            >
                                                Mark Read
                                            </AsyncButton>
                                        )}
                                    </div>
                                </div>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
