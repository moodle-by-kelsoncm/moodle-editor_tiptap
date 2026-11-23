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
 * Mathematical notation for the TipTap editor.
 *
 * Equations are kept as TeX text between \( \) (inline) or $$ $$ (display), the delimiters of the
 * Moodle filters, so existing content keeps working and the filter of the site renders them.
 *
 * @module     tiptap_math/plugin
 * @copyright  2026 Kelson da Costa Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Notification from 'core/notification';
import {getStrings} from 'core/str';

/** @type {Object<string, string[]>} Delimiters by kind. */
const DELIMITERS = {inline: ['\\(', '\\)'], block: ['$$', '$$']};

/**
 * Asks for the TeX of an equation and inserts it where the cursor was.
 *
 * @param {Object} KelsonTiptap API of @kelsoncm/tiptap-core.
 * @param {Object} editor Tiptap editor.
 * @param {Object} i18n Translations of the editor.
 * @param {string} kind inline or block.
 * @returns {Promise<void>}
 */
const insertEquation = async(KelsonTiptap, editor, i18n, kind) => {
    const {from, to} = editor.state.selection;
    const selected = editor.state.doc.textBetween(from, to, ' ');
    const values = await KelsonTiptap.openFormDialog({
        i18n,
        titleKey: `tiptap_math.${kind}`,
        fields: [{name: 'tex', labelKey: 'tiptap_math.tex', type: 'textarea', value: selected, required: true}],
    });
    const tex = String(values?.tex ?? '').trim();
    if (!tex) {
        return;
    }
    const [open, close] = DELIMITERS[kind];
    const text = `${open}${tex}${close}`;
    const chain = editor.chain().focus().setTextSelection({from, to});
    if (kind === 'block') {
        chain.insertContent({type: 'paragraph', content: [{type: 'text', text}]}).run();
    } else {
        chain.insertContent({type: 'text', text}).run();
    }
};

/**
 * Builds the plugin.
 *
 * @param {Object} KelsonTiptap API of @kelsoncm/tiptap-core.
 * @param {Object} config Data from tiptap_math\plugininfo (none).
 * @param {Object} environment Locale id of the Moodle strings.
 * @returns {Promise<Object>} EditorPlugin.
 */
export const getPlugin = async(KelsonTiptap, config, environment) => {
    const [inline, block, tex] = await getStrings(['inline', 'block', 'tex'].map((key) => ({key, component: 'tiptap_math'})));
    return {
        name: 'tiptap_math',
        messages: {
            [environment.locale]: {'tiptap_math.inline': inline, 'tiptap_math.block': block, 'tiptap_math.tex': tex},
        },
        register: (registry, i18n) => {
            ['inline', 'block'].forEach((kind) => registry.register({
                id: `tiptap_math_${kind}`,
                labelKey: `tiptap_math.${kind}`,
                icon: kind === 'inline' ? 'math' : 'math-block',
                group: 'insert',
                isEnabled: (editor) => editor.isEditable,
                run: (editor) => {
                    insertEquation(KelsonTiptap, editor, i18n, kind).catch(Notification.exception);
                    return true;
                },
            }));
        },
    };
};
