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

/**
 * Tests for the tiptap subplugin type.
 *
 * @package    editor_tiptap
 * @copyright  2026 Kelson da Costa Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \editor_tiptap\plugininfo\tiptap
 */
final class tiptap_test extends \advanced_testcase {
    /**
     * Reset the settings after each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * The type is known by Moodle and supports disabling and ordering.
     */
    public function test_plugin_type(): void {
        $this->assertArrayHasKey('tiptap', \core_component::get_plugin_types());
        $this->assertSame('\\' . tiptap::class, \core_plugin_manager::resolve_plugininfo_class('tiptap'));
        $this->assertTrue(tiptap::plugintype_supports_disabling());
        $this->assertTrue(tiptap::plugintype_supports_ordering());
        $this->assertStringContainsString('section=editorsettingstiptap', tiptap::get_manage_url()->out(false));
    }

    /**
     * Enabling and disabling store the "disabled" setting of the plugin and report changes only.
     */
    public function test_enable_plugin(): void {
        $this->assertTrue(tiptap::enable_plugin('example', 0));
        $this->assertEquals(1, get_config('tiptap_example', 'disabled'));
        $this->assertFalse(tiptap::enable_plugin('example', 0));

        $this->assertTrue(tiptap::enable_plugin('example', 1));
        $this->assertFalse(get_config('tiptap_example', 'disabled'));
        $this->assertFalse(tiptap::enable_plugin('example', 1));
    }

    /**
     * Plugins are sorted by name until an order is saved; disabled plugins leave the enabled list.
     */
    public function test_order(): void {
        $all = ['link', 'math', 'media'];
        $this->assertSame($all, tiptap::get_sorted_names());

        $this->assertTrue(tiptap::change_plugin_order('media', -1));
        $this->assertSame(['link', 'media', 'math'], tiptap::get_sorted_names());
        $this->assertSame('link,media,math', get_config('editor_tiptap', 'pluginorder'));

        $this->assertFalse(tiptap::change_plugin_order('link', -1));
        $this->assertFalse(tiptap::change_plugin_order('math', 1));
        $this->assertFalse(tiptap::change_plugin_order('unknown', 1));

        tiptap::enable_plugin('math', 0);
        $this->assertSame(['link', 'media'], array_values(tiptap::get_enabled_plugins()));
        $this->assertCount(3, tiptap::get_sorted_plugins());
        // The plugin management table gets plugininfo objects.
        $this->assertContainsOnlyInstancesOf(tiptap::class, tiptap::get_sorted_plugins());
        $this->assertSame(['link', 'media'], array_keys(tiptap::get_sorted_plugins(true)));
    }

    /**
     * Uninstalled plugins saved in the order are ignored.
     */
    public function test_order_ignores_missing_plugins(): void {
        set_config('pluginorder', 'gone,media,link', 'editor_tiptap');
        $this->assertSame(['media', 'link', 'math'], tiptap::get_sorted_names());
    }
}
