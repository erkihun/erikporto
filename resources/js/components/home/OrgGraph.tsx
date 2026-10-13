import { useEffect, useRef, useState } from 'react';

import { whenIdle } from '@/lib/idle';
import type { GraphMode, OrgGraphEngine } from '@/lib/org-graph-engine';

const MODE_COPY: Record<GraphMode, { label: string; caption: string }> = {
    system: { label: 'org.structure → 40 nodes', caption: '1 bureau · 3 dirs · 9 teams · 27 staff' },
    design: { label: 'identity.construct → grid', caption: 'circle · triangle · baseline grid' },
};

/**
 * Hero motion graphic. The stage and HUD are SSR'd so the layout is final at first
 * paint; the canvas engine is a separate chunk started when the browser is idle,
 * so it never delays rendering or LCP.
 */
export function OrgGraph() {
    const stageRef = useRef<HTMLDivElement>(null);
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const engineRef = useRef<OrgGraphEngine | null>(null);
    const modeRef = useRef<GraphMode>('system');
    const autoRef = useRef<number | null>(null);
    const [mode, setMode] = useState<GraphMode>('system');

    const select = (next: GraphMode, fromUser = false) => {
        if (fromUser && autoRef.current !== null) {
            window.clearInterval(autoRef.current);
            autoRef.current = null;
        }
        modeRef.current = next;
        setMode(next);
        engineRef.current?.setMode(next);
    };

    useEffect(() => {
        const stage = stageRef.current;
        const canvas = canvasRef.current;
        if (!stage || !canvas) return;

        let disposed = false;
        const cancelIdle = whenIdle(() => {
            void import('@/lib/org-graph-engine').then(({ startOrgGraph }) => {
                if (!disposed) engineRef.current = startOrgGraph(stage, canvas, modeRef.current);
            });
        });

        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            autoRef.current = window.setInterval(() => {
                if (!document.hidden) select(modeRef.current === 'system' ? 'design' : 'system');
            }, 7000);
        }

        return () => {
            disposed = true;
            cancelIdle();
            engineRef.current?.stop();
            engineRef.current = null;
            if (autoRef.current !== null) window.clearInterval(autoRef.current);
        };
    }, []);

    return (
        <div className="stage" ref={stageRef}>
            <canvas ref={canvasRef} aria-hidden="true" />
            <div className="hud top mono">
                <span>{MODE_COPY[mode].label}</span>
                <div className="mode" role="group" aria-label="Animation mode">
                    {(['system', 'design'] as const).map((m) => (
                        <button key={m} type="button" aria-pressed={mode === m} onClick={() => select(m, true)}>
                            {m === 'system' ? 'System' : 'Design'}
                        </button>
                    ))}
                </div>
            </div>
            <div className="hud bot mono">
                <span>{MODE_COPY[mode].caption}</span>
                <span>move cursor to interact</span>
            </div>
        </div>
    );
}
