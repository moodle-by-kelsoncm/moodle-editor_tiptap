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

namespace tiptap_link;

/**
 * Tests for the Links to activities plugin.
 *
 * @package    tiptap_link
 * @copyright  2026 Kelson da Costa Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tiptap_link\plugininfo
 */
final class plugininfo_test extends \advanced_testcase {
    /**
     * Reset the database after each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * The plugin runs in courses and their activities, not on the front page or system pages.
     */
    public function test_is_enabled(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);

        $this->assertFalse(plugininfo::is_enabled(\context_system::instance(), []));
        $this->assertFalse(plugininfo::is_enabled(\context_course::instance(SITEID), []));
        $this->assertTrue(plugininfo::is_enabled(\context_course::instance($course->id), []));
        $this->assertTrue(plugininfo::is_enabled(\context_module::instance($page->cmid), []));
    }

    /**
     * Only activities the user can see are listed, with name, URL and icon.
     */
    public function test_activities(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $visible = $generator->create_module('page', ['course' => $course->id, 'name' => 'Syllabus']);
        $generator->create_module('page', ['course' => $course->id, 'name' => 'Hidden', 'visible' => 0]);
        $student = $generator->create_and_enrol($course, 'student');
        $this->setUser($student);

        $config = plugininfo::get_config_for_context(\context_course::instance($course->id), [], null);
        $this->assertCount(1, $config['activities']);
        $activity = $config['activities'][0];
        $this->assertSame((string) $visible->cmid, $activity['id']);
        $this->assertSame('Syllabus', $activity['label']);
        $this->assertStringContainsString('/mod/page/view.php?id=' . $visible->cmid, $activity['url']);
        $this->assertNotEmpty($activity['src']);
    }
}
