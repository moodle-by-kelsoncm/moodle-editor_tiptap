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

/**
 * Base class of the TipTap subplugins.
 *
 * Each subplugin tiptap_<name> has a class tiptap_<name>\plugininfo extending this one, and an AMD
 * module (tiptap_<name>/plugin by default) exporting getPlugin(KelsonTiptap, config), which returns
 * an EditorPlugin of @kelsoncm/tiptap-core.
 *
 * @package    editor_tiptap
 * @copyright  2026 Kelson da Costa Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class plugin {
    /**
     * Component of the subplugin, taken from the namespace of the class (tiptap_<name>).
     *
     * @return string
     */
    final public static function get_component(): string {
        return explode('\\', static::class)[0];
    }

    /**
     * Name of the subplugin, without the type prefix.
     *
     * @return string
     */
    final public static function get_name(): string {
        return substr(static::get_component(), strlen('tiptap_'));
    }

    /**
     * Whether the subplugin is used in this editor.
     *
     * By default, the user needs the capability tiptap/<name>:use in the context.
     *
     * @param context $context Context of the editor.
     * @param array $options Options given by the form to the editor.
     * @return bool
     */
    public static function is_enabled(context $context, array $options): bool {
        return has_capability('tiptap/' . static::get_name() . ':use', $context);
    }

    /**
     * Data given to the AMD module of the subplugin.
     *
     * @param context $context Context of the editor.
     * @param array $options Options given by the form to the editor.
     * @param \stdClass|null $fpoptions File picker options, when the field accepts files.
     * @return array Data that can be encoded as JSON.
     */
    public static function get_config_for_context(context $context, array $options, ?\stdClass $fpoptions): array {
        return [];
    }

    /**
     * Toolbar items added by the subplugin (command ids registered by its AMD module).
     *
     * They are added after the toolbar chosen by the administrator, unless already there.
     *
     * @return string[]
     */
    public static function get_toolbar_items(): array {
        return [];
    }

    /**
     * AMD module that returns the EditorPlugin.
     *
     * @return string
     */
    public static function get_amd_module(): string {
        return static::get_component() . '/plugin';
    }
}
