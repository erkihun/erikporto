import { useEffect, useState } from 'react';

export function Toast({ message, onDone }: { message: string | null; onDone?: () => void }) {
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        if (!message) return;
        setVisible(true);
        const t = window.setTimeout(() => {
            setVisible(false);
            onDone?.();
        }, 3200);
        return () => window.clearTimeout(t);
    }, [message, onDone]);

    return (
        <div className={`toast ${visible ? 'on' : ''}`} role="status" aria-live="polite">
            {message}
        </div>
    );
}
