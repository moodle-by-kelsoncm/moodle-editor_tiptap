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

namespace tiptap_math;

/**
 * Mathematical notation written as TeX between \( \) or $$ $$, rendered by the Moodle filters
 * (MathJax or TeX notation) when the text is shown.
 *
 * @package    tiptap_math
 * @copyright  2026 Kelson da Costa Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugininfo extends \editor_tiptap\plugin {
    /**
     * Toolbar buttons: inline and display equations.
     *
     * @return string[]
     */
    public static function get_toolbar_items(): array {
        return ['tiptap_math_inline', 'tiptap_math_block'];
    }
}
