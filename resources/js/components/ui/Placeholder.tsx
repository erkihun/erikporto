/** Marks content the owner still has to fill in from the admin panel. Hidden when the value exists. */
export function Placeholder({ label }: { label: string }) {
    return <span className="ph">{label}</span>;
}
