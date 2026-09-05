<?php
/**
 * Allowlist HTML sanitizer for lesson / page rich-text bodies saved by the
 * admin WYSIWYG editor. Built on DOMDocument (a core PHP extension, no
 * external library needed). Anything not explicitly allowed is either
 * stripped (dangerous tags, with their content) or unwrapped (unknown but
 * harmless wrapper tags keep their text/children, lose the tag itself).
 */

const SANITIZER_ALLOWED_TAGS = [
    'p', 'br', 'h2', 'h3', 'strong', 'em', 'u', 's',
    'ul', 'ol', 'li', 'blockquote', 'a', 'img', 'span',
];

const SANITIZER_STRIP_ENTIRELY_TAGS = [
    'script', 'style', 'iframe', 'object', 'embed', 'form', 'input',
    'button', 'textarea', 'select', 'link', 'meta', 'title', 'head', 'svg', 'noscript',
];

// "style" is allowed on any tag a browser's contenteditable might attach a
// text/highlight colour to - not just <span> - since different browsers
// wrap the same toolbar action differently. sanitize_style_attr() still
// restricts the actual CSS properties to color/background-color everywhere.
const SANITIZER_ALLOWED_ATTRS = [
    '*'          => ['class', 'style'],
    'a'          => ['href'],
    'img'        => ['src', 'alt'],
    'span'       => ['lang'],
];

function sanitize_html(string $html): string
{
    if (trim($html) === '') {
        return '';
    }

    $doc = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $doc->loadHTML(
        '<?xml encoding="utf-8"?><div id="sanitize-root">' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();

    $root = $doc->getElementById('sanitize-root');
    if (!$root) {
        return '';
    }

    sanitize_dom_children($root, $doc);

    $output = '';
    foreach (iterator_to_array($root->childNodes) as $child) {
        $output .= $doc->saveHTML($child);
    }

    return trim($output);
}

function sanitize_dom_children(DOMNode $node, DOMDocument $doc): void
{
    foreach (iterator_to_array($node->childNodes) as $child) {
        if ($child->nodeType === XML_TEXT_NODE) {
            continue;
        }

        if ($child->nodeType !== XML_ELEMENT_NODE) {
            $node->removeChild($child);
            continue;
        }

        /** @var DOMElement $child */
        $tag = strtolower($child->tagName);

        if (in_array($tag, SANITIZER_STRIP_ENTIRELY_TAGS, true)) {
            $node->removeChild($child);
            continue;
        }

        if (!in_array($tag, SANITIZER_ALLOWED_TAGS, true)) {
            // Sanitize its subtree first, THEN unwrap - so nothing unsafe
            // can escape by hiding inside a tag we don't otherwise allow.
            sanitize_dom_children($child, $doc);

            $fragment = $doc->createDocumentFragment();
            foreach (iterator_to_array($child->childNodes) as $grandchild) {
                $fragment->appendChild($grandchild);
            }
            $node->replaceChild($fragment, $child);
            continue;
        }

        sanitize_attributes($child, $tag);
        sanitize_dom_children($child, $doc);
    }
}

function sanitize_attributes(DOMElement $el, string $tag): void
{
    $allowed = array_merge(SANITIZER_ALLOWED_ATTRS['*'], SANITIZER_ALLOWED_ATTRS[$tag] ?? []);
    $toRemove = [];

    foreach (iterator_to_array($el->attributes) as $attr) {
        $name = strtolower($attr->nodeName);

        if (!in_array($name, $allowed, true)) {
            $toRemove[] = $attr->nodeName;
            continue;
        }

        if ($name === 'href') {
            $safe = sanitize_url_attr($attr->nodeValue);
            if ($safe === null) {
                $toRemove[] = $attr->nodeName;
            } else {
                $el->setAttribute('href', $safe);
                $el->setAttribute('target', '_blank');
                $el->setAttribute('rel', 'noopener noreferrer');
            }
        } elseif ($name === 'src') {
            $safe = sanitize_url_attr($attr->nodeValue);
            if ($safe === null) {
                $toRemove[] = $attr->nodeName;
            } else {
                $el->setAttribute('src', $safe);
            }
        } elseif ($name === 'style') {
            $safeStyle = sanitize_style_attr($attr->nodeValue);
            if ($safeStyle === '') {
                $toRemove[] = $attr->nodeName;
            } else {
                $el->setAttribute('style', $safeStyle);
            }
        } elseif ($name === 'class') {
            if (!preg_match('/^[a-zA-Z0-9_\- ]{1,100}$/', $attr->nodeValue)) {
                $toRemove[] = $attr->nodeName;
            }
        } elseif ($name === 'lang') {
            if (!preg_match('/^[a-zA-Z\-]{1,10}$/', $attr->nodeValue)) {
                $toRemove[] = $attr->nodeName;
            }
        }
    }

    foreach ($toRemove as $name) {
        $el->removeAttribute($name);
    }

    if ($tag === 'img' && !$el->hasAttribute('alt')) {
        $el->setAttribute('alt', '');
    }
}

function sanitize_url_attr(string $url): ?string
{
    $url = trim($url);
    if ($url === '') {
        return null;
    }

    $lower = strtolower($url);
    foreach (['javascript:', 'data:', 'vbscript:', 'file:'] as $bad) {
        if (str_starts_with($lower, $bad)) {
            return null;
        }
    }

    if (preg_match('#^https?://#i', $url) || str_starts_with($lower, 'mailto:') || str_starts_with($url, '/') || str_starts_with($url, '#')) {
        return $url;
    }

    // relative path with no scheme at all (e.g. "uploads/lessons/x.jpg")
    if (!str_contains($url, ':')) {
        return $url;
    }

    return null;
}

function sanitize_style_attr(string $style): string
{
    $allowedProps = ['color', 'background-color'];
    $out = [];

    foreach (explode(';', $style) as $decl) {
        $decl = trim($decl);
        if ($decl === '' || !str_contains($decl, ':')) {
            continue;
        }
        [$prop, $val] = array_map('trim', explode(':', $decl, 2));
        $prop = strtolower($prop);
        if (!in_array($prop, $allowedProps, true)) {
            continue;
        }
        if (!preg_match('/^(#[0-9a-fA-F]{3,8}|rgba?\([0-9,.\s%]+\)|[a-zA-Z]{3,20})$/', $val)) {
            continue;
        }
        $out[] = $prop . ': ' . $val;
    }

    return implode('; ', $out);
}
