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

namespace tiptap_media;

/**
 * Tests for the Images and media plugin.
 *
 * @package    tiptap_media
 * @copyright  2026 Kelson da Costa Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tiptap_media\plugininfo
 */
final class plugininfo_test extends \advanced_testcase {
    /**
     * Reset the settings after each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * The plugin only runs in fields that accept files.
     */
    public function test_is_enabled(): void {
        $this->setAdminUser();
        $context = \context_system::instance();
        $this->assertFalse(plugininfo::is_enabled($context, [], null));
        $this->assertFalse(plugininfo::is_enabled($context, [], (object) []));
        $this->assertTrue(plugininfo::is_enabled($context, [], (object) ['image' => (object) ['itemid' => 5]]));
    }

    /**
     * File picker options keep only the context id, and only the types given by the form.
     */
    public function test_config(): void {
        $context = \context_system::instance();
        $fpoptions = (object) [
            'image' => (object) ['itemid' => 5, 'context' => $context, 'client_id' => 'abc'],
            'link' => (object) ['itemid' => 5],
        ];
        $config = plugininfo::get_config_for_context($context, [], $fpoptions);
        $this->assertSame(['image', 'link'], array_keys($config['pickers']));
        $this->assertEquals((object) ['id' => $context->id], $config['pickers']['image']->context);
        $this->assertSame('abc', $config['pickers']['image']->client_id);
        $this->assertInstanceOf(\context::class, $fpoptions->image->context, 'The options of the form are not changed.');
        $this->assertStringContainsString('repository_ajax.php?action=upload', $config['uploadUrl']);
    }

    /**
     * Embed hosts: one per line or comma, lower case, invalid names dropped.
     */
    public function test_embed_hosts(): void {
        set_config('embedhosts', "YouTube.com, player.vimeo.com\n\nbad_host!\nyoutube.com", 'tiptap_media');
        $this->assertSame(['youtube.com', 'player.vimeo.com'], plugininfo::get_embed_hosts());

        set_config('embedhosts', '', 'tiptap_media');
        $this->assertSame([], plugininfo::get_embed_hosts());
    }
}
