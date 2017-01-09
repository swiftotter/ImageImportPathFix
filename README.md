# System > Import > Images Fix

##Installation:

* `composer require swiftotter/image-import-path-fix`
* `php bin/magento module:enable SwiftOtter_ImageImportPathFix`
* `php bin/magento setup:upgrade`
* `php bin/magento setup:di:compile`

##Description:

Where to begin on this one? This bug fix is for Magento 2.1.2 (latest as of 1/9/17). Nginx and MySQL. Folder structure is a little different
than normal, so that may account for why this bug wasn't found sooner. Nginx is pointed to `pub/index.php`.

Folder structure:
 * current > symlinked to: /path/to/releases/release-2
 * link
    * media
        * import
        * catalog
            * product
    * var
        * log
        * report
 * releases
    * release-1
        * app
        * pub
            * index.php
            * media > symlinked to: link/media
        * ...
        * var > symlinked to: link/var
    * release-2
        * app
        * pub
            * index.php
            * media > symlinked to: link/media
        * ...
        * var > symlinked to: link/var
        
### Issue description:

**The root issue is inconsistent handling of the `pub/` folder.**

Magento's initial file is `pub/index.php`. The `cwd` for this file is `/path/to/releases/release-2/pub/`. However, a feature of
`\Magento\Framework\Filesystem\Directory\ReadInterface->getAbsolutePath()` is that it removes `/pub` from the path structure.
This works well, until trying to use relative paths in combination with vanilla PHP functions, such as `is_readable($relativePath)`.

There are two facets: the `tmp` directory (where the files are coming from) and the `dest` directory (where the files are going to).
Both have unique challenges.

### `tmp` directory:

The `tmp` directory comes from the "Images File Directory". Magento strips out the reference to `pub/` at the end. As the `media` folder
is inside the `pub` folder, it is unclear as to how to reference this folder. The patch deals with this by providing an additional lookup path.

I am not sure if this was also causing issues, but the `tmp` directory was relative and not absolute. This caused problems for the `dest`
directory, so I have patched the `tmp` as well.

### `dest` directory:

This was home to another inconsistency. When creating / setting the folder in `\Magento\CatalogImportExport\Model\Import\Product->_getUploader`,
the paths are initially checked as absolute paths. However, when it is used in `\Magento\Framework\File\Uploader->save()`, the 
`validateDestination()` is called. Inside this function is the use of `is_writable()` with a relative path.

My solution is to make the destination file be absolute. This seems to fix the problem and should work with any environment.