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

/**
 * Actions of the TipTap subplugin table when JavaScript is not available.
 *
 * With JavaScript, the table uses the core_admin web services instead.
 *
 * @package    editor_tiptap
 * @copyright  2026 Kelson da Costa Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);
require_sesskey();

$action = required_param('action', PARAM_ALPHA);
$plugin = required_param('plugin', PARAM_PLUGIN);

$PAGE->set_context($context);
$PAGE->set_url('/lib/editor/tiptap/subplugins.php');

$plugins = \core_plugin_manager::instance()->get_installed_plugins('tiptap');
if (!array_key_exists($plugin, $plugins)) {
    throw new moodle_exception('invalidplugin', 'editor_tiptap', '', $plugin);
}

$pluginname = get_string('pluginname', "tiptap_{$plugin}");
$manager = \editor_tiptap\plugininfo\tiptap::class;

switch ($action) {
    case 'enable':
        if ($manager::enable_plugin($plugin, 1)) {
            \core\notification::success(get_string('pluginenabled', 'editor_tiptap', $pluginname));
        }
        break;
    case 'disable':
        if ($manager::enable_plugin($plugin, 0)) {
            \core\notification::success(get_string('plugindisabled', 'editor_tiptap', $pluginname));
        }
        break;
    case 'up':
        $manager::change_plugin_order($plugin, -1);
        break;
    case 'down':
        $manager::change_plugin_order($plugin, 1);
        break;
    default:
        throw new moodle_exception('invalidaction', 'editor_tiptap', '', $action);
}

redirect($manager::get_manage_url());
