import React from 'react';
import { Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '../Components/Card';
import AppLayout from '../Layouts/AppLayout';
import { Badge } from '../Components/Badge';

export default function Home() {
    return (
        <AppLayout>
            <div className="py-10 space-y-12 max-w-5xl mx-auto bg-white px-4">
                {/* Hero Header Strip */}
                <div className="text-center space-y-4">
                    <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-zinc-100 text-zinc-800 border border-zinc-200">
                        Intelligent Hospital Queue & Appointment System
                    </span>
                    <h1 className="text-3xl sm:text-5xl font-extrabold text-zinc-900 tracking-tight">
                        Streamlined Patient Care & Clinical Queue Management
                    </h1>
                    <p className="text-zinc-500 text-sm max-w-2xl mx-auto leading-relaxed">
                        Automated patient scheduling, real-time consultation queue tracking, and instant desk clearance for modern healthcare services.
                    </p>
                </div>

                {/* Main Access Portals */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    {/* Patient Portal */}
                    <Card className="bg-white border border-zinc-200 hover:border-zinc-400 transition-all shadow-sm">
                        <CardHeader className="pb-3 border-b border-zinc-100">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <CardTitle className="text-lg font-bold text-zinc-900">Patient Portal</CardTitle>
                                <Badge variant="outline" className="font-mono text-[10px] uppercase tracking-wider self-start sm:self-auto px-2.5 py-1">
                                    Self-Service
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent className="pt-5 space-y-5">
                            <p className="text-xs text-zinc-600 leading-relaxed">
                                Schedule appointment slots, track your live queue ticket status from your phone, and manage your medical visit profile.
                            </p>
                            <div className="flex flex-wrap items-center gap-3 pt-1">
                                <Link
                                    href="/login/patient"
                                    className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-4 py-2 rounded text-xs transition-colors shadow-sm"
                                >
                                    Patient Sign In
                                </Link>
                                <Link
                                    href="/register"
                                    className="bg-white hover:bg-zinc-50 text-zinc-700 font-medium px-4 py-2 rounded text-xs border border-zinc-200 transition-colors"
                                >
                                    Register Account
                                </Link>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Staff Portal */}
                    <Card className="bg-white border border-zinc-200 hover:border-zinc-400 transition-all shadow-sm">
                        <CardHeader className="pb-3 border-b border-zinc-100">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <CardTitle className="text-lg font-bold text-zinc-900">Staff & Clinical Operations</CardTitle>
                                <Badge variant="secondary" className="font-mono text-[10px] uppercase tracking-wider self-start sm:self-auto px-2.5 py-1">
                                    Restricted Access
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent className="pt-5 space-y-5">
                            <p className="text-xs text-zinc-600 leading-relaxed">
                                Operational desk for receptionists, doctors, specialists, and hospital administration. Call patients, manage walk-ins, and view electronic files.
                            </p>
                            <div className="pt-1">
                                <Link
                                    href="/login/staff"
                                    className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-4 py-2 rounded text-xs transition-colors shadow-sm inline-block"
                                >
                                    Staff & Doctor Login
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* System Capabilities Grid */}
                <div className="space-y-4 pt-4 border-t border-zinc-100">
                    <h2 className="text-xs font-bold text-zinc-500 uppercase tracking-wider text-center">
                        Core Platform Capabilities
                    </h2>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div className="p-4 rounded-lg border border-zinc-200 bg-white space-y-1.5">
                            <h3 className="text-xs font-bold text-zinc-900">Online Booking</h3>
                            <p className="text-[11px] text-zinc-500">Select department, specialist practitioner, and preferred time slot with real-time capacity locks.</p>
                        </div>
                        <div className="p-4 rounded-lg border border-zinc-200 bg-white space-y-1.5">
                            <h3 className="text-xs font-bold text-zinc-900">Live Queue Tracking</h3>
                            <p className="text-[11px] text-zinc-500">Monitor queue number progress live and receive progressive notifications when your turn approaches.</p>
                        </div>
                        <div className="p-4 rounded-lg border border-zinc-200 bg-white space-y-1.5">
                            <h3 className="text-xs font-bold text-zinc-900">Payment & Desk Clearance</h3>
                            <p className="text-[11px] text-zinc-500">Secure online consultation payments or instant physical cash and POS desk clearance.</p>
                        </div>
                        <div className="p-4 rounded-lg border border-zinc-200 bg-white space-y-1.5">
                            <h3 className="text-xs font-bold text-zinc-900">Electronic Patient Records</h3>
                            <p className="text-[11px] text-zinc-500">Centralized patient directory, visit history, and instant clinical folder access for staff.</p>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
