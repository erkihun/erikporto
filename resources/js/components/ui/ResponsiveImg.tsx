import type { ResponsiveImage } from '@/types/portfolio';

interface ResponsiveImgProps {
    image: ResponsiveImage;
    alt: string;
    /** Rendered width at each breakpoint, so the browser picks the right WebP variant. */
    sizes: string;
    /** Above-the-fold images load eagerly; everything else waits for the viewport. */
    eager?: boolean;
}

/**
 * Uploaded image: the stripped original as src, WebP variants as srcset, and the intrinsic
 * width/height so the box is reserved before the file arrives (no layout shift).
 */
export function ResponsiveImg({ image, alt, sizes, eager = false }: ResponsiveImgProps) {
    return (
        <img
            src={image.url}
            srcSet={image.srcset ?? undefined}
            sizes={image.srcset ? sizes : undefined}
            width={image.width ?? undefined}
            height={image.height ?? undefined}
            alt={alt}
            loading={eager ? 'eager' : 'lazy'}
            decoding="async"
        />
    );
}
