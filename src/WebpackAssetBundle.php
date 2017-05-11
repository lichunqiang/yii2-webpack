<?php
/**
 * WebpackAssetBundle.php.
 *
 * PHP version 5.6+
 *
 * @author Philippe Gaultier <pgaultier@ibitux.com>
 * @copyright 2010-2017 Ibitux
 * @license http://www.ibitux.com/license license
 *
 * @version 1.1.0
 *
 * @link http://www.ibitux.com
 *
 * @package sweelix\webpack
 */

namespace sweelix\webpack;

use Exception;
use Yii;
use yii\caching\Cache;
use yii\caching\FileDependency;
use yii\di\Instance;
use yii\helpers\Json;
use yii\web\AssetBundle;

/**
 * Base webpack assets manager.
 *
 * @author Philippe Gaultier <pgaultier@ibitux.com>
 * @copyright 2010-2017 Ibitux
 * @license http://www.ibitux.com/license license
 *
 * @version 1.1.0
 *
 * @link http://www.ibitux.com
 *
 * @package sweelix\webpack
 *
 * @since 1.0.0
 */
class WebpackAssetBundle extends AssetBundle
{
    /**
     * string base cache key.
     */
    const CACHE_KEY = 'webpack:bundles:';

    /**
     * @var bool enable caching system
     */
    public $cacheEnabled = false;

    /**
     * @var \yii\caching\Cache cache
     */
    public $cache = 'cache';

    /**
     * @var string name of webpack asset catalog, should be in synch with webpack.config.js
     */
    public $webpackAssetCatalog = 'assets-catalog.json';

    /**
     * @var string default dist directory, should be in synch with webpack.config.js
     */
    public $webpackDistDirectory = 'dist';

    /**
     * @var string base webpack alias
     */
    public $webpackPath;

    /**
     * @var array list of webpack bundles to add
     */
    public $webpackBundles = [];

    /**
     * Merge webpack bundles with classic bundles and cache it if needed.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function mergeWebpackBundles()
    {
        try {
            if ((isset($this->webpackPath) === true) && (is_array($this->webpackBundles) === true)) {
                $cacheKey = self::CACHE_KEY.get_called_class();
                $this->sourcePath = $this->webpackPath.'/'.$this->webpackDistDirectory;
                $cache = $this->getCache();
                if (($cache === null) || ($cache->get($cacheKey) === false)) {
                    $assetsFileAlias = $this->webpackPath.'/'.$this->webpackAssetCatalog;
                    $bundles = file_get_contents(Yii::getAlias($assetsFileAlias));
                    $bundles = Json::decode($bundles);
                    if ($cache !== null) {
                        $cacheDependency = new FileDependency([
                            'fileName' => $assetsFileAlias,
                        ]);
                        $cache->set($cacheKey, $bundles, 0, $cacheDependency);
                    }
                } else {
                    $bundles = $cache->get($cacheKey);
                }
                foreach ($this->webpackBundles as $bundle) {
                    if (isset($bundles[$bundle]['js']) === true) {
                        $this->js[] = $bundles[$bundle]['js'];
                    }
                    if (isset($bundles[$bundle]['css']) === true) {
                        $this->css[] = $bundles[$bundle]['css'];
                    }
                }
            }
        } catch (Exception $e) {
            Yii::error($e->getMessage(), 'sweelix\webpack');
            throw $e;
        }
    }

    /**
     * @return null|Cache
     *
     * @since XXX
     */
    private function getCache()
    {
        if ($this->cacheEnabled === true) {
            $this->cache = Instance::ensure($this->cache, Cache::className());
        }

        return $this->cacheEnabled ? $this->cache : null;
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->mergeWebpackBundles();
        parent::init();
    }
}
