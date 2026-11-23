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

namespace editor_tiptap\local;

/**
 * Names and defaults of the editor settings, and the migration of the 1.x settings.
 *
 * @package    editor_tiptap
 * @copyright  2026 Kelson da Costa Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class settings {
    /** @var string[] Toolbar presets of the core, plus the custom toolbar. */
    public const TOOLBARS = ['minimal', 'basic', 'full', 'custom'];

    /** @var string[] Editor modes offered to the administrator. */
    public const MODES = ['toolbar', 'balloon', 'notion'];

    /** @var array<string, bool> Optional features of the core and whether they are on by default. */
    public const FEATURES = [
        'fontFamily' => true,
        'fontSize' => true,
        'formatPainter' => true,
        'highlight' => true,
        'indent' => true,
        'lineHeight' => true,
        'listTypes' => true,
        'pageBreak' => true,
        'subscript' => true,
        'superscript' => true,
        'table' => true,
        'tableOfContents' => false,
        'taskList' => true,
        'textAlign' => true,
        'textColor' => true,
    ];

    /** @var array<string, string> 1.x checkbox => toolbar item of the core. */
    public const LEGACY_BUTTONS = [
        'enablebold' => 'bold',
        'enableitalic' => 'italic',
        'enableunderline' => 'underline',
        'enablebulletlist' => 'bulletList',
        'enableorderedlist' => 'orderedList',
        'enablelink' => 'link',
    ];

    /** @var string Toolbar of the 1.x editor, written with the custom toolbar syntax. */
    public const LEGACY_TOOLBAR = 'undo redo | blockType | bold italic underline | bulletList orderedList | link';

    /** @var string Pattern of the custom toolbar: command ids and "|" separated by spaces. */
    public const CUSTOM_TOOLBAR_PATTERN = '/^\s*(?:[A-Za-z][A-Za-z0-9:_-]*|\|)(?:\s+(?:[A-Za-z][A-Za-z0-9:_-]*|\|))*\s*$/';

    /**
     * Features turned on by default, as stored by admin_setting_configmulticheckbox.
     *
     * @return array<string, int>
     */
    public static function default_features(): array {
        return array_map(fn(bool $on) => (int) $on, array_filter(self::FEATURES));
    }

    /**
     * Toolbar of a 1.x site: the 1.x toolbar without the buttons the administrator turned off.
     *
     * @param array $config Settings of the 1.x editor (checkbox => '1' or '0').
     * @return string Custom toolbar.
     */
    public static function legacy_toolbar(array $config): string {
        $disabled = [];
        foreach (self::LEGACY_BUTTONS as $setting => $item) {
            if (array_key_exists($setting, $config) && empty($config[$setting])) {
                $disabled[] = $item;
            }
        }
        $groups = array_map(
            fn(string $group) => implode(' ', array_diff(preg_split('/\s+/', trim($group)), $disabled)),
            explode('|', self::LEGACY_TOOLBAR)
        );
        return implode(' | ', array_filter($groups, fn(string $group) => $group !== ''));
    }

    /**
     * Normalises a custom toolbar (single spaces), or returns null when the syntax is wrong.
     *
     * @param string $value
     * @return string|null
     */
    public static function normalise_custom_toolbar(string $value): ?string {
        if (trim($value) === '') {
            return '';
        }
        if (!preg_match(self::CUSTOM_TOOLBAR_PATTERN, $value)) {
            return null;
        }
        return implode(' ', preg_split('/\s+/', trim($value)));
    }
}
