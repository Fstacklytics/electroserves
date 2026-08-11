<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Resolves CMS image references to public URLs.
 *
 * Decap writes media into content/uploads and references it as `/uploads/...`.
 * This service normalises those references, refuses anything that is not a
 * plain relative path or absolute http(s) URL, and reports whether the file is
 * actually present so views can fall back to a placeholder instead of emitting
 * a broken <img>.
 */
class ImageService
{
    /**
     * Convert a CMS image reference into a URL usable in a src attribute.
     *
     * Returns null when the reference is unusable, so callers can render their
     * empty state rather than a broken image.
     */
    public function url(?string $reference): ?string
    {
        $reference = $this->normalise($reference);

        if ($reference === null) {
            return null;
        }

        if (preg_match('#^https?://#i', $reference) === 1) {
            return $reference;
        }

        return asset(ltrim($reference, '/'));
    }

    /**
     * Absolute URL, required for Open Graph and structured data.
     */
    public function absoluteUrl(?string $reference): ?string
    {
        $url = $this->url($reference);

        if ($url === null) {
            return null;
        }

        if (preg_match('#^https?://#i', $url) === 1) {
            return $url;
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
    }

    /**
     * Whether a local image reference actually exists on disk.
     *
     * Remote URLs are assumed present — we do not make outbound requests to
     * check them (see the SSRF note in the threat model).
     */
    public function exists(?string $reference): bool
    {
        $reference = $this->normalise($reference);

        if ($reference === null) {
            return false;
        }

        if (preg_match('#^https?://#i', $reference) === 1) {
            return true;
        }

        return is_file($this->localPath($reference));
    }

    /**
     * Absolute filesystem path for a local image reference.
     */
    public function localPath(string $reference): string
    {
        return public_path(ltrim($reference, '/'));
    }

    /**
     * A WebP sibling path for a raster image, when one has been generated.
     *
     * Used to emit <picture><source type="image/webp"> with a safe fallback.
     */
    public function webpUrl(?string $reference): ?string
    {
        $reference = $this->normalise($reference);

        if ($reference === null || preg_match('#^https?://#i', $reference) === 1) {
            return null;
        }

        if (! preg_match('/\.(jpe?g|png)$/i', $reference)) {
            return null;
        }

        $candidate = preg_replace('/\.(jpe?g|png)$/i', '.webp', $reference);

        if (! is_string($candidate) || ! is_file($this->localPath($candidate))) {
            return null;
        }

        return asset(ltrim($candidate, '/'));
    }

    /**
     * Reject references that are empty or could escape the public directory.
     */
    private function normalise(?string $reference): ?string
    {
        if ($reference === null) {
            return null;
        }

        $reference = trim($reference);

        if ($reference === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $reference) === 1) {
            return $reference;
        }

        // Anything with a scheme other than http(s) (javascript:, data:, //evil)
        // is rejected outright.
        if (str_contains($reference, ':') || str_starts_with($reference, '//')) {
            Log::warning('Rejected image reference with an unexpected scheme.', ['reference' => $reference]);

            return null;
        }

        if (str_contains($reference, '..')) {
            Log::warning('Rejected image reference containing path traversal.', ['reference' => $reference]);

            return null;
        }

        return $reference;
    }
}
