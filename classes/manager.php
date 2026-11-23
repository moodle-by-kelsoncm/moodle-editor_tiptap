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

use context;
use editor_tiptap\local\settings;
use editor_tiptap\plugininfo\tiptap;

/**
 * Builds the configuration of an editor: settings of the administrator and enabled subplugins.
 *
 * @package    editor_tiptap
 * @copyright  2026 Kelson da Costa Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /** @var string Locale id given to the core for the Moodle strings. */
    public const LOCALE = 'x-moodle';

    /**
     * Configuration of one editor, as given to editor_tiptap/editor.
     *
     * @param context $context Context of the editor.
     * @param array $options Options given by the form to the editor.
     * @param \stdClass|null $fpoptions File picker options, when the field accepts files.
     * @return array
     */
    public static function get_config(context $context, array $options = [], ?\stdClass $fpoptions = null): array {
        global $CFG;

        $config = get_config('editor_tiptap');
        $toolbar = in_array($config->toolbar ?? '', settings::TOOLBARS, true) ? $config->toolbar : 'basic';
        $mode = in_array($config->mode ?? '', settings::MODES, true) ? $config->mode : 'toolbar';

        $plugins = self::get_plugins($context, $options, $fpoptions);
        $items = $toolbar === 'custom'
            ? preg_split('/\s+/', trim((string) ($config->toolbarcustom ?? '')), -1, PREG_SPLIT_NO_EMPTY)
            : [];

        return [
            'contextid' => $context->id,
            'coreUrl' => (new \moodle_url('/lib/editor/tiptap/thirdparty/tiptap-core/kelsoncm-tiptap-core.umd.js'))->out(false),
            'cssUrl' => (new \moodle_url('/lib/editor/tiptap/thirdparty/tiptap-core/kelsoncm-tiptap-core.css'))->out(false),
            'toolbar' => $toolbar,
            'toolbarItems' => $items,
            'mode' => $mode,
            'features' => self::get_features($config->features ?? null),
            'statusBar' => !isset($config->statusbar) || !empty($config->statusbar),
            'language' => current_language(),
            'langRevision' => (int) ($CFG->langrev ?? 0),
            'locale' => self::LOCALE,
            'contentLanguage' => self::get_content_language(current_language()),
            'minHeight' => self::get_min_height($options),
            'plugins' => $plugins,
        ];
    }

    /**
     * Features of the core, on or off, from the multicheckbox setting.
     *
     * @param string|null $setting Comma separated features that are on; null when never saved.
     * @return array<string, bool>
     */
    public static function get_features(?string $setting): array {
        if ($setting === null) {
            return settings::FEATURES;
        }
        $on = array_filter(explode(',', $setting));
        $features = [];
        foreach (array_keys(settings::FEATURES) as $feature) {
            $features[$feature] = in_array($feature, $on, true);
        }
        return $features;
    }

    /**
     * Enabled subplugins allowed in this editor, in their order.
     *
     * @param context $context
     * @param array $options
     * @param \stdClass|null $fpoptions
     * @return array[] Each with name, module, config and toolbar.
     */
    public static function get_plugins(context $context, array $options, ?\stdClass $fpoptions): array {
        $plugins = [];
        foreach (tiptap::get_enabled_plugins() as $name) {
            $class = "tiptap_{$name}\\plugininfo";
            if (!class_exists($class) || !is_subclass_of($class, plugin::class)) {
                debugging("TipTap plugin tiptap_{$name} has no class {$class} extending \\editor_tiptap\\plugin.");
                continue;
            }
            if (!$class::is_enabled($context, $options, $fpoptions)) {
                continue;
            }
            $plugins[] = [
                'name' => $name,
                'module' => $class::get_amd_module(),
                'config' => $class::get_config_for_context($context, $options, $fpoptions),
                'toolbar' => array_values($class::get_toolbar_items()),
            ];
        }
        return $plugins;
    }

    /**
     * Language of the text as a BCP 47 tag, from a Moodle language code (pt_br becomes pt-BR).
     *
     * @param string $lang Moodle language code.
     * @return string
     */
    public static function get_content_language(string $lang): string {
        $parts = explode('_', $lang, 2);
        if (count($parts) === 2 && strlen($parts[1]) === 2) {
            return $parts[0] . '-' . strtoupper($parts[1]);
        }
        return str_replace('_', '-', $lang);
    }

    /**
     * Minimum height of the content, from the number of rows asked by the form.
     *
     * @param array $options
     * @return string CSS length.
     */
    public static function get_min_height(array $options): string {
        $rows = (int) ($options['rows'] ?? 0);
        if ($rows <= 0) {
            return '160px';
        }
        return (max(3, min(30, $rows)) * 1.5) . 'em';
    }
}
