<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace editor_tiptap\plugininfo;

use moodle_url;

/**
 * Subplugin type tiptap: features added to the TipTap editor.
 *
 * Plugins can be enabled, disabled and ordered from the TipTap editor settings page. A disabled
 * plugin has the setting "disabled" in its own component; the order is a comma separated list
 * in the setting editor_tiptap/pluginorder.
 *
 * @package    editor_tiptap
 * @copyright  2026 Kelson da Costa Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tiptap extends \core\plugininfo\base {
    /**
     * Plugins of this type can be disabled.
     *
     * @return bool
     */
    public static function plugintype_supports_disabling(): bool {
        return true;
    }

    /**
     * Plugins of this type can be ordered; the order is the order of their toolbar buttons.
     *
     * @return bool
     */
    public static function plugintype_supports_ordering(): bool {
        return true;
    }

    /**
     * Plugins of this type can be uninstalled.
     *
     * @return bool
     */
    public function is_uninstall_allowed(): bool {
        return true;
    }

    /**
     * URL of the page that manages the plugins of this type.
     *
     * @return moodle_url
     */
    public static function get_manage_url(): moodle_url {
        return new moodle_url('/admin/settings.php', ['section' => 'editorsettingstiptap']);
    }

    /**
     * Name of the settings page of this plugin, when it has a settings.php.
     *
     * @return string|null
     */
    public function get_settings_section_name(): ?string {
        if (!file_exists($this->full_path('settings.php'))) {
            return null;
        }
        return "tiptap_{$this->name}_settings";
    }

    /**
     * Adds the settings page of this plugin under the TipTap category.
     *
     * @param \part_of_admin_tree $adminroot
     * @param string $parentnodename
     * @param bool $hassiteconfig Whether the current user has moodle/site:config
     */
    public function load_settings(\part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig): void {
        // Variables used by the included settings.php, as in the other plugin types.
        global $CFG, $USER, $DB, $OUTPUT, $PAGE;
        $ADMIN = $adminroot;
        $plugininfo = $this;

        if (!$this->is_installed_and_upgraded() || !$hassiteconfig) {
            return;
        }
        $section = $this->get_settings_section_name();
        if ($section === null) {
            return;
        }

        $settings = new \admin_settingpage($section, $this->displayname, 'moodle/site:config', $this->is_enabled() === false);
        include($this->full_path('settings.php'));
        if ($settings) {
            $ADMIN->add($parentnodename, $settings);
        }
    }

    /**
     * Enabled plugins, in their order.
     *
     * @return array Plugin name => plugin name.
     */
    public static function get_enabled_plugins(): array {
        $names = self::get_sorted_names(true);
        return $names ? array_combine($names, $names) : [];
    }

    /**
     * Plugins in their order, as the plugin management table expects them.
     *
     * @param bool $enabledonly Only enabled plugins.
     * @return array|null Plugin name => plugininfo object.
     */
    public static function get_sorted_plugins(bool $enabledonly = false): ?array {
        $plugins = \core_plugin_manager::instance()->get_plugins_of_type('tiptap');
        $sorted = [];
        foreach (self::get_sorted_names($enabledonly) as $name) {
            if (isset($plugins[$name])) {
                $sorted[$name] = $plugins[$name];
            }
        }
        return $sorted;
    }

    /**
     * Names of the installed plugins in their order: first those listed in
     * editor_tiptap/pluginorder, then the others by name.
     *
     * @param bool $enabledonly Only enabled plugins.
     * @return string[]
     */
    public static function get_sorted_names(bool $enabledonly = false): array {
        $installed = array_keys(\core_plugin_manager::instance()->get_installed_plugins('tiptap'));
        sort($installed);
        $saved = array_filter(explode(',', (string) get_config('editor_tiptap', 'pluginorder')));
        $order = array_values(array_unique(array_merge(array_intersect($saved, $installed), $installed)));

        if ($enabledonly) {
            $order = array_values(array_filter($order, fn(string $name) => !get_config("tiptap_{$name}", 'disabled')));
        }
        return $order;
    }

    /**
     * Enables or disables a plugin.
     *
     * @param string $pluginname Plugin name without the type prefix.
     * @param int $enabled 1 to enable, 0 to disable.
     * @return bool Whether the setting changed.
     */
    public static function enable_plugin(string $pluginname, int $enabled): bool {
        $component = "tiptap_{$pluginname}";
        $wasdisabled = !empty(get_config($component, 'disabled'));
        $disable = empty($enabled);
        if ($wasdisabled === $disable) {
            return false;
        }

        if ($disable) {
            set_config('disabled', 1, $component);
        } else {
            unset_config('disabled', $component);
        }
        add_to_config_log('disabled', (int) $wasdisabled, (int) $disable, $component);
        \core_plugin_manager::reset_caches();
        return true;
    }

    /**
     * Moves a plugin one position up or down.
     *
     * @param string $pluginname Plugin name without the type prefix.
     * @param int $direction Negative to move up, positive to move down.
     * @return bool Whether the order changed.
     */
    public static function change_plugin_order(string $pluginname, int $direction): bool {
        $order = self::get_sorted_names();
        $position = array_search($pluginname, $order, true);
        if ($position === false || $direction === 0) {
            return false;
        }
        $target = $position + ($direction < 0 ? -1 : 1);
        if ($target < 0 || $target >= count($order)) {
            return false;
        }

        [$order[$position], $order[$target]] = [$order[$target], $order[$position]];
        $old = (string) get_config('editor_tiptap', 'pluginorder');
        $new = implode(',', $order);
        set_config('pluginorder', $new, 'editor_tiptap');
        add_to_config_log('pluginorder', $old, $new, 'editor_tiptap');
        \core_plugin_manager::reset_caches();
        return true;
    }
}
