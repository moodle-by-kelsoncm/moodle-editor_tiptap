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
 * TipTap editor AMD module.
 *
 * Replaces the text area of a Moodle form with an editor of @kelsoncm/tiptap-core (bundled in
 * thirdparty/tiptap-core) and keeps the text area value in sync with the edited HTML. The
 * interface texts come from the Moodle strings of editor_tiptap ("kt:" strings), and enabled
 * TipTap subplugins add their own features.
 *
 * @module     editor_tiptap/editor
 * @copyright  2025 Kelson da Costa Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import LocalStorage from 'core/localstorage';
import Notification from 'core/notification';
import {getString} from 'core/str';

/** @type {string} Prefix of the core interface strings in lang/en/editor_tiptap.php. */
const STRING_PREFIX = 'kt:';

/** @type {Promise<Object>|null} The core library, loaded once per page. */
let corePromise = null;

/** @type {Map<string, Promise<Object>>} Interface strings by language and revision. */
const stringPromises = new Map();

/**
 * Loads the core library (UMD build) and its stylesheet, once per page.
 *
 * The stylesheet is added from here so that it also works in forms loaded in modals, after the
 * page head was printed.
 *
 * @param {Object} config Editor configuration.
 * @returns {Promise<Object>} The KelsonTiptap API.
 */
const loadCore = (config) => {
    if (!corePromise) {
        if (!document.querySelector('link[data-editor-tiptap]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = config.cssUrl;
            link.dataset.editorTiptap = 'core';
            document.head.appendChild(link);
        }
        corePromise = new Promise((resolve, reject) => {
            window.require([config.coreUrl], resolve, reject);
        });
    }
    return corePromise;
};

/**
 * Turns Moodle placeholders ({$a->name}) into core placeholders ({name}).
 *
 * @param {string} text
 * @returns {string}
 */
const toCorePlaceholders = (text) => text.replace(/\{\$a->(\w+)\}/g, '{$1}');

/**
 * Fetches the interface strings of the core in the user language, cached in the browser for the
 * current language revision.
 *
 * @param {Object} config Editor configuration.
 * @returns {Promise<Object>} Core key => text.
 */
const loadStrings = (config) => {
    const key = `editor_tiptap/strings/${config.language}/${config.langRevision}`;
    if (!stringPromises.has(key)) {
        stringPromises.set(key, (async() => {
            // Revision 0 or less means the language cache is off: always ask the server.
            const cacheable = config.langRevision > 0;
            if (cacheable) {
                const cached = LocalStorage.get(key);
                if (cached) {
                    try {
                        return JSON.parse(cached);
                    } catch (error) {
                        // A broken cache entry is fetched again below.
                    }
                }
            }
            const [request] = Ajax.call([{
                methodname: 'core_get_component_strings',
                args: {component: 'editor_tiptap', lang: config.language},
            }]);
            const messages = {};
            (await request).forEach(({stringid, string}) => {
                if (stringid.startsWith(STRING_PREFIX)) {
                    messages[stringid.slice(STRING_PREFIX.length)] = toCorePlaceholders(string);
                }
            });
            if (cacheable) {
                LocalStorage.set(key, JSON.stringify(messages));
            }
            return messages;
        })());
    }
    return stringPromises.get(key);
};

/**
 * Loads the enabled subplugins. A subplugin that fails is skipped and reported.
 *
 * @param {Object} KelsonTiptap The core API.
 * @param {Object} config Editor configuration.
 * @returns {Promise<Object[]>} EditorPlugin objects for the core.
 */
const loadPlugins = async(KelsonTiptap, config) => {
    const plugins = await Promise.all(config.plugins.map(async(plugin) => {
        try {
            const module = await new Promise((resolve, reject) => {
                window.require([plugin.module], resolve, reject);
            });
            return await module.getPlugin(KelsonTiptap, plugin.config, {locale: config.locale});
        } catch (error) {
            Notification.exception(error);
            return null;
        }
    }));
    return plugins.filter(Boolean);
};

/**
 * Toolbar items: the preset or the custom toolbar chosen by the administrator, followed by the
 * items of the subplugins that are not there yet.
 *
 * In the custom toolbar, a dropdown is written by its command id (blockType, fontFamily...).
 *
 * @param {Object} KelsonTiptap The core API.
 * @param {Object} config Editor configuration.
 * @returns {Array} Toolbar items for the core.
 */
const toolbarItems = (KelsonTiptap, config) => {
    const dropdowns = new Map([['blockType', KelsonTiptap.BLOCK_TYPE_SELECT]]);
    KelsonTiptap.TOOLBAR_PRESETS.full.forEach((item) => {
        if (typeof item === 'object' && item.type === 'select') {
            dropdowns.set(item.command, item);
        }
    });

    const items = config.toolbar === 'custom'
        ? config.toolbarItems.map((id) => dropdowns.get(id) ?? id)
        : [...KelsonTiptap.TOOLBAR_PRESETS[config.toolbar]];

    const present = new Set(items.map((item) => (typeof item === 'string' ? item : item.command ?? item.labelKey)));
    const extra = config.plugins.flatMap((plugin) => plugin.toolbar).filter((id) => !present.has(id));
    return extra.length ? [...items, KelsonTiptap.SEPARATOR, ...extra] : items;
};

/**
 * Initialise the editor for a text area.
 *
 * @param {string} elementId Id of the text area.
 * @param {Object} config Configuration built by \editor_tiptap\manager::get_config().
 * @returns {Promise<void>}
 */
export const init = async(elementId, config) => {
    const textarea = document.getElementById(elementId);
    if (!textarea || textarea.dataset.editorTiptap) {
        return;
    }
    textarea.dataset.editorTiptap = 'loading';

    try {
        const KelsonTiptap = await loadCore(config);
        const [messages, plugins] = await Promise.all([loadStrings(config), loadPlugins(KelsonTiptap, config)]);

        const host = document.createElement('div');
        host.className = 'editor_tiptap';
        host.id = `${elementId}_tiptap`;
        textarea.insertAdjacentElement('afterend', host);

        const readonly = textarea.disabled || textarea.readOnly;
        const ui = KelsonTiptap.createEditorUI(host, {
            content: textarea.value,
            mode: readonly ? 'readonly' : config.mode,
            toolbar: toolbarItems(KelsonTiptap, config),
            features: config.features,
            statusBar: config.statusBar,
            minHeight: config.minHeight,
            plugins,
            i18n: {
                locale: config.locale,
                fallbackLocale: 'en',
                messages: {[config.locale]: messages},
            },
        });

        // The label of the text area names the editable area for screen readers.
        const label = document.querySelector(`label[for="${CSS.escape(elementId)}"]`);
        if (label) {
            label.id = label.id || `${elementId}_label`;
            ui.editor.view.dom.setAttribute('aria-labelledby', label.id);
        }

        // The text area keeps the stored text until the user changes something, so content the
        // editor does not support is only lost when the text is edited.
        ui.editor.on('update', () => {
            textarea.value = ui.editor.isEmpty ? '' : ui.editor.getHTML().replace(/(?:<p><\/p>)+$/, '');
            textarea.dispatchEvent(new Event('input', {bubbles: true}));
        });

        textarea.hidden = true;
        textarea.dataset.editorTiptap = 'ready';
    } catch (error) {
        // The text area stays visible: the text can still be edited and saved as HTML.
        textarea.dataset.editorTiptap = 'failed';
        Notification.addNotification({
            message: await getString('loaderror', 'editor_tiptap'),
            type: 'error',
        });
        Notification.exception(error);
    }
};
