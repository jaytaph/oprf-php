# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.9.0] - 2026-05-04

### Added

- Initial release of `noxlogic/oprf`
- Base mode OPRF implementation per [RFC 9497](https://www.rfc-editor.org/rfc/rfc9497)
- `ristretto255-SHA-512` cipher suite via `ext-sodium`
- `OprfClient` with `blind()` and `finalize()` methods
- `OprfServer` with `generateKey()` and `evaluate()` methods
- Compatible with [liboprf](https://github.com/stef/liboprf)

[0.9.0]: https://github.com/noxlogic/oprf-php/releases/tag/v0.9.0
