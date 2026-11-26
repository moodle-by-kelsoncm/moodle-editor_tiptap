Plugins do TipTap
=================

Plugins do TipTap (subplugins do tipo ``tiptap``, componentes ``tiptap_<nome>``) acrescentam recursos ao editor. Ficam na
pasta ``plugins`` do editor e são gerenciados na página de configurações (veja :doc:`configuration`).

Plugins incluídos
-----------------

.. list-table::
   :header-rows: 1

   * - Plugin
     - Botões
     - Observações
   * - Imagens e mídia (``tiptap_media``)
     - Imagem pelo seletor de arquivos; vídeo ou áudio pelo seletor de arquivos
     - Só em campos que aceitam arquivos. Configuração: hosts permitidos para conteúdo incorporado (YouTube, Vimeo...
       um por linha).
   * - Links para atividades (``tiptap_link``)
     - Link para uma atividade
     - Só dentro de cursos; lista as atividades que o usuário pode ver (até 300).
   * - Notação matemática (``tiptap_math``)
     - Fórmula na linha; fórmula destacada
     - Precisa do filtro MathJax ou notação TeX habilitado para exibir as fórmulas.

Plugins que correspondem a plugins de terceiros do TinyMCE (por exemplo, importar do Word) são distribuídos à parte, para
que cada site decida se os instala. Instale-os em ``plugins/<nome>`` e acesse **Administração do site > Notificações**.

.. list-table::
   :header-rows: 1

   * - Plugin
     - Botões
     - Repositório
   * - Importar do Word (``tiptap_office``)
     - Importar do Word (.docx), convertido no navegador
     - `moodle-tiptap_office <https://github.com/moodle-by-kelsoncm/moodle-tiptap_office>`_ (pasta ``plugins/office``)

Escrevendo um plugin do TipTap
------------------------------

Um plugin ``tiptap_<nome>`` em ``plugins/<nome>`` tem:

- ``version.php`` e ``lang/en/tiptap_<nome>.php`` (com ``pluginname`` e ``privacy:metadata``);
- ``db/access.php`` com a permissão ``tiptap/<nome>:use``;
- ``classes/plugininfo.php``: classe ``tiptap_<nome>\plugininfo`` que estende ``\editor_tiptap\plugin``. Pode
  sobrescrever ``is_enabled()``, ``get_config_for_context()`` (dados para o JavaScript) e ``get_toolbar_items()`` (seus
  botões);
- ``classes/privacy/provider.php``;
- ``amd/src/plugin.js``, que exporta ``getPlugin(KelsonTiptap, config, environment)``. Devolve um ``EditorPlugin`` do
  `tiptap-core <https://tiptap-by-kelsoncm.github.io/tiptap-core/docs/plugins.html>`_ (``name``, ``extensions``, ``register``,
  ``messages``), ou ``{plugin, options}`` para também definir opções do editor (por exemplo ``media.upload``).
  ``KelsonTiptap`` é a API do core incluído, com as classes do Tiptap ``Node``, ``Mark`` e ``Extension``, então o plugin
  não precisa de outra cópia do Tiptap;
- opcionais: ``settings.php`` (com link na tabela de plugins) e ``thirdparty``.

Os textos do plugin são strings do Moodle: carregue-as com ``core/str`` e entregue-as ao editor em
``messages[environment.locale]``.
