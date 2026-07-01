/*
 * @package    tpl_itheme
 * @author     Pavel Lange <pavel@ilange.ru>
 * @link       https://github.com/i-lange/tpl_itheme
 * @copyright  (C) 2026 Pavel Lange <https://ilange.ru>
 * @license    GNU General Public License version 2 or later
 */

import fs from 'node:fs';
import path from 'node:path';
import archiver from 'archiver';
import pkg from './package.json' with { type: 'json' };

const outputDir = 'build';
const filename = `mod_ishop_compare-${pkg.version}.zip`;
const outputPath = path.join(outputDir, filename);

fs.mkdirSync(outputDir, { recursive: true });

const output = fs.createWriteStream(outputPath);
const archive = archiver('zip', { zlib: { level: 9 } });

archive.pipe(output);

for (const dir of ['language', 'media', 'services', 'src', 'tmpl']) {
  archive.directory(dir, dir);
}

for (const file of [
    'mod_ishop_compare.xml',
    'README.md',
    'script.php',
]) {
  archive.file(file, { name: file });
}

await archive.finalize();

console.log('\n✅ Создан архив для установки! Файл: ' + outputPath);
