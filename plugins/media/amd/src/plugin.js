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
 * Images and media for the TipTap editor: the Moodle file picker, and uploads (button, paste and
 * drop) saved in the draft area of the field.
 *
 * @module     tiptap_media/plugin
 * @copyright  2026 Kelson da Costa Medeiros <kelsoncm@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Config from 'core/config';
import Notification from 'core/notification';
import {getStrings} from 'core/str';

/**
 * Uploads a file to the draft area of the field through the upload repository.
 *
 * @param {Object} picker File picker options of the type (image, media or link).
 * @param {string} uploadUrl URL of repository_ajax.php?action=upload.
 * @param {File} file
 * @returns {Promise<string>} URL of the uploaded file.
 */
const upload = async(picker, uploadUrl, file) => {
    const repository = Object.values(picker.repositories ?? {}).find((repo) => repo.type === 'upload');
    if (!repository) {
        throw new Error('No upload repository is enabled.');
    }

    const body = new FormData();
    body.append('repo_upload_file', file, file.name);
    body.append('repo_id', repository.id);
    body.append('itemid', picker.itemid);
    body.append('env', picker.env);
    body.append('sesskey', Config.sesskey);
    body.append('client_id', picker.client_id);
    body.append('savepath', picker.savepath ?? '/');
    body.append('ctx_id', picker.context.id);
    [].concat(picker.accepted_types ?? []).forEach((type) => body.append('accepted_types[]', type));

    const response = await fetch(uploadUrl, {method: 'POST', body, credentials: 'same-origin'});
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }
    const result = await response.json();
    // A file with the same name is renamed by Moodle ("fileexists" with the new file).
    const url = result.url ?? result.newfile?.url;
    if (typeof url !== 'string') {
        throw new Error(result.error ?? 'Unexpected answer from the upload repository.');
    }
    return url;
};

/**
 * Opens the Moodle file picker of a type.
 *
 * @param {Object} picker File picker options of the type.
 * @returns {Promise<Object>} Selected file (url, file...).
 */
const openPicker = (picker) => new Promise((resolve) => {
    window.M.core_filepicker.show(window.Y, {
        ...picker,
        formcallback: resolve,
        previousActiveElement: document.activeElement,
    });
});

/**
 * Builds the plugin.
 *
 * @param {Object} KelsonTiptap API of @kelsoncm/tiptap-core.
 * @param {Object} config Data from tiptap_media\plugininfo::get_config_for_context().
 * @param {Object} environment Locale id of the Moodle strings.
 * @returns {Promise<Object>} The plugin and the media options of the editor.
 */
export const getPlugin = async(KelsonTiptap, config, environment) => {
    const [imageLabel, videoLabel] = await getStrings([
        {key: 'image', component: 'tiptap_media'},
        {key: 'video', component: 'tiptap_media'},
    ]);
    const {pickers, uploadUrl} = config;

    const plugin = {
        name: 'tiptap_media',
        messages: {
            [environment.locale]: {
                'tiptap_media.image': imageLabel,
                'tiptap_media.video': videoLabel,
            },
        },
        register: (registry) => {
            const command = (id, labelKey, icon, picker, insert) => registry.register({
                id,
                labelKey,
                icon,
                group: 'insert',
                // The YUI file picker may load after this module: check it each time.
                isEnabled: (editor) => editor.isEditable && Boolean(picker) && Boolean(window.M?.core_filepicker),
                run: (editor) => {
                    openPicker(picker).then((selected) => insert(editor, selected)).catch(Notification.exception);
                    return true;
                },
            });
            command('tiptap_media_image', 'tiptap_media.image', 'image', pickers.image, (editor, selected) => {
                editor.chain().focus().setImage({src: selected.url, alt: selected.file ?? ''}).run();
            });
            command('tiptap_media_video', 'tiptap_media.video', 'media', pickers.media, (editor, selected) => {
                // The core media command chooses <video>, <audio> or an embed from the URL.
                registry.get('media')?.run(editor, selected.url);
            });
        },
    };

    return {
        plugin,
        options: {
            media: {
                upload: (file) => {
                    const picker = file.type.startsWith('image/') ? pickers.image : (pickers.link ?? pickers.image);
                    return upload(picker, uploadUrl, file);
                },
                embedHosts: config.embedHosts,
                maxFileSize: pickers.image?.maxbytes > 0 ? pickers.image.maxbytes : undefined,
                onError: (error) => Notification.exception(error),
            },
        },
    };
};
