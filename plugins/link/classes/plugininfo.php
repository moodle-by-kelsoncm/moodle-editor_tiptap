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

use context;

/**
 * Links to the activities and resources of the course of the editor.
 *
 * @package    tiptap_link
 * @copyright  2026 Kelson da Costa Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugininfo extends \editor_tiptap\plugin {
    /** @var int Maximum number of activities sent to the browser. */
    public const LIMIT = 300;

    /**
     * Only inside a course (not on the front page or in system pages).
     *
     * @param context $context
     * @param array $options
     * @param \stdClass|null $fpoptions
     * @return bool
     */
    public static function is_enabled(context $context, array $options, ?\stdClass $fpoptions = null): bool {
        $coursecontext = $context->get_course_context(false);
        return $coursecontext && $coursecontext->instanceid != SITEID && parent::is_enabled($context, $options, $fpoptions);
    }

    /**
     * Activities the user can see in the course, in course order.
     *
     * @param context $context
     * @param array $options
     * @param \stdClass|null $fpoptions
     * @return array
     */
    public static function get_config_for_context(context $context, array $options, ?\stdClass $fpoptions): array {
        $coursecontext = $context->get_course_context(false);
        return ['activities' => $coursecontext ? self::get_activities((int) $coursecontext->instanceid) : []];
    }

    /**
     * Toolbar button: link to an activity.
     *
     * @return string[]
     */
    public static function get_toolbar_items(): array {
        return ['tiptap_link_activity'];
    }

    /**
     * Visible activities of a course with their name, URL and icon.
     *
     * @param int $courseid
     * @return array[]
     */
    public static function get_activities(int $courseid): array {
        $activities = [];
        foreach (get_fast_modinfo($courseid)->get_cms() as $cm) {
            if (!$cm->uservisible || !$cm->url || $cm->deletioninprogress) {
                continue;
            }
            $activities[] = [
                'id' => (string) $cm->id,
                'label' => format_string($cm->name, true, ['context' => $cm->context]),
                'url' => $cm->url->out(false),
                'src' => $cm->get_icon_url()->out(false),
            ];
            if (count($activities) >= self::LIMIT) {
                break;
            }
        }
        return $activities;
    }
}
