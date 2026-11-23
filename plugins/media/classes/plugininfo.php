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

use context;

/**
 * Images, video and audio from the Moodle file picker, uploaded to the draft area of the field.
 *
 * @package    tiptap_media
 * @copyright  2026 Kelson da Costa Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugininfo extends \editor_tiptap\plugin {
    /** @var string[] File picker types used by the plugin. */
    private const TYPES = ['image', 'media', 'link'];

    /**
     * Only for fields that accept files (they have file picker options).
     *
     * @param context $context
     * @param array $options
     * @param \stdClass|null $fpoptions
     * @return bool
     */
    public static function is_enabled(context $context, array $options, ?\stdClass $fpoptions = null): bool {
        return !empty($fpoptions->image) && parent::is_enabled($context, $options, $fpoptions);
    }

    /**
     * File picker options per type and the hosts allowed for embedded content.
     *
     * @param context $context
     * @param array $options
     * @param \stdClass|null $fpoptions
     * @return array
     */
    public static function get_config_for_context(context $context, array $options, ?\stdClass $fpoptions): array {
        $pickers = [];
        foreach (self::TYPES as $type) {
            if (empty($fpoptions->$type)) {
                continue;
            }
            $picker = clone (object) $fpoptions->$type;
            // The context object does not survive JSON encoding: keep its id.
            $pickercontext = $picker->context ?? $context;
            $picker->context = (object) ['id' => $pickercontext instanceof context ? $pickercontext->id : $context->id];
            $pickers[$type] = $picker;
        }

        return [
            'pickers' => $pickers,
            'embedHosts' => self::get_embed_hosts(),
            'uploadUrl' => (new \moodle_url('/repository/repository_ajax.php', ['action' => 'upload']))->out(false),
        ];
    }

    /**
     * Toolbar buttons: pick an image and pick a video or audio file.
     *
     * @return string[]
     */
    public static function get_toolbar_items(): array {
        return ['tiptap_media_image', 'tiptap_media_video'];
    }

    /**
     * Hosts allowed for embedded video and pages, from the plugin settings.
     *
     * @return string[]
     */
    public static function get_embed_hosts(): array {
        $setting = (string) get_config('tiptap_media', 'embedhosts');
        $hosts = preg_split('/[\s,]+/', strtolower($setting), -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_unique(array_filter($hosts, fn(string $host) => preg_match('/^[a-z0-9.-]+$/', $host))));
    }
}
