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

/**
 * Links to activities of the course for the TipTap editor.
 *
 * @module     tiptap_link/plugin
 * @copyright  2026 Kelson da Costa Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Notification from 'core/notification';
import {getString} from 'core/str';

/**
 * Asks for an activity and links the selection to it (or inserts its name as a link).
 *
 * @param {Object} KelsonTiptap API of @kelsoncm/tiptap-core.
 * @param {Object} editor Tiptap editor.
 * @param {Object} i18n Translations of the editor.
 * @param {Object[]} activities Activities of the course (id, label, url, src).
 * @returns {Promise<void>}
 */
const linkActivity = async(KelsonTiptap, editor, i18n, activities) => {
    const {from, to} = editor.state.selection;
    const activity = await KelsonTiptap.openPickerDialog({i18n, titleKey: 'tiptap_link.activity', items: activities});
    if (!activity) {
        return;
    }
    const chain = editor.chain().focus().setTextSelection({from, to});
    if (from === to) {
        // No selection: the activity name becomes the link text.
        chain.insertContent({type: 'text', text: activity.label, marks: [{type: 'link', attrs: {href: activity.url}}]}).run();
    } else {
        chain.setLink({href: activity.url}).run();
    }
};

/**
 * Builds the plugin.
 *
 * @param {Object} KelsonTiptap API of @kelsoncm/tiptap-core.
 * @param {Object} config Data from tiptap_link\plugininfo::get_config_for_context().
 * @param {Object} environment Locale id of the Moodle strings.
 * @returns {Promise<Object>} EditorPlugin.
 */
export const getPlugin = async(KelsonTiptap, config, environment) => {
    const label = await getString('activity', 'tiptap_link');
    return {
        name: 'tiptap_link',
        messages: {[environment.locale]: {'tiptap_link.activity': label}},
        register: (registry, i18n) => {
            registry.register({
                id: 'tiptap_link_activity',
                labelKey: 'tiptap_link.activity',
                icon: 'link',
                group: 'insert',
                isEnabled: (editor) => editor.isEditable && config.activities.length > 0,
                run: (editor) => {
                    linkActivity(KelsonTiptap, editor, i18n, config.activities).catch(Notification.exception);
                    return true;
                },
            });
        },
    };
};
