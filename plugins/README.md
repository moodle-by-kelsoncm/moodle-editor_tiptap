# TipTap plugins (`tiptap_*`)

Subplugins of the TipTap editor. Each folder is a plugin `tiptap_<name>`:

- `version.php` and `lang/en/tiptap_<name>.php` (with `pluginname`);
- `db/access.php` with the capability `tiptap/<name>:use`;
- `classes/plugininfo.php`: class `tiptap_<name>\plugininfo` extending `\editor_tiptap\plugin`
  (`is_enabled()`, `get_config_for_context()`, `get_toolbar_items()`);
- `amd/src/plugin.js`: exports `getPlugin(KelsonTiptap, config, environment)`, which returns an
  `EditorPlugin` of `@kelsoncm/tiptap-core` (`name`, `extensions`, `register`, `messages`).
  Translations go in `messages[environment.locale]`, built from the plugin strings;
- optional `settings.php` (linked from the plugin table) and `thirdparty/`.

Administrators enable, disable and order them in
*Site administration > Plugins > Text editors > TipTap editor > General settings*.
