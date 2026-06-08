import { readFileSync } from 'node:fs'
import { promisify } from 'node:util'
import yauzl from 'yauzl'
import { describe, expect, it } from 'vitest'
import pkg from '../../package.json' with { type: 'json' }

const openZip = promisify(yauzl.open)
const zipFile = `mod_ishop_compare-${pkg.version}.zip`

/**
 * Читает central directory zip без распаковки архива на диск.
 */
async function readZipEntries(file) {
  const zip = await openZip(file, { lazyEntries: true })
  const entries = []

  return new Promise((resolve, reject) => {
    zip.readEntry()
    zip.on('entry', (entry) => {
      entries.push(entry.fileName)
      zip.readEntry()
    })
    zip.on('end', () => resolve(entries))
    zip.on('error', reject)
  })
}

/**
 * Проверяет наличие хотя бы одного файла внутри директории архива.
 */
function hasEntryInside(entries, directory) {
  return entries.some((entry) => entry.startsWith(`${directory}/`) && entry !== `${directory}/`)
}

describe('installable zip package', () => {
  it('создает архив с именем из package.version', async () => {
    const entries = await readZipEntries(zipFile)

    expect(entries.length).toBeGreaterThan(0)
  })

  it('содержит обязательные корневые файлы', async () => {
    const entries = await readZipEntries(zipFile)

    expect(entries).toContain('mod_ishop_compare.xml')
    expect(entries).toContain('README.md')
    expect(entries).toContain('script.php')
  })

  it('содержит обязательные директории расширения', async () => {
    const entries = await readZipEntries(zipFile)

    for (const directory of ['language', 'media', 'services', 'src', 'tmpl']) {
      expect(hasEntryInside(entries, directory)).toBe(true)
    }
  })

  it('содержит media artifacts и source SCSS согласно текущей packaging policy', async () => {
    const entries = await readZipEntries(zipFile)

    for (const file of [
      'media/joomla.asset.json',
      'media/css/front.css',
      'media/css/front.min.css',
      'media/css/front.min.css.gz',
      'media/js/front.js',
      'media/js/front.min.js',
      'media/js/front.min.js.gz',
      'media/scss/front.scss',
    ]) {
      expect(entries).toContain(file)
    }
  })

  it('не содержит development/test artifacts', async () => {
    const entries = await readZipEntries(zipFile)
    const forbiddenPrefixes = ['.git/', '.idea/', 'node_modules/', 'vendor/', 'tests/', 'coverage/', 'build/']

    for (const entry of entries) {
      expect(forbiddenPrefixes.some((prefix) => entry.startsWith(prefix))).toBe(false)
      expect(entry.endsWith('.zip')).toBe(false)
    }
  })

  it('содержит все файлы и папки, объявленные в manifest', async () => {
    const entries = await readZipEntries(zipFile)
    const manifest = readFileSync('mod_ishop_compare.xml', 'utf8')
    const folders = Array.from(manifest.matchAll(/<folder(?:\s+[^>]*)?>([^<]+)<\/folder>/g)).map((match) => match[1])
    const languages = Array.from(manifest.matchAll(/<language[^>]*>([^<]+)<\/language>/g)).map((match) => match[1])

    for (const folder of folders) {
      const normalized = folder.startsWith('css') || folder.startsWith('js') ? `media/${folder}` : folder
      expect(hasEntryInside(entries, normalized)).toBe(true)
    }

    for (const language of languages) {
      expect(entries).toContain(language)
    }

    expect(entries).toContain('script.php')
    expect(entries).toContain('media/joomla.asset.json')
  })
})
