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

namespace Core\Model;

use Engine\Db\AbstractModel;
use Engine\Db\Model\Behavior\Sortable;
use User\Model\User;

/**
 * Menu item.
 *
 * @category  PhalconEye
 * @package   Core\Model
 * @author    Ivan Vorontsov <ivan.vorontsov@phalconeye.com>
 * @copyright 2013 PhalconEye Team
 * @license   New BSD License
 * @link      http://phalconeye.com/
 *
 * @Source("menu_items")
 * @BelongsTo("menu_id", '\Core\Model\Menu', "id", {
 *  "alias": "Menu"
 * })
 * @BelongsTo("parent_id", '\Core\Model\MenuItem', "id", {
 *  "alias": "MenuItem"
 * })
 *
 * @method static \Core\Model\MenuItem findFirst($parameters = null)
 */
class MenuItem extends AbstractModel
{
    use Sortable;

    /**
     * @Primary
     * @Identity
     * @Column(type="integer", nullable=false, column="id", size="11")
     */
    public $id;

    /**
     * @Column(type="string", nullable=false, column="title", size="255")
     */
    public $title;

    /**
     * @Column(type="integer", nullable=false, column="menu_id", size="11")
     */
    public $menu_id;

    /**
     * @Column(type="integer", nullable=true, column="parent_id", size="11")
     */
    public $parent_id = null;

    /**
     * @Column(type="integer", nullable=true, column="page_id", size="11")
     */
    public $page_id = null;

    /**
     * @Column(type="string", nullable=true, column="url", size="255")
     */
    public $url = null;

    /**
     * @Column(type="string", nullable=true, column="onclick", size="255")
     */
    public $onclick = null;

    /**
     * @Column(type="string", nullable=true, column="target", size="10")
     */
    public $target = null;

    /**
     * @Column(type="string", nullable=true, column="tooltip", size="255")
     */
    public $tooltip = null;

    /**
     * @Column(type="string", nullable=true, column="tooltip_position", size="10")
     */
    public $tooltip_position = 'top';

    /**
     * @Column(type="string", nullable=true, column="icon", size="255")
     */
    public $icon = null;

    /**
     * @Column(type="string", nullable=false, column="icon_position", size="10")
     */
    public $icon_position = 'left';

    /**
     * @Column(type="string", nullable=true, column="languages", size="150")
     */
    public $languages = null;

    /**
     * @Column(type="string", nullable=true, column="roles", size="150")
     */
    public $roles = null;

    /**
     * Return the related "Menu" entity.
     *
     * @param array $arguments Entity params.
     *
     * @return Menu
     */
    public function getMenu($arguments = [])
    {
        return $this->getRelated('Menu', $arguments);
    }

    /**
     * Return the related "Menu" entity.
     *
     * @param array $arguments Entity params.
     *
     * @return Menu
     */
    public function getMenuItems($arguments = [])
    {
        return $this->getRelated('MenuItem', $arguments);
    }

    /**
     * Returns parent object, it can be MenuItem or Menu (if there is no parent_id).
     *
     * @return MenuItem|Menu
     */
    public function getParent()
    {
        if ($this->parent_id) {
            return self::findFirst($this->parent_id);
        } else {
            return Menu::findFirst($this->menu_id);
        }
    }

    /**
     * Returns the value of field onclick.
     *
     * @return string
     */
    public function getOnclick()
    {
        return str_replace('"', "'", $this->onclick);
    }

    /**
     * Returns the value of field tooltip.
     *
     * @return string
     */
    public function getTooltip()
    {
        return str_replace('"', "'", $this->tooltip);
    }

    /**
     * Returns the value of field languages.
     *
     * @return string
     */
    public function getLanguages()
    {
        if (is_array($this->languages)) {
            return $this->languages;
        }

        return json_decode($this->languages);
    }

    /**
     * Prepare json string to object to interact.
     *
     * @return void
     */
    public function prepareLanguages()
    {
        if (!is_array($this->languages)) {
            $this->languages = json_decode($this->languages);
        }
    }

    /**
     * Returns the value of field roles.
     *
     * @return string
     */
    public function getRoles()
    {
        if (is_array($this->roles)) {
            return $this->roles;
        }

        return json_decode($this->roles);
    }

    /**
     * Prepare json string to object to interact.
     *
     * @return void
     */
    public function prepareRoles()
    {
        if (!is_array($this->roles)) {
            $this->roles = json_decode($this->roles);
        }
    }

    /**
     * Get menu item href.
     *
     * @return null|string
     */
    public function getHref()
    {
        if ($this->page_id) {
            return "page/{$this->page_id}";
        }

        if ($this->url) {
            return $this->url;
        }

        return 'javascript:;';
    }

    /**
     * Check if menu item output is allowed.
     *
     * @return bool
     */
    public function isAllowed()
    {
        $valid = true;
        $viewer = User::getViewer();
        $roles = $this->getRoles();

        if (!empty($roles)) {
            $valid = in_array($viewer->getRoleId(), $roles);
        }

        if (!$valid) {
            return false;
        }

        $valid = true;
        $locale = $this->getDI()->get('session')->get('locale', 'en');
        $languages = $this->getLanguages();

        if (!empty($languages)) {
            $valid = in_array($locale, $languages);
        }

        return $valid;
    }

    /**
     * Logic before removal.
     *
     * @return bool
     */
    protected function beforeDelete()
    {
        $flag = true;
        foreach ($this->getMenuItems() as $item) {
            $flag = $item->delete();
            if (!$flag) {
                break;
            }
        }

        return $flag;
    }

    /**
     * Logic before save.
     *
     * @return void
     */
    protected function beforeSave()
    {
        if (is_array($this->roles) && !empty($this->roles)) {
            $this->roles = json_encode($this->roles);
        } else {
            $this->roles = null;
        }

        if (is_array($this->languages) && !empty($this->languages)) {
            $this->languages = json_encode($this->languages);
        } else {
            $this->languages = null;
        }
    }
}