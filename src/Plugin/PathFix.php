<?php
/**
 * @author Joseph Maxwell
 * @copyright SwiftOtter, Inc., 1/3/17
 * @website https://swiftotter.com
 **/

namespace SwiftOtter\ImageImportPathFix\Plugin;

use Magento\CatalogImportExport\Model\Import\Uploader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\WriteInterface;

class PathFix
{
    private $write;

    public function __construct(\Magento\Framework\Filesystem $fileSystem)
    {
        $this->write = $fileSystem->getDirectoryWrite(DirectoryList::ROOT);
    }

    /**
     * Forces output to be an absolute path.
     *
     * @param Uploader $subject
     * @param $result
     * @return string
     */
    public function afterGetTmpDir(Uploader $subject, string $result)
    {
        return $this->write->getAbsolutePath($result);
    }

    /**
     * Forces output to be an absolute path.
     *
     * @param Uploader $subject
     * @param $result
     * @return string
     */
    public function afterGetDestDir(Uploader $subject, string $result)
    {
        return $this->write->getAbsolutePath($result);
    }

    /**
     * Iterates through some common prefixes to see if one matches a directory structure.
     *
     * @param Uploader $subject
     * @param $path
     * @return array
     */
    public function beforeSetTmpDir(Uploader $subject, string $path)
    {
        if (is_string($path) && !$this->write->isReadable($path)) {
            $prefixes = ['pub', 'pub/media'];
            $path = ltrim($path, '/');

            $prefix = array_reduce($prefixes, function(string $carry, string $prefix) use ($path) {
                return $this->write->isReadable($prefix . '/' . $path) ? $prefix : $carry;
            }, '');

            if ($prefix) {
                $path = $prefix . '/' . $path;
            }
        }

        return [$path];
    }
}