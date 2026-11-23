// Updates the bundled @kelsoncm/tiptap-core: downloads the UMD build and its CSS from the
// GitHub release into thirdparty/tiptap-core/, records the version in thirdpartylibs.xml and
// regenerates the "kt:" strings of lang/en/editor_tiptap.php from the core English texts.
//
// Usage: node .github/scripts/update-tiptap-core.mjs 0.17.0
//        node .github/scripts/update-tiptap-core.mjs --strings-only   (only lang/en, from the files already in thirdparty/)
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createContext, runInContext } from 'node:vm';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const TARGET = join(ROOT, 'thirdparty', 'tiptap-core');
const UMD = join(TARGET, 'kelsoncm-tiptap-core.umd.js');
const CSS = join(TARGET, 'kelsoncm-tiptap-core.css');
const LANG = join(ROOT, 'lang', 'en', 'editor_tiptap.php');
const THIRDPARTY = join(ROOT, 'thirdpartylibs.xml');
const PREFIX = 'kt:';

async function download(version) {
  const base = `https://github.com/tiptap-by-kelsoncm/tiptap-core/releases/download/v${version}`;
  mkdirSync(TARGET, { recursive: true });
  for (const [name, file] of [
    ['kelsoncm-tiptap-core.umd.js', UMD],
    ['kelsoncm-tiptap-core.css', CSS],
  ]) {
    const response = await fetch(`${base}/${name}`);
    if (!response.ok) throw new Error(`Falha ao baixar ${base}/${name}: HTTP ${response.status}`);
    let content = await response.text();
    // Moodle ships no source maps for third-party files.
    content = content.replace(/\n?\/\/# sourceMappingURL=.*$/m, '');
    writeFileSync(file, content);
  }
  const xml = readFileSync(THIRDPARTY, 'utf8').replace(
    /(<location>thirdparty\/tiptap-core<\/location>[\s\S]*?<version>)[^<]*(<\/version>)/,
    `$1${version}$2`,
  );
  writeFileSync(THIRDPARTY, xml);
}

/** English texts of the core, read by running the UMD build as a browser script would. */
function coreMessages() {
  const context = createContext({});
  runInContext(readFileSync(UMD, 'utf8'), context);
  const messages = context.KelsonTiptap?.builtInMessages?.en;
  if (!messages) throw new Error(`${UMD} não expõe KelsonTiptap.builtInMessages.en.`);
  return messages;
}

/** "{ratio}" (core) becomes "{$a->ratio}" (Moodle); the editor converts it back. */
const toMoodle = (text) => text.replace(/\{([A-Za-z0-9_]+)\}/g, '{$a->$1}');
const phpString = (text) => `'${text.replaceAll('\\', '\\\\').replaceAll("'", "\\'")}'`;

function writeStrings(messages) {
  const source = readFileSync(LANG, 'utf8');
  const start = source.indexOf('$string[');
  const header = source.slice(0, start);
  const own = {};
  for (const match of source.slice(start).matchAll(/^\$string\['([^']+)'\] = '((?:[^'\\]|\\.)*)';$/gm)) {
    if (!match[1].startsWith(PREFIX)) own[match[1]] = match[2];
  }
  const entries = Object.entries(own).map(([key, value]) => [key, `'${value}'`]);
  for (const [key, text] of Object.entries(messages)) {
    entries.push([`${PREFIX}${key}`, phpString(toMoodle(text))]);
  }
  entries.sort(([a], [b]) => (a < b ? -1 : a > b ? 1 : 0));
  const body = entries.map(([key, value]) => `$string['${key}'] = ${value};`).join('\n');
  writeFileSync(LANG, `${header}${body}\n`);
  return Object.keys(messages).length;
}

const argument = process.argv[2];
if (!argument) {
  console.error('Uso: node .github/scripts/update-tiptap-core.mjs <versão> | --strings-only');
  process.exit(1);
}
if (argument !== '--strings-only') await download(argument.replace(/^v/, ''));
const count = writeStrings(coreMessages());
console.log(`tiptap-core ${argument}: ${count} textos em lang/en/editor_tiptap.php`);
