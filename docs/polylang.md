# Polylang configuration

Polylang is installed per WordPress environment and is not committed to this
repository. HATA A was verified locally with Polylang 3.8.10 and these settings:

- Languages: Nederlands (`nl`, default), English (`en`), and Українська (`uk`).
- Language appears in the URL; the default language code is hidden.
- Pretty permalinks are enabled.
- Browser-language detection and automatic redirection are disabled.
- Media translation support is disabled.
- No custom post types, taxonomies, or synchronized fields are configured yet.

The free Polylang plugin cannot share one post slug across translations. The
HATA A page table therefore assigns a distinct slug to every translation so
WordPress does not add numeric suffixes. Polylang Pro would be required if the
same translated slug were used more than once. Do not bypass this by editing
the database directly.

Language content and translation relationships live in the WordPress database.
They must be recreated by the development seeder or migrated through an explicit
content export; they are not theme files.
