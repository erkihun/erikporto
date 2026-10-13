import { useRef, type AnchorHTMLAttributes, type PointerEvent, type ReactNode } from 'react';

interface MagneticButtonProps extends AnchorHTMLAttributes<HTMLAnchorElement> {
    variant?: 'primary' | 'ghost';
    size?: 'md' | 'sm';
    children: ReactNode;
}

/** A link styled as a button that leans toward the cursor (mouse only, motion-safe via CSS). */
export function MagneticButton({ variant = 'primary', size = 'md', className = '', children, ...rest }: MagneticButtonProps) {
    const ref = useRef<HTMLAnchorElement>(null);

    const onMove = (e: PointerEvent<HTMLAnchorElement>) => {
        const el = ref.current;
        if (!el || e.pointerType !== 'mouse' || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const r = el.getBoundingClientRect();
        el.style.setProperty('--bx', `${(e.clientX - r.left - r.width / 2) * 0.25}px`);
        el.style.setProperty('--by', `${(e.clientY - r.top - r.height / 2) * 0.35}px`);
    };
    const onLeave = () => {
        ref.current?.style.setProperty('--bx', '0px');
        ref.current?.style.setProperty('--by', '0px');
    };

    return (
        <a
            ref={ref}
            className={`btn btn-${variant} ${size === 'sm' ? 'btn-sm' : ''} ${className}`.trim()}
            onPointerMove={onMove}
            onPointerLeave={onLeave}
            {...rest}
        >
            {children}
        </a>
    );
}
