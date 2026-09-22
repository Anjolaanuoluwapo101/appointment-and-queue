import React, { useState, useEffect } from 'react';

export default function NetworkStatusIndicator() {
    const [isOnline, setIsOnline] = useState(
        typeof navigator !== 'undefined' ? navigator.onLine : true
    );
    const [wasOffline, setWasOffline] = useState(false);

    useEffect(() => {
        const handleOnline = () => {
            setIsOnline(true);
            setWasOffline(true);
            const timer = setTimeout(() => setWasOffline(false), 4000);
            return () => clearTimeout(timer);
        };

        const handleOffline = () => {
            setIsOnline(false);
        };

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    if (isOnline && !wasOffline) {
        return null;
    }

    return (
        <div
            role="status"
            aria-live="polite"
            className={`fixed top-0 left-0 right-0 z-[9999] px-4 py-2.5 text-center text-sm font-semibold transition-all duration-300 shadow-md ${
                !isOnline
                    ? 'bg-amber-600 text-white animate-pulse'
                    : 'bg-emerald-600 text-white'
            }`}
        >
            {!isOnline ? (
                <div className="flex items-center justify-center gap-2">
                    <svg className="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span><strong>Connection Lost:</strong> You are currently offline. Queue actions will resume when connectivity is restored.</span>
                </div>
            ) : (
                <div className="flex items-center justify-center gap-2">
                    <svg className="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span><strong>Connection Restored:</strong> You are back online.</span>
                </div>
            )}
        </div>
    );
}
