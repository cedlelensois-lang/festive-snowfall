# Security Policy · Politique de sécurité

## Supported versions · Versions maintenues

| Version | Supported |
|---|---|
| 2.x | ✅ |
| 1.x | ❌ |

## Reporting a vulnerability

Please **do not open a public issue**. Use GitHub's private reporting instead: *Security → Report a vulnerability* on this repository. We aim to reply within 7 days.

## Signaler une vulnérabilité

Merci de **ne pas ouvrir de ticket public**. Utilisez le signalement privé de GitHub : *Security → Report a vulnerability* sur ce dépôt. Nous visons une réponse sous 7 jours.

## Security design · Conception

- Settings are saved through the WordPress Settings API (nonce + `manage_options` capability checks handled by core).
- Every setting is validated against a whitelist or bounded range (dates via `checkdate`, colours via `sanitize_hex_color`, integers clamped, banner/flake types whitelisted, CSS selector sanitized and length-limited).
- All admin output is escaped (`esc_html`, `esc_attr`, `esc_url`).
- Front-end configuration is passed as JSON with `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`, so it cannot break out of the `<script>` element.
- The JavaScript never uses `innerHTML`; the CSS selector is only passed to `querySelector` inside `try/catch`.
- No AJAX/REST endpoints, no custom database queries, no file writes.
- The only outgoing request is a read-only call to the public GitHub API for updates (no site data sent, results cached 12 h). Update packages are only accepted from this repository's own release assets; WordPress core downloads and installs them.
