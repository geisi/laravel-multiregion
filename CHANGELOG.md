# Release Notes

## [v2.0.0](https://github.com/tpetry/laravel-multiregion/compare/1.0.0...2.0.0) - 2026-11-02

### Added

- Route modifiers `->multiRegionRead()`, `->multiRegionWrite()` and `->noMultiRegion()` to apply the multi-region middleware to a route.
- `NoMultiRegion` middleware to mark routes that intentionally don't use multi-region handling, e.g. health checks or routes without database access.
- URLs generated with `route()` now point automatically to the multi-region endpoint that handles them.

### Breaking Changes

- The configuration file has changed. Republish it or update your copy:
  - `primary_url` has been removed. Whether an instance is part of the primary region was derived from it being empty, it now has to be set explicitly with the new `primary` option.
  - `primary` (`MULTIREGION_PRIMARY`) must be set to `true` in the primary region and to `false` in every replica region.
  - `url.write` (`MULTIREGION_URL_WRITE`) replaces `primary_url` and must lead to the primary region. It is now needed in every region, including the primary one, to generate URLs for write routes.
  - `url.read` (`MULTIREGION_URL_READ`) is new and is used to generate URLs for read routes. It can lead to any region (including the primary region), e.g. a load balancer sending users to their nearest region.

## [v1.0.0](https://github.com/tpetry/laravel-multiregion/compare/...v1.0.0) - 2026-10-28

Initial release.
