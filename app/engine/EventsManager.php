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

namespace Engine;

use Phalcon\Config;
use Phalcon\Events\Manager as PhalconEventsManager;

/**
 * Events manager class.
 *
 * @category  PhalconEye
 * @package   Engine
 * @author    Ivan Vorontsov <ivan.vorontsov@phalconeye.com>
 * @copyright 2013 PhalconEye Team
 * @license   New BSD License
 * @link      http://phalconeye.com/
 */
class EventsManager extends PhalconEventsManager
{
    /**
     * Create manager.
     *
     * @param Config $config Application configuration.
     */
    public function __construct($config)
    {
        self::attachEngineEvents($this, $config);
    }

    /**
     * Attach required event.s
     *
     * @param PhalconEventsManager $eventsManager Manager object.
     * @param Config               $config        Application configuration.
     */
    public static function attachEngineEvents($eventsManager, $config)
    {
        // Attach modules plugins events.
        $modules = $config->get('events')->toArray();

        $loadedModules = $config->modules->toArray();
        if (!empty($modules)) {
            foreach ($modules as $module => $events) {
                if (!in_array($module, $loadedModules)) {
                    continue;
                }
                foreach ($events as $event) {
                    $pluginClass = $event['namespace'] . '\\' . $event['class'];
                    $eventsManager->attach($event['type'], new $pluginClass());
                }
            }
        }

        // Attach plugins events.
        $plugins = $config->get('plugins');
        if (!empty($plugins)) {
            foreach ($plugins as $pluginName => $plugin) {

                if (!$plugin['enabled'] || empty($plugin['events']) || !is_array($plugin['events'])) {
                    continue;
                }

                $pluginClass = '\Plugin\\' . ucfirst($pluginName) . '\\' . ucfirst($pluginName);
                foreach ($plugin['events'] as $event) {
                    $eventsManager->attach($event, new $pluginClass());
                }
            }
        }
    }
}