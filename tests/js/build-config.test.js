import { existsSync, readFileSync } from 'node:fs'
import { gunzipSync } from 'node:zlib'
import { describe, expect, it } from 'vitest'

/**
 * Читает текстовый artifact как UTF-8.
 */
function readText(path) {
  return readFileSync(path, 'utf8')
}

describe('build artifacts', () => {
  it('создает CSS artifacts из SCSS source', () => {
    expect(existsSync('media/scss/front.scss')).toBe(true)
    expect(existsSync('media/css/front.css')).toBe(true)
    expect(existsSync('media/css/front.min.css')).toBe(true)
    expect(existsSync('media/css/front.min.css.gz')).toBe(true)
    expect(readText('media/css/front.css')).toContain('.mod_ishop_compare')
  })

  it('создает JS minified и gzip artifacts, не удаляя source entrypoint', () => {
    expect(existsSync('media/js/front.js')).toBe(true)
    expect(existsSync('media/js/front.min.js')).toBe(true)
    expect(existsSync('media/js/front.min.js.gz')).toBe(true)
    expect(readText('vite.config.js.mts')).toContain('emptyOutDir: false')
  })

  it('gzip artifacts распаковываются и содержат ожидаемые маркеры', () => {
    const css = gunzipSync(readFileSync('media/css/front.min.css.gz')).toString('utf8')
    const js = gunzipSync(readFileSync('media/js/front.min.js.gz')).toString('utf8')

    expect(css).toContain('.mod_ishop_compare')
    expect(js).toContain('mod_ishop_compare.front')
  })

  it('фиксирует source-политику generated файлов', () => {
    const cssConfig = readText('vite.config.css.mts')
    const jsConfig = readText('vite.config.js.mts')

    expect(cssConfig).toContain("const SCSS_ENTRIES")
    expect(cssConfig).toContain("'front.scss'")
    expect(jsConfig).toContain("const JS_ENTRY_FILES")
    expect(jsConfig).toContain("'front.js'")
  })
})
