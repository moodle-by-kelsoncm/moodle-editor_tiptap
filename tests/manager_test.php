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

namespace editor_tiptap;

/**
 * Tests for the editor configuration.
 *
 * @package    editor_tiptap
 * @copyright  2026 Kelson da Costa Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \editor_tiptap\manager
 */
final class manager_test extends \advanced_testcase {
    /**
     * Reset the settings after each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * The custom toolbar reaches JavaScript as a list of items; presets as their name.
     */
    public function test_toolbar(): void {
        $context = \context_system::instance();
        $this->assertSame([], manager::get_config($context)['toolbarItems']);

        set_config('toolbar', 'custom', 'editor_tiptap');
        set_config('toolbarcustom', 'undo redo | blockType bold', 'editor_tiptap');
        $config = manager::get_config($context);
        $this->assertSame('custom', $config['toolbar']);
        $this->assertSame(['undo', 'redo', '|', 'blockType', 'bold'], $config['toolbarItems']);

        set_config('toolbar', 'unknown', 'editor_tiptap');
        set_config('mode', 'unknown', 'editor_tiptap');
        $config = manager::get_config($context);
        $this->assertSame('basic', $config['toolbar']);
        $this->assertSame('toolbar', $config['mode']);
    }

    /**
     * Features come from the multicheckbox; unknown names are ignored.
     */
    public function test_features(): void {
        $this->assertSame(local\settings::FEATURES, manager::get_features(null));

        $features = manager::get_features('table,fontSize,unknown');
        $this->assertTrue($features['table']);
        $this->assertTrue($features['fontSize']);
        $this->assertFalse($features['fontFamily']);
        $this->assertArrayNotHasKey('unknown', $features);
        $this->assertSame(array_keys(local\settings::FEATURES), array_keys($features));
    }

    /**
     * Moodle language codes become BCP 47 tags for the lang attribute of the text.
     */
    public function test_content_language(): void {
        $this->assertSame('pt-BR', manager::get_content_language('pt_br'));
        $this->assertSame('en', manager::get_content_language('en'));
        $this->assertSame('es-MX', manager::get_content_language('es_mx'));
        $this->assertSame('de-DU', manager::get_content_language('de_du'));
        $this->assertSame('en-kids', manager::get_content_language('en_kids'));
    }

    /**
     * The minimum height follows the rows of the form, within limits.
     *
     * @dataProvider min_height_provider
     * @param array $options
     * @param string $expected
     */
    public function test_min_height(array $options, string $expected): void {
        $this->assertSame($expected, manager::get_min_height($options));
    }

    /**
     * Rows given by forms and the expected heights.
     *
     * @return array
     */
    public static function min_height_provider(): array {
        return [
            'no rows' => [[], '160px'],
            'ten rows' => [['rows' => 10], '15em'],
            'too few' => [['rows' => 1], '4.5em'],
            'too many' => [['rows' => 100], '45em'],
        ];
    }

    /**
     * Subplugins reach the editor when enabled and allowed: media needs file picker options and
     * link needs a course; disabled plugins and users without the capability get none.
     */
    public function test_plugins(): void {
        $system = \context_system::instance();
        $names = fn(array $plugins) => array_column($plugins, 'name');

        $this->assertSame([], manager::get_plugins($system, [], null));

        $this->setAdminUser();
        $this->assertSame(['math', 'office'], $names(manager::get_plugins($system, [], null)));

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'name' => 'Syllabus']);
        $coursecontext = \context_course::instance($course->id);
        $fpoptions = (object) ['image' => (object) ['itemid' => 1, 'context' => $coursecontext, 'repositories' => []]];
        $plugins = manager::get_plugins($coursecontext, [], $fpoptions);
        $this->assertSame(['link', 'math', 'media', 'office'], $names($plugins));
        $this->assertSame('tiptap_media/plugin', $plugins[2]['module']);
        $this->assertSame(['tiptap_media_image', 'tiptap_media_video'], $plugins[2]['toolbar']);

        \editor_tiptap\plugininfo\tiptap::enable_plugin('office', 0);
        $this->assertSame(['link', 'math', 'media'], $names(manager::get_plugins($coursecontext, [], $fpoptions)));
    }
}
