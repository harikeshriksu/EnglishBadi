<?php
/**
 * Inline SVG icon library. Every icon is a small, hand-authored constant
 * string (not user input), so it is safe to echo directly - no stock
 * icon fonts, no external requests. Use icon_html('name', 'css-class')
 * to get it with a class attribute injected onto the <svg> tag.
 */

function icon_definitions(): array
{
    return [
        'logo' => '<svg viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><rect width="40" height="40" rx="10" fill="#4A5FBF"/><path d="M9 12c3-1.5 6.5-1.5 9 0v15c-2.5-1.5-6-1.5-9 0V12z" fill="#FFFFFF"/><path d="M31 12c-3-1.5-6.5-1.5-9 0v15c2.5-1.5 6-1.5 9 0V12z" fill="#FFFFFF"/><rect x="19" y="11" width="2" height="17" fill="#3FAE4B"/></svg>',

        'menu' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>',

        'close' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="5" y1="5" x2="19" y2="19"/><line x1="19" y1="5" x2="5" y2="19"/></svg>',

        'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',

        'chevron-left' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>',

        'chevron-right' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>',

        'download' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><polyline points="7 10 12 15 17 10"/><line x1="4" y1="20" x2="20" y2="20"/></svg>',

        'share' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.6" y1="10.5" x2="15.4" y2="6.5"/><line x1="8.6" y1="13.5" x2="15.4" y2="17.5"/></svg>',

        'play' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>',

        'book' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5.5C4 4.7 4.7 4 5.5 4H12v16H5.5C4.7 20 4 19.3 4 18.5V5.5z"/><path d="M20 5.5c0-.8-.7-1.5-1.5-1.5H12v16h6.5c.8 0 1.5-.7 1.5-1.5V5.5z"/></svg>',

        'link' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 14a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1.5 1.5"/><path d="M14 10a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1.5-1.5"/></svg>',

        'image' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>',

        'check-circle' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><polyline points="8 12.5 11 15.5 16 9.5"/></svg>',

        'external' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 5h5v5"/><path d="M19 5l-9 9"/><path d="M18 13v6a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h6"/></svg>',

        'instagram' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/></svg>',

        'youtube' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="4"/><path d="M10 9.5l6 2.5-6 2.5z" fill="currentColor" stroke="none"/></svg>',

        'facebook' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 8h2V4h-2a4 4 0 0 0-4 4v2H9v4h2v6h4v-6h2.5l.5-4H15V8z"/></svg>',

        'upload-cloud' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 18a4 4 0 0 1-1-7.9A5 5 0 0 1 16 8h.5a3.5 3.5 0 0 1 0 7"/><polyline points="12 12 12 21"/><polyline points="9 15 12 12 15 15"/></svg>',

        'plus' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',

        'trash' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>',

        'edit' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>',

        'arrow-up' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="6 11 12 5 18 11"/></svg>',

        'arrow-down' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="18 13 12 19 6 13"/></svg>',

        'grip' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.6"/><circle cx="15" cy="6" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="9" cy="18" r="1.6"/><circle cx="15" cy="18" r="1.6"/></svg>',

        'bold' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4h6.5a4 4 0 0 1 2.7 6.9A4.5 4.5 0 0 1 14 20H7V4z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M7 12h6" stroke="currentColor" stroke-width="2"/></svg>',

        'italic' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="12" y1="4" x2="8" y2="20"/><line x1="7" y1="20" x2="13" y2="20"/><line x1="11" y1="4" x2="17" y2="4"/></svg>',

        'underline' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 4v7a6 6 0 0 0 12 0V4"/><line x1="4" y1="20" x2="20" y2="20"/></svg>',

        'strikethrough' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 7c1-2 3-3 6-3s5 1 5 3-2 3-5 3"/><path d="M7 17c1 2 3 3 6 3s5-1 5-3"/><line x1="4" y1="12" x2="20" y2="12"/></svg>',

        'list-bullet' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="4.5" cy="6" r="1.2" fill="currentColor" stroke="none"/><circle cx="4.5" cy="12" r="1.2" fill="currentColor" stroke="none"/><circle cx="4.5" cy="18" r="1.2" fill="currentColor" stroke="none"/><line x1="9" y1="6" x2="20" y2="6"/><line x1="9" y1="12" x2="20" y2="12"/><line x1="9" y1="18" x2="20" y2="18"/></svg>',

        'list-number' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><text x="2" y="8" font-size="6" fill="currentColor" stroke="none">1</text><text x="2" y="14.5" font-size="6" fill="currentColor" stroke="none">2</text><text x="2" y="21" font-size="6" fill="currentColor" stroke="none">3</text><line x1="9" y1="6" x2="20" y2="6" stroke-width="2"/><line x1="9" y1="12" x2="20" y2="12" stroke-width="2"/><line x1="9" y1="18" x2="20" y2="18" stroke-width="2"/></svg>',

        'quote' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 8c-2 0-3.5 1.5-3.5 3.5S5 15 7 15c-.3 2-1.5 3-3 3.5l.5 1.5c3-1 5-3 5-6.5V9a1 1 0 0 0-1-1H7zm9 0c-2 0-3.5 1.5-3.5 3.5S14 15 16 15c-.3 2-1.5 3-3 3.5l.5 1.5c3-1 5-3 5-6.5V9a1 1 0 0 0-1-1h-1.5z"/></svg>',

        'align-left' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="14" y2="12"/><line x1="4" y1="18" x2="17" y2="18"/></svg>',

        'align-center' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="4" y1="6" x2="20" y2="6"/><line x1="7" y1="12" x2="17" y2="12"/><line x1="5.5" y1="18" x2="18.5" y2="18"/></svg>',

        'undo' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10h9a5 5 0 0 1 0 10h-3"/><polyline points="8 5 4 10 8 15"/></svg>',

        'redo' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10h-9a5 5 0 0 0 0 10h3"/><polyline points="16 5 20 10 16 15"/></svg>',

        'text-color' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9.5 4h3L18 18h-2.2l-1.1-3.4H8.3L7.2 18H5L9.5 4zm.4 8.8h4.2L12 6.8l-2.1 6z" fill="currentColor" stroke="none"/><rect x="4" y="20" width="16" height="3" fill="currentColor"/></svg>',

        'highlight' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 16l8-8 3 3-8 8H6v-3z" fill="currentColor" stroke="none"/><line x1="4" y1="21" x2="20" y2="21"/></svg>',

        'unlink' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 14a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 10a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/><line x1="3" y1="3" x2="21" y2="21"/></svg>',

        'clear-format' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 4h13"/><path d="M9 4l2 16"/><line x1="4" y1="20" x2="14" y2="20"/><line x1="16" y1="16" x2="21" y2="21"/><line x1="21" y1="16" x2="16" y2="21"/></svg>',

        'paragraph' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11 4a5 5 0 0 0 0 10h1v6h2V6h2v14h2V4H11z"/></svg>',
    ];
}

function icon(string $name): string
{
    static $icons = null;
    if ($icons === null) {
        $icons = icon_definitions();
    }
    return $icons[$name] ?? '';
}

function icon_html(string $name, string $extraClass = ''): string
{
    $svg = icon($name);
    if ($svg === '' || $extraClass === '') {
        return $svg;
    }
    return preg_replace('/<svg /', '<svg class="' . e($extraClass) . '" ', $svg, 1);
}
