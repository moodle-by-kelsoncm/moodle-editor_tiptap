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
 * TipTap editor upgrade steps.
 *
 * @package    editor_tiptap
 * @copyright  2026 Kelson da Costa Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrades the TipTap editor.
 *
 * @param int $oldversion Version installed before the upgrade.
 * @return bool
 */
function xmldb_editor_tiptap_upgrade($oldversion) {
    if ($oldversion < 2026112300) {
        // 2.0: the six 1.x toolbar checkboxes become a custom toolbar without the buttons that were off.
        $legacy = array_keys(\editor_tiptap\local\settings::LEGACY_BUTTONS);
        $config = (array) get_config('editor_tiptap');
        if (array_intersect($legacy, array_keys($config))) {
            set_config('toolbar', 'custom', 'editor_tiptap');
            set_config('toolbarcustom', \editor_tiptap\local\settings::legacy_toolbar($config), 'editor_tiptap');
        }
        foreach ($legacy as $setting) {
            unset_config($setting, 'editor_tiptap');
        }

        upgrade_plugin_savepoint(true, 2026112300, 'editor', 'tiptap');
    }

    return true;
}
