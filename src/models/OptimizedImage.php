<?php
/**
 * Image Optimize plugin for Craft CMS 3.x
 *
 * Automatically optimize images after they've been transformed
 *
 * @link      https://nystudio107.com
 * @copyright Copyright (c) 2017 nystudio107
 */

namespace nystudio107\imageoptimize\models;

use nystudio107\imageoptimize\ImageOptimize;

use Craft;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\base\Model;
use craft\validators\ArrayValidator;

/**
 * @author    nystudio107
 * @package   ImageOptimize
 * @since     1.2.0
 */
class OptimizedImage extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var array
     */
    public $optimizedImageUrls = [];

    /**
     * @var array
     */
    public $optimizedWebPImageUrls = [];

    /**
     * @var array
     */
    public $variantSourceWidths = [];

    /**
     * @var array
     */
    public $focalPoint;

    /**
     * @var int
     */
    public $originalImageWidth;

    /**
     * @var int
     */
    public $originalImageHeight;

    /**
     * @var string
     */
    public $placeholder = '';

    /**
     * @var
     */
    public $placeholderSvg = '';

    /**
     * @var array
     */
    public $colorPalette = [];

    /**
     * @var int
     */
    public $placeholderWidth;

    /**
     * @var int
     */
    public $placeholderHeight;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['optimizedImageUrls', ArrayValidator::class],
            ['optimizedWebPImageUrls', ArrayValidator::class],
            ['variantSourceWidths', ArrayValidator::class],
            ['focalPoint', 'safe'],
            ['originalImageWidth', 'integer'],
            ['originalImageHeight', 'integer'],
            ['placeholder', 'string'],
            ['placeholderSvg', 'string'],
            ['colorPalette', ArrayValidator::class],
            ['placeholderWidth', 'integer'],
            ['placeholderHeight', 'integer'],
        ];
    }

    /**
     * Return the first image variant URL
     *
     * @return \Twig_Markup|null
     */
    public function src(): string
    {
        return Template::raw(reset($this->optimizedImageUrls));
    }

    /**
     * Return a string of image URLs and their sizes
     *
     * @return \Twig_Markup|null
     */
    public function srcset(): string
    {
        return Template::raw($this->getSrcsetFromArray($this->optimizedImageUrls));
    }

    /**
     * Return a string of image URLs and their sizes that match $width
     *
     * @param int $width
     *
     * @return \Twig_Markup|null
     */
    public function srcsetWidth(int $width): string
    {
        $subset = $this->getSrcsetSubsetArray($this->optimizedImageUrls, $width, 'width');

        return Template::raw($this->getSrcsetFromArray($subset));
    }

    /**
     * Return a string of image URLs and their sizes that are at least $width or larger
     *
     * @param int $width
     *
     * @return \Twig_Markup|null
     */
    public function srcsetMinWidth(int $width): string
    {
        $subset = $this->getSrcsetSubsetArray($this->optimizedImageUrls, $width, 'minwidth');

        return Template::raw($this->getSrcsetFromArray($subset));
    }

    /**
     * Return a string of image URLs and their sizes that are $width or smaller
     *
     * @param int $width
     *
     * @return \Twig_Markup|null
     */
    public function srcsetMaxWidth(int $width): string
    {
        $subset = $this->getSrcsetSubsetArray($this->optimizedImageUrls, $width, 'maxwidth');

        return Template::raw($this->getSrcsetFromArray($subset));
    }

    /**
     * Return a string of webp image URLs and their sizes
     *
     * @return \Twig_Markup|null
     */
    public function srcsetWebp(): string
    {
        return Template::raw($this->getSrcsetFromArray($this->optimizedWebPImageUrls));
    }

    /**
     * Return a string of webp image URLs and their sizes that match $width
     *
     * @param int $width
     *
     * @return \Twig_Markup|null
     */
    public function srcsetWidthWebp(int $width): string
    {
        $subset = $this->getSrcsetSubsetArray($this->optimizedWebPImageUrls, $width, 'width');

        return Template::raw($this->getSrcsetFromArray($subset));
    }

    /**
     * Return a string of webp image URLs and their sizes that are at least $width or larger
     *
     * @param int $width
     *
     * @return \Twig_Markup|null
     */
    public function srcsetMinWidthWebp(int $width): string
    {
        $subset = $this->getSrcsetSubsetArray($this->optimizedWebPImageUrls, $width, 'minwidth');

        return Template::raw($this->getSrcsetFromArray($subset));
    }

    /**
     * Return a string of webp image URLs and their sizes that are $width or smaller
     *
     * @param int $width
     *
     * @return \Twig_Markup|null
     */
    public function srcsetMaxWidthWebp(int $width): string
    {
        $subset = $this->getSrcsetSubsetArray($this->optimizedWebPImageUrls, $width, 'maxwidth');

        return Template::raw($this->getSrcsetFromArray($subset));
    }

    /**
     * Work around issues with `<img srcset>` returning sizes larger than are available
     * as per: https://medium.com/@MRWwebDesign/responsive-images-the-sizes-attribute-and-unexpected-image-sizes-882a2eadb6db
     *
     * @return int
     */
    public function maxSrcsetWidth(): int
    {
        $result = 0;
        if (!empty($this->optimizedImageUrls))
        {
            $tempArray = $this->optimizedImageUrls;
            ksort($tempArray, SORT_NUMERIC);

            $keys = array_keys($tempArray);
            $result = end($keys);
        }

        return $result;
    }

    /**
     * Return a base64-encoded placeholder image
     *
     * @return \Twig_Markup|null
     */
    public function placeholderImage()
    {
        $content = '';
        $header = 'data:image/jpeg;base64,';
        if (!empty($this->placeholder)) {
            $content = $this->placeholder;
        }

        return Template::raw($header . rawurlencode($content));
    }

    /**
     * @return string
     */
    public function placeholderImageSize()
    {
        $placeholder = $this->placeholderImage();
        $contentLength = !empty(strlen($placeholder)) ? strlen($placeholder) : 0;
        return ImageOptimize::$plugin->optimize->humanFileSize($contentLength, 1);
    }

    /**
     * Return an SVG box as a placeholder image
     *
     * @param null $color
     *
     * @return \Twig_Markup|null
     */
    public function placeholderBox($color = null)
    {
        $width = $this->placeholderWidth ?? 1;
        $height = $this->placeholderHeight ?? 1;
        $color = $color ?? $this->colorPalette[0] ?? '#CCC';

        return Template::raw(ImageOptimize::$plugin->placeholder->generatePlaceholderBox($width, $height, $color));
    }

    /**
     * @return string
     */
    public function placeholderBoxSize()
    {
        $placeholder = $this->placeholderBox();
        $contentLength = !empty(strlen($placeholder)) ? strlen($placeholder) : 0;
        return ImageOptimize::$plugin->optimize->humanFileSize($contentLength, 1);
    }

    /**
     * Return a silhouette of the image as an SVG placeholder
     *
     * @return \Twig_Markup|null
     */
    public function placeholderSilhouette()
    {
        $content = '';
        $header = 'data:image/svg+xml,';
        if (!empty($this->placeholderSvg)) {
            $content = $this->placeholderSvg;
        }

        return Template::raw($header . $content);
    }

    /**
     * @return string
     */
    public function placeholderSilhouetteSize()
    {
        $placeholder = $this->placeholderSilhouette();
        $contentLength = !empty(strlen($placeholder)) ? strlen($placeholder) : 0;
        return ImageOptimize::$plugin->optimize->humanFileSize($contentLength, 1);
    }

    /**
     *  Get the file size of any remote resource (using curl),
     *  either in bytes or - default - as human-readable formatted string.
     *
     * @author  Stephan Schmitz <eyecatchup@gmail.com>
     * @license MIT <http://eyecatchup.mit-license.org/>
     * @url     <https://gist.github.com/eyecatchup/f26300ffd7e50a92bc4d>
     *
     * @param   string  $url        Takes the remote object's URL.
     * @param   boolean $formatSize Whether to return size in bytes or
     *                              formatted.
     * @param   boolean $useHead    Whether to use HEAD requests. If false,
     *                              uses GET.
     *
     * @return  int|mixed|string    Returns human-readable formatted size
     *                              or size in bytes (default: formatted).
     */
    public function getRemoteFileSize($url, $formatSize = true, $useHead = true)
    {
        // Make this a full URL / aaw -- 2017.09.08
        if (!UrlHelper::isAbsoluteUrl($url)) {
            if (UrlHelper::isProtocolRelativeUrl($url)) {
                if (isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'], 'on') === 0 || $_SERVER['HTTPS'] == 1)
                    || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0
                ) {
                    $protocol = "https";
                } else {
                    $protocol = "http";
                }
                $url = UrlHelper::urlWithProtocol($url, $protocol);
            } else {
                $url = UrlHelper::siteUrl($url);
            }
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_SSL_VERIFYPEER => 0,
        ]);
        if ($useHead) {
            curl_setopt($ch, CURLOPT_NOBODY, 1);
        }
        curl_exec($ch);
        // content-length of download (in bytes), read from Content-Length: field
        $contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);
        // cannot retrieve file size, return "-1"
        if (!$contentLength) {
            return -1;
        }
        // return size in bytes
        if (!$formatSize) {
            return $contentLength;
        }

        return ImageOptimize::$plugin->optimize->humanFileSize($contentLength, 1);
    }

    // Protected Methods
    // =========================================================================

    protected function getSrcsetSubsetArray(array $set, int $width, string $comparison): array
    {
        $subset = [];
        $index = 0;
        if (!empty($this->variantSourceWidths)) {
            foreach ($this->variantSourceWidths as $variantSourceWidth) {
                $match = false;
                switch ($comparison) {
                    case 'width':
                        if ($variantSourceWidth == $width) {
                            $match = true;
                        }
                        break;

                    case 'minwidth':
                        if ($variantSourceWidth >= $width) {
                            $match = true;
                        }
                        break;

                    case 'maxwidth':
                        if ($variantSourceWidth <= $width) {
                            $match = true;
                        }
                        break;
                }
                if ($match) {
                    $subset+= array_slice($set, $index, 1, true);
                }
                $index++;
            }
        }

        return $subset;
    }
    /**
     * @param array $array
     *
     * @return string
     */
    protected function getSrcsetFromArray(array $array): string
    {
        $srcset = '';
        foreach ($array as $key => $value) {
            $srcset .= $value . ' ' . $key . 'w, ';
        }
        $srcset = rtrim($srcset, ', ');

        return $srcset;
    }
}
