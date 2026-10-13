/**
 * The `react` module for this build (see the aliases in vite.config.ts).
 * Types come from preact/compat through the `paths` mapping in tsconfig.json.
 *
 * Re-exports preact/compat with one fix: compat's `use(Context)` depends on
 * render tracking that preact-render-to-string doesn't provide, so Inertia's
 * usePage() (which calls React.use(PageContext)) throws during SSR. Contexts are
 * routed through useContext instead, which is equivalent at the top level of a
 * component, the only place Inertia calls it. Promises still use compat's `use`.
 */
import compat, { use as compatUse, useContext } from 'preact/compat';

export * from 'preact/compat';

/**
 * @template T
 * @param {Promise<T> | import('preact').Context<T>} resource
 * @returns {T}
 */
export function use(resource) {
    return 'then' in resource ? compatUse(resource) : useContext(resource);
}

export default { ...compat, use };
