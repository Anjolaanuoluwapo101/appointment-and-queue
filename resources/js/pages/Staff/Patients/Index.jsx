import React, { useEffect, useRef, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../Components/Table';
import { Badge } from '../../../Components/Badge';

export default function Index() {
    const { patients, filters = {} } = usePage().props;

    const [search, setSearch] = useState(filters.search || '');
    const [gender, setGender] = useState(filters.gender || 'all');
    const [sort, setSort] = useState(filters.sort || 'full_name');
    const [isSearching, setIsSearching] = useState(false);

    const isInitialRender = useRef(true);

    useEffect(() => {
        if (isInitialRender.current) {
            isInitialRender.current = false;
            return undefined;
        }

        setIsSearching(true);
        const timer = setTimeout(() => {
            router.get(
                '/staff/patients',
                { search, gender, sort },
                {
                    preserveState: true,
                    preserveScroll: true,
                    only: ['patients', 'filters'],
                    onFinish: () => setIsSearching(false),
                }
            );
        }, 300);

        return () => clearTimeout(timer);
    }, [search, gender, sort]);

    const handleClearSearch = () => {
        setSearch('');
        setGender('all');
        setSort('full_name');
    };

    const patientList = patients?.data ?? (Array.isArray(patients) ? patients : []);
    const totalCount = patients?.total ?? patientList.length;
    const isFiltered = !!search || (gender && gender !== 'all') || (sort && sort !== 'full_name');

    return (
        <AppLayout activeRoute="staff.patients">
            <div className="space-y-6 max-w-6xl mx-auto bg-white py-4 px-2 sm:px-4">
                {/* Header Strip */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-100 pb-4">
                    <div>
                        <div className="flex items-center space-x-2">
                            <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Patient Directory</h1>
                            <Badge variant="outline" className="font-mono text-xs">{totalCount} Registered</Badge>
                        </div>
                        <p className="text-xs text-zinc-500 mt-1">Search, filter, and access complete electronic patient folders.</p>
                    </div>
                    <Link
                        href="/staff/patients/create"
                        className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-4 py-2 rounded text-xs transition-colors shadow-sm self-start sm:self-auto"
                    >
                        + Register New Patient
                    </Link>
                </div>

                {/* Filter & Async Search Bar */}
                <div className="p-4 bg-zinc-50/70 border border-zinc-200 rounded-lg space-y-3">
                    <div className="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                        {/* Search Input with Spinner */}
                        <div className="sm:col-span-6 relative">
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search by name, hospital number, phone, email..."
                                className="w-full px-3 py-2 text-xs border border-zinc-300 rounded bg-white text-zinc-900 placeholder-zinc-400 focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900"
                            />
                            {isSearching ? (
                                <span className="absolute inset-y-0 right-0 pr-3 flex items-center text-[10px] font-semibold text-emerald-700 font-mono animate-pulse">
                                    Searching...
                                </span>
                            ) : search ? (
                                <button
                                    type="button"
                                    onClick={() => setSearch('')}
                                    className="absolute inset-y-0 right-0 pr-3 flex items-center text-xs font-bold text-zinc-400 hover:text-zinc-700"
                                >
                                    Clear
                                </button>
                            ) : null}
                        </div>

                        {/* Gender Filter */}
                        <div className="sm:col-span-3">
                            <select
                                value={gender}
                                onChange={(e) => setGender(e.target.value)}
                                className="w-full py-2 px-3 text-xs border border-zinc-300 rounded bg-white text-zinc-800 focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900"
                            >
                                <option value="all">All Genders</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        {/* Sort Order */}
                        <div className="sm:col-span-3">
                            <select
                                value={sort}
                                onChange={(e) => setSort(e.target.value)}
                                className="w-full py-2 px-3 text-xs border border-zinc-300 rounded bg-white text-zinc-800 focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900"
                            >
                                <option value="full_name">Sort by Name (A-Z)</option>
                                <option value="created_at">Sort by Newest</option>
                                <option value="patient_number">Sort by Hospital No.</option>
                            </select>
                        </div>
                    </div>

                    {isFiltered && (
                        <div className="flex items-center justify-between text-xs pt-1 border-t border-zinc-200/60">
                            <span className="text-zinc-500 font-mono text-[11px]">
                                Active Filters: {search && `Search: "${search}"`} {gender !== 'all' && `Gender: ${gender}`} {sort !== 'full_name' && `Sort: ${sort}`}
                            </span>
                            <button
                                type="button"
                                onClick={handleClearSearch}
                                className="text-zinc-700 hover:text-zinc-900 font-semibold underline text-[11px]"
                            >
                                Clear All Filters
                            </button>
                        </div>
                    )}
                </div>

                {/* Patient Table with Loading State */}
                <div className={`transition-opacity duration-150 ${isSearching ? 'opacity-50 pointer-events-none' : 'opacity-100'}`}>
                    {patientList.length === 0 ? (
                        <div className="p-8 text-center bg-white border border-zinc-200 rounded-lg space-y-2">
                            <p className="text-xs font-semibold text-zinc-600">No patients matched your query.</p>
                            <p className="text-xs text-zinc-400">Try adjusting your search keyword or gender filter.</p>
                            {isFiltered && (
                                <button
                                    type="button"
                                    onClick={handleClearSearch}
                                    className="mt-2 inline-block text-xs font-semibold text-zinc-900 underline"
                                >
                                    Reset Search Filters
                                </button>
                            )}
                        </div>
                    ) : (
                        <div className="border border-zinc-200 rounded-lg overflow-hidden shadow-sm bg-white">
                            <Table>
                                <TableHeader className="bg-zinc-50 border-b border-zinc-200">
                                    <TableRow>
                                        <TableHead className="py-2.5 text-xs font-bold text-zinc-700">Hospital No.</TableHead>
                                        <TableHead className="py-2.5 text-xs font-bold text-zinc-700">Patient Name</TableHead>
                                        <TableHead className="py-2.5 text-xs font-bold text-zinc-700">Phone Number</TableHead>
                                        <TableHead className="py-2.5 text-xs font-bold text-zinc-700">Email Address</TableHead>
                                        <TableHead className="py-2.5 text-xs font-bold text-zinc-700">Gender</TableHead>
                                        <TableHead className="py-2.5 text-xs font-bold text-zinc-700 text-right">Action</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody className="divide-y divide-zinc-100">
                                    {patientList.map((p) => (
                                        <TableRow key={p.id} className="hover:bg-zinc-50/60 transition-colors">
                                            <TableCell className="font-mono text-xs font-bold text-zinc-900">
                                                {p.patient_number || `PAT-#${p.id}`}
                                            </TableCell>
                                            <TableCell className="font-semibold text-xs text-zinc-900">
                                                <Link href={`/staff/patients/${p.id}`} className="hover:text-blue-600 hover:underline">
                                                    {p.full_name}
                                                </Link>
                                            </TableCell>
                                            <TableCell className="font-mono text-xs text-zinc-600">{p.phone}</TableCell>
                                            <TableCell className="text-xs text-zinc-500">{p.email || '—'}</TableCell>
                                            <TableCell className="text-xs capitalize text-zinc-600">
                                                {p.gender ? (
                                                    <Badge variant="outline" className="text-[10px] uppercase font-mono">
                                                        {p.gender}
                                                    </Badge>
                                                ) : '—'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Link
                                                    href={`/staff/patients/${p.id}`}
                                                    className="inline-flex items-center text-xs font-semibold text-zinc-900 hover:text-blue-600 underline"
                                                >
                                                    View File →
                                                </Link>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </div>

                {/* Pagination Controls */}
                {patients?.links && patients.links.length > 3 && (
                    <div className="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
                        <span className="text-xs text-zinc-500 font-mono">
                            Showing {patients.from || 0} to {patients.to || 0} of {patients.total || 0} patients
                        </span>
                        <div className="flex items-center space-x-1">
                            {patients.links.map((link, idx) => {
                                if (!link.url) {
                                    return (
                                        <span
                                            key={idx}
                                            className="px-2.5 py-1 text-xs text-zinc-300 border border-zinc-200 rounded cursor-not-allowed"
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    );
                                }
                                return (
                                    <Link
                                        key={idx}
                                        href={link.url}
                                        preserveState
                                        preserveScroll
                                        className={`px-2.5 py-1 text-xs rounded border font-medium transition-colors ${
                                            link.active
                                                ? 'bg-zinc-900 text-white border-zinc-900'
                                                : 'bg-white text-zinc-700 border-zinc-200 hover:bg-zinc-50'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                );
                            })}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
