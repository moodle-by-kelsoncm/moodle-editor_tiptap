TipTap plugins
==============

TipTap plugins (subplugins of type ``tiptap``, components ``tiptap_<name>``) add features to the editor. They live in
the ``plugins`` folder of the editor and are managed on the editor settings page (see :doc:`configuration`).

Bundled plugins
---------------

.. list-table::
   :header-rows: 1

   * - Plugin
     - Buttons
     - Notes
   * - Images and media (``tiptap_media``)
     - Image from the file picker; video or audio from the file picker
     - Only in fields that accept files. Setting: hosts allowed for embedded content (YouTube, Vimeo... one per line).
   * - Links to activities (``tiptap_link``)
     - Link to an activity
     - Only inside courses; lists the activities the user can see (up to 300).
   * - Mathematical notation (``tiptap_math``)
     - Inline equation; display equation
     - Needs the MathJax or TeX notation filter enabled to show the equations.

Plugins that match third-party TinyMCE plugins (for example import from Word) are distributed as separate plugins, so each
site decides whether to install them. Install them in ``plugins/<name>`` and visit **Site administration > Notifications**.

Writing a TipTap plugin
-----------------------

A plugin ``tiptap_<name>`` in ``plugins/<name>`` has:

- ``version.php`` and ``lang/en/tiptap_<name>.php`` (with ``pluginname`` and ``privacy:metadata``);
- ``db/access.php`` with the capability ``tiptap/<name>:use``;
- ``classes/plugininfo.php``: class ``tiptap_<name>\plugininfo`` extending ``\editor_tiptap\plugin``. It can override
  ``is_enabled()``, ``get_config_for_context()`` (data for the JavaScript) and ``get_toolbar_items()`` (its buttons);
- ``classes/privacy/provider.php``;
- ``amd/src/plugin.js``, exporting ``getPlugin(KelsonTiptap, config, environment)``. It returns an ``EditorPlugin`` of
  `tiptap-core <https://tiptap-by-kelsoncm.github.io/tiptap-core/docs/plugins.html>`_ (``name``, ``extensions``, ``register``,
  ``messages``), or ``{plugin, options}`` to also set options of the editor (for example ``media.upload``).
  ``KelsonTiptap`` is the API of the bundled core, including the Tiptap classes ``Node``, ``Mark`` and ``Extension``, so the
  plugin does not need its own copy of Tiptap;
- optional ``settings.php`` (linked from the plugin table) and ``thirdparty``.

The texts of the plugin are Moodle strings: load them with ``core/str`` and give them to the editor in
``messages[environment.locale]``.
