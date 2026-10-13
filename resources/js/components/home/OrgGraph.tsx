import { useEffect, useRef, useState } from 'react';

type Mode = 'system' | 'design';

interface Node {
    lvl: 0 | 1 | 2 | 3;
    x: number;
    y: number;
    tx: number;
    ty: number;
    vx: number;
    vy: number;
    ph: number;
}

const MODE_COPY: Record<Mode, { label: string; caption: string }> = {
    system: { label: 'org.structure → 40 nodes', caption: '1 bureau · 3 dirs · 9 teams · 27 staff' },
    design: { label: 'identity.construct → grid', caption: 'circle · triangle · baseline grid' },
};

/** Builds an org tree: 1 root → 3 directorates → 9 teams → 27 staff. */
function buildTree(): { nodes: Node[]; edges: [number, number][] } {
    const nodes: Node[] = [];
    const edges: [number, number][] = [];
    const add = (lvl: Node['lvl'], parent: number | null) => {
        nodes.push({ lvl, x: 0, y: 0, tx: 0, ty: 0, vx: 0, vy: 0, ph: Math.random() * 6.28 });
        const id = nodes.length - 1;
        if (parent !== null) edges.push([parent, id]);
        return id;
    };
    const root = add(0, null);
    for (let a = 0; a < 3; a++) {
        const d = add(1, root);
        for (let b = 0; b < 3; b++) {
            const t = add(2, d);
            for (let c = 0; c < 3; c++) add(3, t);
        }
    }
    return { nodes, edges };
}

/**
 * Hero motion graphic. One canvas, two states that the same 40 nodes morph between:
 * an institution's org chart (the software work) and a logo construction grid (the design work).
 */
export function OrgGraph() {
    const stageRef = useRef<HTMLDivElement>(null);
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const modeRef = useRef<Mode>('system');
    const layoutRef = useRef<() => void>(() => {});
    const t0Ref = useRef(0);
    const autoRef = useRef<number | null>(null);
    const [mode, setMode] = useState<Mode>('system');

    const select = (next: Mode, fromUser = false) => {
        if (fromUser && autoRef.current !== null) {
            window.clearInterval(autoRef.current);
            autoRef.current = null;
        }
        modeRef.current = next;
        setMode(next);
        t0Ref.current = performance.now();
        layoutRef.current();
    };

    useEffect(() => {
        const stage = stageRef.current;
        const canvas = canvasRef.current;
        const ctx = canvas?.getContext('2d');
        if (!stage || !canvas || !ctx) return;

        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const { nodes, edges } = buildTree();
        const mouse = { x: -999, y: -999 };
        let W = 0;
        let H = 0;
        let raf = 0;
        let visible = true;
        let col = { ink: '', mut: '', line: '', acc: '', sig: '', ok: '' };

        const readColours = () => {
            const css = getComputedStyle(document.documentElement);
            const v = (n: string) => css.getPropertyValue(n).trim();
            col = { ink: v('--ink'), mut: v('--muted'), line: v('--line'), acc: v('--accent'), sig: v('--signal'), ok: v('--ok') };
        };

        const layout = () => {
            const pad = W * 0.1;
            if (modeRef.current === 'system') {
                const levels: number[][] = [[], [], [], []];
                nodes.forEach((n, i) => levels[n.lvl]!.push(i));
                levels.forEach((ids, l) =>
                    ids.forEach((id, k) => {
                        const n = nodes[id]!;
                        n.tx = pad + (W - 2 * pad) * ((k + 0.5) / ids.length);
                        n.ty = H * 0.17 + l * ((H * 0.66) / 3);
                    }),
                );
                return;
            }
            const cx = W / 2;
            const cy = H / 2;
            nodes.forEach((n, i) => {
                if (n.lvl === 0) {
                    n.tx = cx;
                    n.ty = cy;
                } else if (n.lvl === 1 || n.lvl === 2) {
                    const ring = n.lvl === 1 ? W * 0.16 : W * 0.3;
                    const peers = nodes.map((m, j) => (m.lvl === n.lvl ? j : -1)).filter((j) => j >= 0);
                    const a = -Math.PI / 2 + (peers.indexOf(i) * 2 * Math.PI) / peers.length;
                    n.tx = cx + Math.cos(a) * ring;
                    n.ty = cy + Math.sin(a) * ring;
                } else {
                    const k = i % 27;
                    n.tx = pad + (k % 9) * ((W - 2 * pad) / 8);
                    n.ty = H * 0.86 + (Math.floor(k / 9) - 1) * 14;
                }
            });
        };
        layoutRef.current = layout;

        const resize = () => {
            const r = stage.getBoundingClientRect();
            const dpr = Math.min(window.devicePixelRatio || 1, 2);
            W = r.width;
            H = r.height;
            canvas.width = W * dpr;
            canvas.height = H * dpr;
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            layout();
            nodes.forEach((n) => {
                if (!n.x) {
                    n.x = W / 2;
                    n.y = H / 2;
                }
            });
        };

        const sizes = [9, 6.5, 4.5, 3];
        const frame = (now: number) => {
            raf = requestAnimationFrame(frame);
            if (!visible) return;
            const isSystem = modeRef.current === 'system';
            const t = (now - t0Ref.current) / 1000;
            ctx.clearRect(0, 0, W, H);

            ctx.strokeStyle = col.line;
            ctx.lineWidth = 1;
            ctx.globalAlpha = 0.55;
            const g = W / 12;
            for (let x = g; x < W; x += g) { ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, H); ctx.stroke(); }
            for (let y = g; y < H; y += g) { ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(W, y); ctx.stroke(); }
            ctx.globalAlpha = 1;

            for (const n of nodes) {
                const k = reduce ? 1 : 0.06;
                n.vx += (n.tx + Math.sin(now / 900 + n.ph) * 2 - n.x) * k;
                n.vy += (n.ty + Math.cos(now / 1100 + n.ph) * 2 - n.y) * k;
                const dx = n.x - mouse.x;
                const dy = n.y - mouse.y;
                const d2 = dx * dx + dy * dy;
                if (d2 < 9000) {
                    const f = ((9000 - d2) / 9000) * 2.2;
                    const d = Math.sqrt(d2 + 1);
                    n.vx += (dx / d) * f;
                    n.vy += (dy / d) * f;
                }
                n.vx *= 0.78;
                n.vy *= 0.78;
                n.x += n.vx;
                n.y += n.vy;
            }

            if (!isSystem) {
                ctx.strokeStyle = col.mut;
                ctx.globalAlpha = 0.6;
                ctx.setLineDash([4, 5]);
                [0.16, 0.3].forEach((r) => { ctx.beginPath(); ctx.arc(W / 2, H / 2, W * r * Math.min(1, t * 1.2), 0, Math.PI * 2); ctx.stroke(); });
                ctx.setLineDash([]);
                const tri = nodes.filter((n) => n.lvl === 1);
                ctx.globalAlpha = 0.16;
                ctx.fillStyle = col.acc;
                ctx.beginPath();
                tri.forEach((n, i) => (i ? ctx.lineTo(n.x, n.y) : ctx.moveTo(n.x, n.y)));
                ctx.closePath();
                ctx.fill();
                ctx.globalAlpha = 1;
                ctx.strokeStyle = col.acc;
                ctx.lineWidth = 2;
                ctx.stroke();
            }

            const prog = Math.min(1, t * 1.4);
            edges.forEach(([a, b], i) => {
                const A = nodes[a]!;
                const B = nodes[b]!;
                ctx.strokeStyle = isSystem ? col.mut : col.line;
                ctx.lineWidth = 1.2;
                ctx.globalAlpha = isSystem ? 0.55 : 0.35;
                ctx.beginPath();
                const my = (A.y + B.y) / 2;
                if (isSystem) {
                    ctx.moveTo(A.x, A.y);
                    ctx.bezierCurveTo(A.x, my, B.x, my, A.x + (B.x - A.x) * prog, A.y + (B.y - A.y) * prog);
                } else {
                    ctx.moveTo(A.x, A.y);
                    ctx.lineTo(B.x, B.y);
                }
                ctx.stroke();
                ctx.globalAlpha = 1;
                if (isSystem && !reduce) {
                    // Data packets travelling down the hierarchy.
                    const p = (now / 1600 + i * 0.137) % 1;
                    const u = 1 - p;
                    const x = u * u * u * A.x + 3 * u * u * p * A.x + 3 * u * p * p * B.x + p * p * p * B.x;
                    const y = u * u * u * A.y + 3 * u * u * p * my + 3 * u * p * p * my + p * p * p * B.y;
                    ctx.fillStyle = i % 5 === 0 ? col.sig : col.acc;
                    ctx.beginPath();
                    ctx.arc(x, y, 1.8, 0, 7);
                    ctx.fill();
                }
            });

            nodes.forEach((n, i) => {
                const near = Math.hypot(n.x - mouse.x, n.y - mouse.y) < 60;
                const r = sizes[n.lvl]! * (near ? 1.5 : 1);
                ctx.fillStyle = n.lvl === 0 ? col.acc : n.lvl === 1 ? col.ink : n.lvl === 2 ? col.mut : i % 7 === 0 ? col.ok : col.mut;
                if (!isSystem && n.lvl === 3) ctx.fillStyle = col.line;
                ctx.beginPath();
                if (n.lvl === 2 && isSystem) ctx.rect(n.x - r, n.y - r, r * 2, r * 2);
                else ctx.arc(n.x, n.y, r, 0, 7);
                ctx.fill();
                if (n.lvl === 0) {
                    ctx.strokeStyle = col.acc;
                    ctx.globalAlpha = 0.35;
                    ctx.beginPath();
                    ctx.arc(n.x, n.y, r + 6 + Math.sin(now / 400) * 2, 0, 7);
                    ctx.stroke();
                    ctx.globalAlpha = 1;
                }
            });
        };

        readColours();
        resize();
        t0Ref.current = performance.now();
        raf = requestAnimationFrame(frame);

        const ro = new ResizeObserver(resize);
        ro.observe(stage);
        const mo = new MutationObserver(readColours);
        mo.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
        const scheme = window.matchMedia('(prefers-color-scheme: dark)');
        scheme.addEventListener('change', readColours);
        // Pause drawing when the hero is off-screen.
        const io = new IntersectionObserver(([e]) => { visible = e?.isIntersecting ?? true; });
        io.observe(stage);

        const onMove = (e: PointerEvent) => {
            const r = stage.getBoundingClientRect();
            mouse.x = e.clientX - r.left;
            mouse.y = e.clientY - r.top;
        };
        const onLeave = () => { mouse.x = mouse.y = -999; };
        stage.addEventListener('pointermove', onMove);
        stage.addEventListener('pointerleave', onLeave);

        if (!reduce) {
            autoRef.current = window.setInterval(() => {
                if (!document.hidden) select(modeRef.current === 'system' ? 'design' : 'system');
            }, 7000);
        }

        return () => {
            cancelAnimationFrame(raf);
            ro.disconnect();
            mo.disconnect();
            io.disconnect();
            scheme.removeEventListener('change', readColours);
            stage.removeEventListener('pointermove', onMove);
            stage.removeEventListener('pointerleave', onLeave);
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
