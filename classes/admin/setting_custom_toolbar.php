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

namespace editor_tiptap\admin;

use editor_tiptap\local\settings;

/**
 * Custom toolbar: command ids and "|" separators, separated by spaces.
 *
 * Only the syntax is checked here; ids unknown to the editor are skipped by the editor itself,
 * because subplugins add commands that only exist in the browser.
 *
 * @package    editor_tiptap
 * @copyright  2026 Kelson da Costa Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_custom_toolbar extends \admin_setting_configtextarea {
    /**
     * Checks the syntax.
     *
     * @param string $data
     * @return bool|string True, or the error message.
     */
    public function validate($data) {
        return settings::normalise_custom_toolbar((string) $data) === null
            ? get_string('toolbarcustom_invalid', 'editor_tiptap')
            : true;
    }

    /**
     * Stores the toolbar with single spaces.
     *
     * @param string $data
     * @return string Empty on success, otherwise the error message.
     */
    public function write_setting($data) {
        $valid = $this->validate($data);
        if ($valid !== true) {
            return $valid;
        }
        return parent::write_setting(settings::normalise_custom_toolbar((string) $data));
    }
}
