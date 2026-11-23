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
 * TipTap editor settings: subplugins, toolbar, mode and features.
 *
 * @package    editor_tiptap
 * @copyright  2025 Kelson da Costa Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use editor_tiptap\local\settings as tiptapsettings;

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('editorsettings', new admin_category('editortiptap', $editor->displayname, $editor->is_enabled() === false));

$settings = new admin_settingpage('editorsettingstiptap', new lang_string('settings', 'editor_tiptap'));

// Subplugins: enable, disable, order, settings and uninstall.
$settings->add(new \core_admin\admin\admin_setting_plugin_manager(
    'tiptap',
    \editor_tiptap\table\plugin_management_table::class,
    'editor_tiptap_subplugins',
    new lang_string('subplugins', 'editor_tiptap'),
));

if ($ADMIN->fulltree) {
    $toolbars = [];
    foreach (tiptapsettings::TOOLBARS as $toolbar) {
        $toolbars[$toolbar] = new lang_string('toolbar_' . $toolbar, 'editor_tiptap');
    }
    $settings->add(new admin_setting_configselect(
        'editor_tiptap/toolbar',
        new lang_string('toolbarpreset', 'editor_tiptap'),
        new lang_string('toolbarpreset_desc', 'editor_tiptap'),
        'basic',
        $toolbars
    ));

    $settings->add(new \editor_tiptap\admin\setting_custom_toolbar(
        'editor_tiptap/toolbarcustom',
        new lang_string('toolbarcustom', 'editor_tiptap'),
        new lang_string('toolbarcustom_desc', 'editor_tiptap'),
        tiptapsettings::LEGACY_TOOLBAR,
        PARAM_TEXT,
        60,
        3
    ));
    $settings->hide_if('editor_tiptap/toolbarcustom', 'editor_tiptap/toolbar', 'neq', 'custom');

    $modes = [];
    foreach (tiptapsettings::MODES as $mode) {
        $modes[$mode] = new lang_string('mode_' . $mode, 'editor_tiptap');
    }
    $settings->add(new admin_setting_configselect(
        'editor_tiptap/mode',
        new lang_string('mode', 'editor_tiptap'),
        new lang_string('mode_desc', 'editor_tiptap'),
        'toolbar',
        $modes
    ));

    $features = [];
    foreach (array_keys(tiptapsettings::FEATURES) as $feature) {
        $features[$feature] = new lang_string('feature_' . strtolower($feature), 'editor_tiptap');
    }
    $settings->add(new admin_setting_configmulticheckbox(
        'editor_tiptap/features',
        new lang_string('features', 'editor_tiptap'),
        new lang_string('features_desc', 'editor_tiptap'),
        tiptapsettings::default_features(),
        $features
    ));

    $settings->add(new admin_setting_configcheckbox(
        'editor_tiptap/statusbar',
        new lang_string('statusbar', 'editor_tiptap'),
        new lang_string('statusbar_desc', 'editor_tiptap'),
        1
    ));
}

// Added here, not by the plugininfo class, so that the page is listed inside the category.
$ADMIN->add('editortiptap', $settings);

foreach (core_plugin_manager::instance()->get_plugins_of_type('tiptap') as $plugin) {
    /** @var \editor_tiptap\plugininfo\tiptap $plugin */
    $plugin->load_settings($ADMIN, 'editortiptap', $hassiteconfig);
}

// Otherwise the editor plugininfo adds the page a second time.
unset($settings);
$settings = null;
