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
 * Tests for the setting helpers and the migration of the 1.x toolbar.
 *
 * @package    editor_tiptap
 * @copyright  2026 Kelson da Costa Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \editor_tiptap\local\settings
 */
final class settings_test extends \basic_testcase {
    /**
     * The 1.x toolbar loses only the buttons that were off, and empty groups disappear.
     *
     * @dataProvider legacy_provider
     * @param array $config
     * @param string $expected
     */
    public function test_legacy_toolbar(array $config, string $expected): void {
        $this->assertSame($expected, settings::legacy_toolbar($config));
    }

    /**
     * 1.x settings and the expected custom toolbars.
     *
     * @return array
     */
    public static function legacy_provider(): array {
        return [
            'all on' => [
                ['enablebold' => '1', 'enableitalic' => '1', 'enablelink' => '1'],
                'undo redo | blockType | bold italic underline | bulletList orderedList | link',
            ],
            'underline and link off' => [
                ['enableunderline' => '0', 'enablelink' => '0'],
                'undo redo | blockType | bold italic | bulletList orderedList',
            ],
            'lists off' => [
                ['enablebulletlist' => '0', 'enableorderedlist' => '0'],
                'undo redo | blockType | bold italic underline | link',
            ],
        ];
    }

    /**
     * The custom toolbar accepts command ids and separators only.
     */
    public function test_normalise_custom_toolbar(): void {
        $this->assertSame('', settings::normalise_custom_toolbar('  '));
        $this->assertSame('undo redo | bold', settings::normalise_custom_toolbar(" undo  redo\n| bold "));
        $this->assertSame('widget:alerta tiptap_x-y', settings::normalise_custom_toolbar('widget:alerta tiptap_x-y'));
        $this->assertNull(settings::normalise_custom_toolbar('bold <script>'));
        $this->assertNull(settings::normalise_custom_toolbar('bold,italic'));
        $this->assertNull(settings::normalise_custom_toolbar('1bold'));
    }

    /**
     * The default features are those on in the core, stored as the multicheckbox does.
     */
    public function test_default_features(): void {
        $defaults = settings::default_features();
        $this->assertArrayHasKey('table', $defaults);
        $this->assertArrayNotHasKey('tableOfContents', $defaults);
        $this->assertSame([1], array_values(array_unique($defaults)));
    }
}
