# Composer Dist Plugin

A [Composer](https://getcomposer.org) plugin that allows zip files containing distributable assets to be downloaded and extracted within a package's directory when it's installed.

Useful for packages that need to ship compiled css/js files but don't want to track them within git.

> **Note**: This just downloads and extracts the zip.  
> It doesn't compile or create them. To do that, check the the [Prerequisites](#prerequisites) section below.

## Example

Suppose you publish a PHP package `foo/bar` which expects your compiled dist assets to be located in `resources/dist`. Place this configuration in the `composer.json` for `foo/bar`:

``` json
{
  "name": "foo/bar",
  "require": {
    "pixelfear/composer-dist-plugin": "dev-master"
  },
  "extra": {
    "download-dist": {
      "url": "https://github.com/foo/bar/releases/download/{$version}/dist.tar.gz",
      "path": "resources/dist"
    }
  }
}
```

## Prerequisites

This plugin only downloads and extracts an existing zip from a URL. You will need to create the zip yourself.

A good solution for this could be using a GitHub Actions workflow.

For example, [this workflow](examples/github-workflow.yml) will do the following steps whenever you push a tag starting with `v`:

- Checkout your code using Git
- Run npm install
- Compile assets using Laravel Mix
- Create a tar.gz archive
- Create a GitHub release
- Upload the tar to the release

Of course, this means that archives will only exist for tagged releases. If you are installating a package using a branch like dev-master, the zip will 404. In this case you can manually compile your assets locally.

## Configuration

### Multiple bundles

In the example above, a single zip (bundle) is used. However, you may configure multiple bundles to be downloaded by providing an array of bundle objects.

``` json
  "extra": {
    "download-dist": [
      {
        "url": "...",
        "path": "dist/one",
      },
      {
        "url": "...",
        "path": "dist/two"
      }
    ]
  }
```

### Bundle options

For each bundle, the following options are available:

| Option | Description |
|--------|-------------|
| url | The URL of the zip to download. Supports `zip`, `tar`, or `tar.gz` files. You can include `{$version}` in the URL which will be replaced by the version of the package being installed (eg. `v1.0.0`).
| path | Where the zip should be extracted relative to your package's root. Defaults to `dist`.
| name | Name of the bundle which will be displayed in the Composer output. Defaults to `dist`.
