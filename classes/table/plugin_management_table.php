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

namespace editor_tiptap\table;

use moodle_url;

/**
 * Table of TipTap subplugins on the editor settings page: enable, disable, order, settings and uninstall.
 *
 * @package    editor_tiptap
 * @copyright  2026 Kelson da Costa Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugin_management_table extends \core_admin\table\plugin_management_table {
    /**
     * Plugin type shown in the table.
     *
     * @return string
     */
    protected function get_plugintype(): string {
        return 'tiptap';
    }

    /**
     * The table lives on the TipTap settings page.
     */
    public function guess_base_url(): void {
        $this->define_baseurl(new moodle_url('/admin/settings.php', ['section' => 'editorsettingstiptap']));
    }

    /**
     * Page that runs the actions when JavaScript is not available.
     *
     * @param array $params
     * @return moodle_url
     */
    protected function get_action_url(array $params = []): moodle_url {
        return new moodle_url('/lib/editor/tiptap/subplugins.php', $params);
    }
}
