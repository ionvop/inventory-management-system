<?php

namespace App\Services;

use League\CommonMark\Extension\DisallowedRawHtml\DisallowedRawHtmlExtension;
use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Loads and renders the in-app user manual.
 *
 * The manual is authored as Markdown files under `resources/docs/manual/`,
 * one file per section. Files are ordered by a numeric filename prefix
 * (`NN-slug.md`) and titled from their first level-one heading, so adding a
 * section is a matter of dropping a new file into the directory — no code
 * change is required.
 *
 * Markdown is converted to HTML server-side with GitHub Flavored Markdown
 * (tables, strikethrough, autolinks, task lists) and raw HTML is restricted to
 * a safe subset, since the rendered output is injected into the page.
 */
class ManualService
{
    /**
     * The directory holding the manual's Markdown sections.
     */
    protected const string DIRECTORY = 'docs/manual';

    /**
     * The converter used to render Markdown to HTML.
     */
    protected GithubFlavoredMarkdownConverter $converter;

    public function __construct()
    {
        $this->converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        // Strip dangerous raw HTML tags (script, iframe, style, ...) even when
        // they appear inside otherwise-allowed HTML.
        $this->converter->getEnvironment()->addExtension(
            new DisallowedRawHtmlExtension,
        );
    }

    /**
     * The manual's sections, in display order.
     *
     * @return array<int, array{slug: string, title: string}>
     */
    public function sections(): array
    {
        $sections = [];

        foreach ($this->files() as $slug => $path) {
            $sections[] = [
                'slug' => $slug,
                'title' => $this->title($path, $slug),
            ];
        }

        return $sections;
    }

    /**
     * Resolve a requested slug to a known section, falling back to the first.
     */
    public function resolve(?string $slug): ?string
    {
        $sections = $this->sections();

        if ($sections === []) {
            return null;
        }

        foreach ($sections as $section) {
            if ($section['slug'] === $slug) {
                return $section['slug'];
            }
        }

        return $sections[0]['slug'];
    }

    /**
     * Render a section's Markdown to HTML.
     *
     * Returns an empty string when the slug does not match a known section,
     * which also guards against path traversal (only known slugs are read).
     */
    public function html(?string $slug): string
    {
        $resolved = $this->resolve($slug);

        if ($resolved === null) {
            return '';
        }

        $path = $this->files()[$resolved] ?? null;

        if ($path === null) {
            return '';
        }

        $markdown = file_get_contents($path);

        if ($markdown === false) {
            return '';
        }

        return (string) $this->converter->convert($markdown);
    }

    /**
     * Map each section slug to its absolute file path, in display order.
     *
     * @return array<string, string>
     */
    protected function files(): array
    {
        $directory = resource_path(static::DIRECTORY);

        if (! is_dir($directory)) {
            return [];
        }

        $paths = glob($directory.'/*.md');

        if ($paths === false) {
            return [];
        }

        $files = [];

        foreach ($paths as $path) {
            $slug = $this->slug($path);

            if ($slug !== null) {
                $files[$slug] = $path;
            }
        }

        // `glob` returns paths in alphabetical order, and the numeric filename
        // prefix (`01-`, `02-`, ...) makes that the intended display order.
        return $files;
    }

    /**
     * Derive a section slug from a file path.
     *
     * The numeric ordering prefix is stripped, so `03-dashboard.md` becomes
     * `dashboard`.
     */
    protected function slug(string $path): ?string
    {
        $name = pathinfo($path, PATHINFO_FILENAME);

        if ($name === '') {
            return null;
        }

        return preg_replace('/^\d+[-_]/', '', $name) ?? $name;
    }

    /**
     * Read a section's title from its first level-one heading.
     *
     * Falls back to a humanised slug when the file has no `# ` heading.
     */
    protected function title(string $path, string $slug): string
    {
        $contents = file_get_contents($path);

        if ($contents !== false && preg_match('/^#\s+(.+)$/m', $contents, $matches) === 1) {
            return trim($matches[1]);
        }

        return ucfirst(str_replace('-', ' ', $slug));
    }
}
