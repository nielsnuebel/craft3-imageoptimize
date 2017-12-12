<?php
/**
 * Image Optimize plugin for Craft CMS 3.x
 *
 * Automatically optimize images after they've been transformed
 *
 * @link      https://nystudio107.com
 * @copyright Copyright (c) 2017 nystudio107
 */

namespace nystudio107\imageoptimize\variables;

use nystudio107\imageoptimize\ImageOptimize;
use nystudio107\imageoptimize\models\OptimizedImage;

use craft\elements\Asset;

/**
 * @author    nystudio107
 * @package   ImageOptimize
 * @since     1.2.0
 */
class ImageOptimizeVariable
{
    // Public Methods
    // =========================================================================

    /**
     * @param Asset          $asset
     * @param array          $variants
     *
     * @return OptimizedImage|null
     */
    public function createOptimizedImages(Asset $asset, $variants = null)
    {
        $model = ImageOptimize::$plugin->optimizedImages->createOptimizedImages($asset, $variants);

        return $model;
    }
}
