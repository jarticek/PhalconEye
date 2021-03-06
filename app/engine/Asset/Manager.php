<?php
/*
  +------------------------------------------------------------------------+
  | PhalconEye CMS                                                         |
  +------------------------------------------------------------------------+
  | Copyright (c) 2013 PhalconEye Team (http://phalconeye.com/)            |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file LICENSE.txt.                             |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconeye.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Author: Ivan Vorontsov <ivan.vorontsov@phalconeye.com>                 |
  +------------------------------------------------------------------------+
*/

namespace Engine\Asset;

use Core\Model\Settings;
use Engine\Asset\Css\Less;
use Engine\DependencyInjection;
use Engine\Package\Utilities as FsUtilities;
use Phalcon\Assets\Collection;
use Phalcon\Assets\Filters\Cssmin;
use Phalcon\Assets\Filters\Jsmin;
use Phalcon\Assets\Manager as AssetManager;
use Phalcon\Config;
use Phalcon\DI;
use Phalcon\DiInterface;

/**
 * Assets initializer.
 *
 * @category  PhalconEye
 * @package   Engine\Asset
 * @author    Ivan Vorontsov <ivan.vorontsov@phalconeye.com>
 * @copyright 2013 PhalconEye Team
 * @license   New BSD License
 * @link      http://phalconeye.com/
 */
class Manager extends AssetManager
{
    const
        /**
         * Style file name in url.
         */
        FILENAME_STYLE = 'style.css',

        /**
         * Javascript file name in url.
         */
        FILENAME_JAVASCRIPT = 'javascript.js';

    use DependencyInjection {
        DependencyInjection::__construct as protected __DIConstruct;
    }

    /**
     * Application config.
     *
     * @var Config
     */
    protected $_config;

    /**
     * Initialize assets manager.
     *
     * @param DiInterface $di      Dependency injection.
     * @param bool        $prepare Prepare manager (install assets if in debug and create default collections).
     */
    public function __construct($di, $prepare = true)
    {
        $this->__DIConstruct($di);
        $this->_config = $di->get('config');
        if ($prepare) {
            if ($this->_config->application->debug) {
                $this->installAssets();
            }

            $this->set('css', $this->getEmptyCssCollection());
            $this->set('js', $this->getEmptyJsCollection());
        }
    }

    /**
     * Install assets from all modules.
     *
     * @return void
     */
    public function installAssets()
    {
        $location = $this->_config->application->assets->local;
        $less = Less::factory();

        ///////////////////////////////////
        // Compile themes css.
        ///////////////////////////////////
        $themeDirectory = '';
        if ($this->_config->installed) {
            $themeDirectory = PUBLIC_PATH . '/themes/' . Settings::getSetting('system_theme');
            $themeFiles = glob($themeDirectory . '/*.less');
            FsUtilities::fsCheckLocation($location . 'css/');
            foreach ($themeFiles as $file) {
                $newFileName = $location . 'css/' . basename($file, '.less') . '.css';
                $less->checkedCompile($file, $newFileName);
            }
        }

        ///////////////////////////////////
        // Collect css/js/img from modules.
        ///////////////////////////////////
        foreach ($this->getDI()->get('modules') as $module => $enabled) {
            if (!$enabled) {
                continue;
            }

            // CSS
            $assetsPath = $this->_config->application->modulesDir . ucfirst($module) . '/Assets/';
            $path = $location . 'css/' . $module . '/';
            FsUtilities::fsCheckLocation($path);
            $cssFiles = FsUtilities::fsRecursiveGlob($assetsPath . 'css/*');
            $less->addImportDir($themeDirectory);
            foreach ($cssFiles as $file) {
                if (!is_file($file)) {
                    continue;
                }
                $fileName = basename($file);
                $fileNameWithoutExt = basename($file, '.less');
                $additionalPath = str_replace($fileName, '', str_replace($assetsPath . 'css/', '', $file));
                if (pathinfo($file, PATHINFO_EXTENSION) == 'less') {
                    FsUtilities::fsCheckLocation($path . $additionalPath);
                    $newFileName = $path . $additionalPath . $fileNameWithoutExt . '.css';
                    $less->checkedCompile($file, $newFileName);
                } else {
                    copy($file, $path . $additionalPath . $fileName);
                }
            }

            // JS
            $path = $location . 'js/' . $module . '/';
            FsUtilities::fsCheckLocation($path);
            FsUtilities::fsCopyRecursive($assetsPath . 'js', $path, true);

            // IMAGES
            $path = $location . 'img/' . $module . '/';
            FsUtilities::fsCheckLocation($path);
            FsUtilities::fsCopyRecursive($assetsPath . 'img', $path, true);
        }
    }

    /**
     * Clear assets cache.
     *
     * @param bool $refresh Install and compile new assets?
     *
     * @return void
     */
    public function clear($refresh = true)
    {
        $files = FsUtilities::fsRecursiveGlob(PUBLIC_PATH . '/assets/', '*'); // get all file names
        // iterate files
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file); // delete file
            }
        }

        if ($refresh) {
            $this->installAssets();
        }
    }

    /**
     * Get empty JS collection.
     *
     * @return Collection
     */
    public function getEmptyJsCollection()
    {
        $collection = new Collection();

        $local = $this->_config->application->assets->get('local');
        $remote = $this->_config->application->assets->get('remote');
        if ($remote) {
            $collection
                ->setPrefix($remote)
                ->setLocal(false);
        }

        return $collection
            ->setTargetPath($local . self::FILENAME_JAVASCRIPT)
            ->setTargetUri($local . self::FILENAME_JAVASCRIPT)
            ->addFilter(new Jsmin())
            ->join(!$this->_config->application->debug);
    }

    /**
     * Get empty CSS collection.
     *
     * @return Collection
     */
    public function getEmptyCssCollection()
    {
        $collection = new Collection();

        $local = $this->_config->application->assets->get('local');
        $remote = $this->_config->application->assets->get('remote');
        if ($remote) {
            $collection
                ->setPrefix($remote)
                ->setLocal(false);
        }

        return $collection
            ->setTargetPath($local . self::FILENAME_STYLE)
            ->setTargetUri($local . self::FILENAME_STYLE)
            ->addFilter(new Cssmin())
            ->join(!$this->_config->application->debug);
    }
}